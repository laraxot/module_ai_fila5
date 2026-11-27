<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Outreach;

use Illuminate\Support\Str;
use Modules\AI\Datas\OutreachGateLimitsData;
use Modules\AI\Datas\OutreachGateResultData;
use Modules\AI\Datas\OutreachGateViolationData;
use Modules\AI\Datas\OutreachMessageData;
use Modules\AI\Enums\OutreachGateViolationEnum as Violation;
use Spatie\QueueableAction\QueueableAction;

use function Safe\preg_match;
use function Safe\preg_match_all;
use function Safe\preg_replace;

/**
 * Gate di invio outreach: puro, deterministico, senza DB/HTTP/LLM.
 *
 * Raccoglie TUTTE le violazioni (non si ferma alla prima) cosi' l'UI puo' spiegarle. Restituisce codici
 * stabili + `context`, mai testo per l'utente. Non decide la base giuridica: la espone e pretende che ci sia.
 * L'approvazione umana e' sempre bloccante: non esiste una modalita' autopilot.
 *
 * @see docs/adr/0004-phpstan-zero-errori.md — costanti tipizzate (PHP 8.3) e regex via `Safe\preg_*`: PCRE non puo' restituire `false` silenzioso
 * @see https://phpstan.org/error-identifiers/theCodingMachineSafe.function
 */
final class OutreachGateAction
{
    use QueueableAction;

    private const array DELIVERABLE_STATUSES = ['verified', 'risky'];

    /** Solo le persone giuridiche non sono dato personale; tutto il resto (anche `unknown`) richiede consenso. */
    private const array NON_PERSONAL_SUBJECT_TYPES = ['company'];

    private const string CONSENT = 'consent';

    private const string LINK_PATTERN = '/\b(?:https?:\/\/|www\.)[^\s<>"]+/iu';

    private const string HTML_TAG_PATTERN = '/<\/?[a-z][a-z0-9-]*(?:\s[^<>]*)?\/?>/i';

    /**
     * Candidati segnaposto in ordine di comparsa: {{x}}, {x}, [fill: ..., [x], TODO, LOREM.
     * Contano solo se contengono lettere (filtro in `placeholders()`): `{{1}}` e `[1]` passano.
     */
    private const string PLACEHOLDER_PATTERN = '/\{\{[^{}]*\}\}|(?<!\{)\{(?!\{)[^{}]*\}(?!\})|\[fill:[^\]]*\]?|\[[^\[\]]*\]|\b(?:todo|lorem)\b/iu';

    // ponytail: opt-out riconosciuto con regex solo IT+EN sul testo normalizzato (minuscolo, senza accenti),
    // ovunque nel corpo e non solo in coda; altre lingue/forme: spostare in config per locale.
    private const array OPT_OUT_PATTERNS = [
        '/\bnon (?:desideri|desidera|desiderate|vuoi|vuole|volete|vorresti|vorreste) (?:\w+ )?ricevere\b/',
        '/\b(?:cancellarti|cancellarvi|cancellarmi|disiscriverti|disiscrivervi|opporti|opporvi)\b/',
        '/\brispondi (?:con )?(?:la parola )?stop\b/',
        '/\bunsubscribe\b/',
        '/\bopt[ -]?out\b/',
        '/\b(?:do not|don\'t) want to (?:receive|hear)\b/',
        '/\bno longer (?:wish|want) to (?:receive|hear)\b/',
    ];

    // ponytail: ~12 formule da AI-slop, IT+EN, match letterale sul testo normalizzato; non rileva parafrasi.
    // Upgrade: lista in config o classificatore, se le parafrasi diventano un problema reale.
    private const array BANNED_PHRASES = [
        'spero che questa email ti trovi bene',
        'spero che questa mail ti trovi bene',
        'spero che tu stia bene',
        'mi permetto di contattarti',
        'non esitare a contattarmi',
        "soluzione all'avanguardia",
        'i hope this email finds you well',
        'i hope this message finds you well',
        'i hope you are doing well',
        "i hope you're doing well",
        'i wanted to reach out',
        "don't hesitate to reach out",
    ];

    /** @param list<string> $suppressedEmails indirizzi, oppure `@dominio.tld` per sopprimere un intero dominio */
    public function execute(
        OutreachMessageData $message,
        array $suppressedEmails = [],
        ?OutreachGateLimitsData $limits = null,
    ): OutreachGateResultData {
        $limits ??= new OutreachGateLimitsData();
        // Bytes UTF-8 invalidi farebbero lanciare Safe\preg_*: li rendiamo '?' e si valuta comunque il resto.
        $subject = mb_scrub($message->subject);
        $body = mb_scrub($message->body);

        return OutreachGateResultData::fromViolations([
            ...$this->checkRecipient($message, $suppressedEmails),
            ...$this->checkAuthorisation($message),
            ...$this->checkSize($subject, $body, $limits),
            ...$this->checkBodyShape($subject, $body, $message->isHtml, $limits),
            ...$this->checkSenderIdentity($message, $body),
            ...$this->checkOptOut($body),
            ...$this->checkStyle($subject, $body),
        ]);
    }

    /**
     * @param  list<string>  $suppressedEmails
     * @return list<OutreachGateViolationData>
     */
    private function checkRecipient(OutreachMessageData $message, array $suppressedEmails): array
    {
        $violations = [];
        $recipient = $this->canonicalEmail($message->recipientEmail);

        $match = $this->suppressionMatch($recipient, $suppressedEmails);
        if ($match !== null) {
            $violations[] = new OutreachGateViolationData(Violation::RECIPIENT_SUPPRESSED, ['match' => $match]);
        }

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE) === false) {
            $violations[] = new OutreachGateViolationData(Violation::RECIPIENT_INVALID);
        }

        if ($recipient !== $this->canonicalEmail($message->prospectEmail)) {
            $violations[] = new OutreachGateViolationData(Violation::RECIPIENT_MISMATCH);
        }

        $status = $this->lower($message->emailStatus);
        if (! in_array($status, self::DELIVERABLE_STATUSES, true)) {
            $violations[] = new OutreachGateViolationData(Violation::EMAIL_NOT_DELIVERABLE, ['status' => $status]);
        }

        return $violations;
    }

    /** @return list<OutreachGateViolationData> */
    private function checkAuthorisation(OutreachMessageData $message): array
    {
        $violations = [];

        if (trim($message->approvedByUserId ?? '') === '') {
            $violations[] = new OutreachGateViolationData(Violation::MISSING_HUMAN_APPROVAL);
        }

        $basis = $this->lower($message->lawfulBasis ?? '');
        if ($basis === '') {
            $violations[] = new OutreachGateViolationData(Violation::MISSING_LAWFUL_BASIS);
        }

        $subjectType = $this->lower($message->subjectType);
        if (! in_array($subjectType, self::NON_PERSONAL_SUBJECT_TYPES, true) && $basis !== self::CONSENT) {
            $violations[] = new OutreachGateViolationData(Violation::SUBJECT_REQUIRES_CONSENT, ['subjectType' => $subjectType]);
        }

        return $violations;
    }

    /** @return list<OutreachGateViolationData> */
    private function checkSize(string $subject, string $body, OutreachGateLimitsData $limits): array
    {
        $violations = [];

        $length = mb_strlen(trim($subject));
        if ($length === 0) {
            $violations[] = new OutreachGateViolationData(Violation::SUBJECT_EMPTY);
        } elseif ($length > $limits->maxSubjectChars) {
            $violations[] = new OutreachGateViolationData(Violation::SUBJECT_TOO_LONG, ['length' => $length, 'max' => $limits->maxSubjectChars]);
        }

        $words = preg_match_all('/\S+/u', $body);
        if ($words === 0) {
            $violations[] = new OutreachGateViolationData(Violation::BODY_EMPTY);
        } elseif ($words > $limits->maxBodyWords) {
            $violations[] = new OutreachGateViolationData(Violation::BODY_TOO_LONG, ['words' => $words, 'max' => $limits->maxBodyWords]);
        }

        return $violations;
    }

    /** @return list<OutreachGateViolationData> */
    private function checkBodyShape(string $subject, string $body, bool $isHtml, OutreachGateLimitsData $limits): array
    {
        $violations = [];

        // ponytail: link = solo http(s):// e www., niente domini nudi tipo "acme.it/x"; upgrade: parser di domini.
        $links = preg_match_all(self::LINK_PATTERN, $body);
        if ($links > $limits->maxLinks) {
            $violations[] = new OutreachGateViolationData(Violation::TOO_MANY_LINKS, ['count' => $links, 'max' => $limits->maxLinks]);
        }

        foreach ($this->placeholders($subject."\n".$body) as $token) {
            $oneLine = preg_replace('/\s+/u', ' ', $token);
            $violations[] = new OutreachGateViolationData(Violation::UNRESOLVED_PLACEHOLDER, ['token' => mb_substr($oneLine, 0, 80)]);
        }

        if ($isHtml) {
            $violations[] = new OutreachGateViolationData(Violation::HTML_BODY, ['reason' => 'flag']);
        } elseif (preg_match(self::HTML_TAG_PATTERN, $body) === 1) {
            $violations[] = new OutreachGateViolationData(Violation::HTML_BODY, ['reason' => 'tags']);
        }

        return $violations;
    }

    /** @return list<OutreachGateViolationData> */
    private function checkSenderIdentity(OutreachMessageData $message, string $body): array
    {
        $violations = [];
        $fields = [
            'senderName' => $message->senderName,
            'senderCompany' => $message->senderCompany,
            'senderPostalAddress' => $message->senderPostalAddress,
        ];

        foreach ($fields as $field => $value) {
            if (trim($value) === '') {
                $violations[] = new OutreachGateViolationData(Violation::SENDER_IDENTITY_MISSING, ['field' => $field]);
            }
        }

        $haystack = $this->alnum($body);
        $identities = array_filter([$message->senderName, $message->senderCompany], static fn (string $value): bool => trim($value) !== '');

        if ($identities !== [] && ! $this->anyAppearsIn($identities, $haystack)) {
            $violations[] = new OutreachGateViolationData(Violation::SENDER_NOT_IN_BODY, ['field' => 'name_or_company']);
        }

        if (trim($message->senderPostalAddress) !== '' && ! $this->anyAppearsIn([$message->senderPostalAddress], $haystack)) {
            $violations[] = new OutreachGateViolationData(Violation::SENDER_NOT_IN_BODY, ['field' => 'postal_address']);
        }

        return $violations;
    }

    /** @return list<OutreachGateViolationData> */
    private function checkOptOut(string $body): array
    {
        $folded = $this->fold($body);

        foreach (self::OPT_OUT_PATTERNS as $pattern) {
            if (preg_match($pattern, $folded) === 1) {
                return [];
            }
        }

        return [new OutreachGateViolationData(Violation::MISSING_OPT_OUT_LINE)];
    }

    /** @return list<OutreachGateViolationData> */
    private function checkStyle(string $subject, string $body): array
    {
        $violations = [];
        $text = $subject."\n".$body;
        $folded = $this->fold($text);

        foreach (self::BANNED_PHRASES as $phrase) {
            if (str_contains($folded, $phrase)) {
                $violations[] = new OutreachGateViolationData(Violation::BANNED_PHRASE, ['phrase' => $phrase]);
            }
        }

        $dashes = preg_match_all('/[\x{2013}\x{2014}]/u', $text);
        if ($dashes > 0) {
            $violations[] = new OutreachGateViolationData(Violation::LONG_DASH, ['count' => $dashes]);
        }

        $exclamations = preg_match_all('/[!\x{FF01}]/u', $text);
        if ($exclamations > 1) {
            $violations[] = new OutreachGateViolationData(Violation::MULTIPLE_EXCLAMATION_MARKS, ['count' => $exclamations]);
        }

        return $violations;
    }

    /** @return list<string> segnaposto distinti, in ordine di comparsa */
    private function placeholders(string $text): array
    {
        preg_match_all(self::PLACEHOLDER_PATTERN, $text, $matches);

        $withLetters = array_filter($matches[0], static fn (string $token): bool => preg_match('/\p{L}/u', $token) === 1);

        return array_values(array_unique($withLetters));
    }

    /**
     * Minuscolo, senza spazi ai bordi; `+tag` tolto dalla parte locale.
     *
     * ponytail: il `+tag` si toglie sempre (conservativo: anche dove `+` e' un carattere reale della casella);
     * upgrade: elenco dei provider che lo supportano.
     */
    private function canonicalEmail(string $email): string
    {
        $email = $this->lower($email);
        $at = strrpos($email, '@');
        if ($at === false) {
            return $email;
        }

        $local = substr($email, 0, $at);
        $plus = strpos($local, '+');
        if ($plus !== false && $plus > 0) {
            $local = substr($local, 0, $plus);
        }

        return $local.substr($email, $at);
    }

    /**
     * ponytail: il dominio soppresso si confronta per uguaglianza esatta, niente sottodomini;
     * upgrade: confronto per suffisso con confine su '.'.
     *
     * @param  list<string>  $suppressed
     */
    private function suppressionMatch(string $recipient, array $suppressed): ?string
    {
        $at = strrpos($recipient, '@');
        $domain = $at === false ? '' : substr($recipient, $at + 1);

        foreach ($suppressed as $entry) {
            $entry = $this->lower($entry);
            if ($entry === '' || $entry === '@') {
                continue;
            }

            if (str_starts_with($entry, '@')) {
                if ($domain !== '' && substr($entry, 1) === $domain) {
                    return 'domain';
                }

                continue;
            }

            if ($recipient !== '' && $this->canonicalEmail($entry) === $recipient) {
                return 'email';
            }
        }

        return null;
    }

    /** @param array<array-key, string> $needles */
    private function anyAppearsIn(array $needles, string $haystack): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $this->alnum($needle))) {
                return true;
            }
        }

        return false;
    }

    private function lower(string $text): string
    {
        return mb_strtolower(trim($text));
    }

    /** Minuscolo, ASCII (accenti e apostrofi tipografici), "e-mail" come "email", spazi collassati. */
    private function fold(string $text): string
    {
        $ascii = Str::ascii(mb_strtolower($text));

        return trim(preg_replace('/\s+/', ' ', str_replace('e-mail', 'email', $ascii)));
    }

    /** Solo lettere/cifre ASCII separate da un singolo spazio, con uno spazio ai bordi (match a parola intera). */
    private function alnum(string $text): string
    {
        return ' '.trim(preg_replace('/[^a-z0-9]+/', ' ', $this->fold($text))).' ';
    }
}
