<?php

declare(strict_types=1);

namespace Modules\AI\Tests\Unit\Actions\Outreach;

use Modules\AI\Actions\Outreach\OutreachGateAction;
use Modules\AI\Datas\OutreachGateLimitsData;
use Modules\AI\Datas\OutreachGateResultData;
use Modules\AI\Datas\OutreachGateViolationData;
use Modules\AI\Datas\OutreachMessageData;
use Modules\AI\Enums\OutreachGateViolationEnum as V;
use Modules\AI\Tests\TestCase;

uses(TestCase::class);

const PERFECT_BODY = "Ciao Gino,\n\n"
    .'ho guardato il sito della Trattoria Da Gino e non ho trovato un modo per prenotare un tavolo online. '
    .'Posso preparare gratis una bozza di pagina di prenotazione da mostrarti questa settimana, senza impegno. '
    ."Ti interessa vederla?\n\n"
    ."Marco Bianchi\n"
    ."Laraxot S.r.l., Via Roma 1, 20100 Milano\n\n"
    .'Se non desideri ricevere altre email, rispondi stop e non ti scrivo piu.';

/**
 * Messaggio che supera il gate; ogni test ne cambia solo il campo sotto esame.
 *
 * @param  array<string, mixed>  $overrides
 */
function perfectMessage(array $overrides = []): OutreachMessageData
{
    return OutreachMessageData::from([
        'recipientEmail' => 'gino@trattoria.example',
        'prospectEmail' => 'gino@trattoria.example',
        'subject' => 'Idea per il sito della Trattoria Da Gino',
        'body' => PERFECT_BODY,
        'senderName' => 'Marco Bianchi',
        'senderCompany' => 'Laraxot S.r.l.',
        'senderPostalAddress' => 'Via Roma 1, 20100 Milano',
        'emailStatus' => 'verified',
        'subjectType' => 'company',
        'lawfulBasis' => 'legitimate_interest',
        'approvedByUserId' => 'user-1',
        'isHtml' => false,
        ...$overrides,
    ]);
}

/**
 * @param  list<string>  $suppressed
 * @return list<string>
 */
function codesOf(OutreachMessageData $message, array $suppressed = [], ?OutreachGateLimitsData $limits = null): array
{
    $result = (new OutreachGateAction())->execute($message, $suppressed, $limits);

    return array_values(array_unique(array_map(
        static fn (OutreachGateViolationData $v): string => $v->code->value,
        $result->violations,
    )));
}

/** @param list<string> $suppressed */
function gate(OutreachMessageData $message, array $suppressed = [], ?OutreachGateLimitsData $limits = null): OutreachGateResultData
{
    return (new OutreachGateAction())->execute($message, $suppressed, $limits);
}

// ---------------------------------------------------------------- baseline

test('lets a perfect message through with no violations', function (): void {
    $result = gate(perfectMessage());

    expect($result->allowed)->toBeTrue()
        ->and($result->violations)->toBe([]);
});

test('is deterministic and does not mutate the input', function (): void {
    $message = perfectMessage(['subject' => '', 'body' => '']);
    $before = $message->toArray();

    $first = gate($message)->toArray();
    $second = gate($message)->toArray();

    expect($second)->toBe($first)
        ->and($message->toArray())->toBe($before);
});

test('serializes violations with stable string codes and scalar context', function (): void {
    $result = gate(perfectMessage(), ['gino@trattoria.example']);
    $array = $result->toArray();

    expect($result->allowed)->toBeFalse()
        ->and($result->has(V::RECIPIENT_SUPPRESSED))->toBeTrue()
        ->and($result->has(V::BODY_EMPTY))->toBeFalse()
        ->and($array)->toHaveKey('allowed', false)
        ->and($array)->toHaveKey('violations.0.code', 'recipient_suppressed')
        ->and($array)->toHaveKey('violations.0.context.match', 'email');
});

// ---------------------------------------------------------- 1. suppression

dataset('suppressed', [
    'exact address' => ['gino@trattoria.example'],
    'case and spaces' => ['  GINO@Trattoria.Example  '],
    'plus tag on the list' => ['gino+promo@trattoria.example'],
    'whole domain' => ['@trattoria.example'],
    'whole domain, case and spaces' => [' @TRATTORIA.example '],
    'among other entries' => ['x@y.example', '', '@altro.example', 'gino@trattoria.example'],
]);

it('blocks a suppressed recipient', function (string ...$list): void {
    $result = gate(perfectMessage(), array_values($list));

    expect($result->allowed)->toBeFalse()
        ->and($result->has(V::RECIPIENT_SUPPRESSED))->toBeTrue();
})->with('suppressed');

it('blocks a recipient written with a plus tag when the plain address is suppressed', function (): void {
    $message = perfectMessage(['recipientEmail' => 'Gino+Offerta@Trattoria.example', 'prospectEmail' => 'gino@trattoria.example']);

    expect(codesOf($message, ['gino@trattoria.example']))->toBe([V::RECIPIENT_SUPPRESSED->value]);
});

dataset('not-suppressed', [
    'empty list' => [],
    'other address' => ['luigi@trattoria.example'],
    'other domain' => ['@altro.example'],
    'subdomain of the suppressed domain is a different domain' => ['@mail.trattoria.example'],
    'blank and bare-at entries are ignored' => ['', '   ', '@'],
    'domain-looking entry without at is not a domain rule' => ['trattoria.example'],
]);

it('does not block a recipient that is not suppressed', function (string ...$list): void {
    expect(gate(perfectMessage(), array_values($list))->allowed)->toBeTrue();
})->with('not-suppressed');

// ----------------------------------------------- 2. recipient vs prospect

it('blocks a recipient that differs from the prospect address', function (): void {
    $message = perfectMessage(['recipientEmail' => 'altro@trattoria.example']);

    expect(codesOf($message))->toBe([V::RECIPIENT_MISMATCH->value]);
});

it('treats case, spaces and plus tags as the same address', function (): void {
    $message = perfectMessage(['recipientEmail' => '  GINO+x@Trattoria.EXAMPLE ', 'prospectEmail' => 'gino@trattoria.example']);

    expect(gate($message)->allowed)->toBeTrue();
});

it('accepts a unicode local part', function (): void {
    $message = perfectMessage(['recipientEmail' => 'JOSÉ@trattoria.example', 'prospectEmail' => 'josé@trattoria.example']);

    expect(gate($message)->allowed)->toBeTrue();
});

dataset('invalid-recipients', [
    'empty' => [''],
    'blank' => ['   '],
    'no at sign' => ['gino.trattoria.example'],
    'no domain' => ['gino@'],
    'two at signs' => ['gino@@trattoria.example'],
    'inner spaces' => ['gino rossi@trattoria.example'],
]);

it('blocks a recipient that is not a valid address even if it equals the prospect address', function (string $address): void {
    $message = perfectMessage(['recipientEmail' => $address, 'prospectEmail' => $address]);

    expect(codesOf($message))->toBe([V::RECIPIENT_INVALID->value]);
})->with('invalid-recipients');

// ---------------------------------------------------- 3. email status

dataset('deliverable-statuses', ['verified', 'risky', ' VERIFIED ', 'Risky']);
dataset('undeliverable-statuses', ['guess', 'invalid', 'GUESS', '', 'unknown', 'valid', 'catch-all']);

it('lets verified and risky addresses through', function (string $status): void {
    expect(gate(perfectMessage(['emailStatus' => $status]))->allowed)->toBeTrue();
})->with('deliverable-statuses');

it('never sends to guessed, invalid or unrecognised statuses', function (string $status): void {
    expect(codesOf(perfectMessage(['emailStatus' => $status])))->toBe([V::EMAIL_NOT_DELIVERABLE->value]);
})->with('undeliverable-statuses');

// ------------------------------------------------------ 4. human approval

dataset('missing-approvals', [[null], [''], ['   ']]);

it('always blocks without a human approval', function (?string $approver): void {
    expect(codesOf(perfectMessage(['approvedByUserId' => $approver])))->toBe([V::MISSING_HUMAN_APPROVAL->value]);
})->with('missing-approvals');

it('has no limit or option that can switch the approval requirement off', function (): void {
    $limitProperties = array_keys(get_object_vars(new OutreachGateLimitsData()));

    expect($limitProperties)->toBe(['maxSubjectChars', 'maxBodyWords', 'maxLinks'])
        ->and(codesOf(perfectMessage(['approvedByUserId' => null]), [], new OutreachGateLimitsData(1000, 1000, 1000)))
        ->toBe([V::MISSING_HUMAN_APPROVAL->value]);
});

// ------------------------------------------------------- 5. lawful basis

dataset('missing-bases', [[null], [''], ['  ']]);

it('blocks when the lawful basis is absent or blank', function (?string $basis): void {
    expect(codesOf(perfectMessage(['lawfulBasis' => $basis])))->toBe([V::MISSING_LAWFUL_BASIS->value]);
})->with('missing-bases');

it('exposes the lawful basis without judging which one it is', function (): void {
    expect(gate(perfectMessage(['lawfulBasis' => 'whatever_the_caller_says']))->allowed)->toBeTrue();
});

// ----------------------------------------------- 6. subject type / consent

dataset('consent-matrix', [
    'person without consent' => ['person', 'legitimate_interest', true],
    'person with consent' => ['person', 'consent', false],
    'person with consent, case and spaces' => [' PERSON ', ' Consent ', false],
    'sole proprietor without consent' => ['sole_proprietor', 'legitimate_interest', true],
    'sole proprietor with consent' => ['sole_proprietor', 'consent', false],
    'professional without consent' => ['professional', 'legitimate_interest', true],
    'professional with consent' => ['professional', 'consent', false],
    'company without consent' => ['company', 'legitimate_interest', false],
    'company with consent' => ['company', 'consent', false],
    'unknown without consent is treated as personal data' => ['unknown', 'legitimate_interest', true],
    'unknown with consent' => ['unknown', 'consent', false],
    'unrecognised type without consent' => ['', 'legitimate_interest', true],
]);

it('requires consent for anything that may be personal data', function (string $type, string $basis, bool $blocked): void {
    $codes = codesOf(perfectMessage(['subjectType' => $type, 'lawfulBasis' => $basis]));

    expect($codes)->toBe($blocked ? [V::SUBJECT_REQUIRES_CONSENT->value] : []);
})->with('consent-matrix');

it('reports a missing basis and the consent requirement separately for a person', function (): void {
    expect(codesOf(perfectMessage(['subjectType' => 'person', 'lawfulBasis' => null])))
        ->toBe([V::MISSING_LAWFUL_BASIS->value, V::SUBJECT_REQUIRES_CONSENT->value]);
});

// ------------------------------------------------ 7. subject and body size

it('accepts a subject of exactly 90 characters and blocks 91', function (): void {
    expect(gate(perfectMessage(['subject' => str_repeat('a', 90)]))->allowed)->toBeTrue()
        ->and(codesOf(perfectMessage(['subject' => str_repeat('a', 91)])))->toBe([V::SUBJECT_TOO_LONG->value]);
});

it('counts subject characters, not bytes', function (): void {
    expect(gate(perfectMessage(['subject' => str_repeat('é', 90)]))->allowed)->toBeTrue()
        ->and(gate(perfectMessage(['subject' => str_repeat('😀', 90)]))->allowed)->toBeTrue();
});

dataset('empty-subjects', [[''], ['   '], ["\n\t"]]);

it('blocks an empty subject', function (string $subject): void {
    expect(codesOf(perfectMessage(['subject' => $subject])))->toBe([V::SUBJECT_EMPTY->value]);
})->with('empty-subjects');

it('accepts a body of exactly 150 words and blocks 151', function (): void {
    $filler = static fn (int $words): string => implode(' ', array_fill(0, $words, 'parola'));
    $tail = "\n\nMarco Bianchi\nLaraxot S.r.l., Via Roma 1, 20100 Milano\nNon desideri ricevere altre email? Rispondi stop.";
    $tailWords = 16;

    $atLimit = perfectMessage(['body' => $filler(150 - $tailWords).$tail]);
    $overLimit = perfectMessage(['body' => $filler(151 - $tailWords).$tail]);

    expect(gate($atLimit)->allowed)->toBeTrue()
        ->and(codesOf($overLimit))->toBe([V::BODY_TOO_LONG->value]);
});

dataset('empty-bodies', [[''], ['  '], ["\n\n"]]);

it('blocks an empty body', function (string $body): void {
    expect(gate(perfectMessage(['body' => $body]))->has(V::BODY_EMPTY))->toBeTrue()
        ->and(gate(perfectMessage(['body' => $body]))->has(V::BODY_TOO_LONG))->toBeFalse();
})->with('empty-bodies');

it('honours custom limits', function (): void {
    $tight = new OutreachGateLimitsData(maxSubjectChars: 10, maxBodyWords: 5, maxLinks: 0);

    expect(codesOf(perfectMessage(), [], $tight))->toBe([V::SUBJECT_TOO_LONG->value, V::BODY_TOO_LONG->value]);
});

it('reports measured values and limits in the context', function (): void {
    $violation = gate(perfectMessage(['subject' => str_repeat('a', 95)]))->violations[0];

    expect($violation->code)->toBe(V::SUBJECT_TOO_LONG)
        ->and($violation->context)->toBe(['length' => 95, 'max' => 90]);
});

// ------------------------------------------------------------- 8. links

dataset('link-bodies', [
    'no link' => [PERFECT_BODY, false],
    'one https link' => [PERFECT_BODY.' Vedi https://laraxot.example/demo', false],
    'one www link' => [PERFECT_BODY.' Vedi www.laraxot.example', false],
    'two links' => [PERFECT_BODY.' Vedi https://a.example e http://b.example/x', true],
    'https plus www' => [PERFECT_BODY.' Vedi https://a.example e www.b.example', true],
    'same link twice' => [PERFECT_BODY.' https://a.example https://a.example', true],
    'uppercase scheme' => [PERFECT_BODY.' HTTPS://A.example WWW.b.example', true],
    'www inside an https url counts once' => [PERFECT_BODY.' https://www.a.example/p', false],
]);

it('allows at most one link in the body', function (string $body, bool $blocked): void {
    expect(codesOf(perfectMessage(['body' => $body])))->toBe($blocked ? [V::TOO_MANY_LINKS->value] : []);
})->with('link-bodies');

// ------------------------------------------------------- 9. placeholders

dataset('unresolved-placeholders', [
    'double braces' => ['Ciao {{nome}},'],
    'double braces with spaces' => ['Ciao {{ first_name }},'],
    'single braces' => ['Ciao {nome},'],
    'square brackets' => ['Ciao [Nome],'],
    'square brackets with several words' => ['Ciao [nome del ristorante],'],
    'fill marker without closing' => ['Ciao [fill: nome'],
    'fill marker closed' => ['Ciao [FILL: nome]'],
    'todo' => ['TODO scrivere il resto'],
    'todo lowercase' => ['todo: scrivere il resto'],
    'lorem' => ['LOREM ipsum dolor'],
]);

it('blocks unresolved placeholders in the body', function (string $snippet): void {
    $message = perfectMessage(['body' => $snippet."\n".PERFECT_BODY]);

    expect(codesOf($message))->toBe([V::UNRESOLVED_PLACEHOLDER->value]);
})->with('unresolved-placeholders');

it('blocks unresolved placeholders in the subject too', function (): void {
    expect(codesOf(perfectMessage(['subject' => 'Idea per {{azienda}}'])))->toBe([V::UNRESOLVED_PLACEHOLDER->value]);
});

dataset('harmless-brackets', [
    'numbers in braces' => ['Offerta {{1}} e {2}'],
    'numbers in brackets' => ['Vedi nota [1] e [22]'],
    'empty brackets' => ['Valore [] e {} e {{}}'],
    'italian todos is not a marker' => ['Ne parlano todos i giornali'],
    'a word that merely contains lorem' => ['Il dolore di Lorenzo'],
]);

it('does not flag brackets without letters nor words that only contain a marker', function (string $snippet): void {
    expect(gate(perfectMessage(['body' => $snippet."\n".PERFECT_BODY]))->allowed)->toBeTrue();
})->with('harmless-brackets');

it('reports each distinct placeholder once with its text in the context', function (): void {
    $result = gate(perfectMessage(['body' => '{{nome}} {{nome}} [citta] '.PERFECT_BODY]));
    $tokens = array_map(
        static fn (OutreachGateViolationData $v): mixed => $v->context['token'] ?? null,
        $result->violations,
    );

    expect($tokens)->toBe(['{{nome}}', '[citta]']);
});

// ------------------------------------------------------------ 10. HTML

it('blocks a body flagged as HTML', function (): void {
    expect(codesOf(perfectMessage(['isHtml' => true])))->toBe([V::HTML_BODY->value]);
});

dataset('html-snippets', ['<b>offerta</b>', 'riga<br>riga', 'riga<br/>riga', '<a href="https://x.example">qui</a>', '<DIV class="x">ciao</DIV>']);

it('blocks tags inside a plain-text body', function (string $snippet): void {
    $result = gate(perfectMessage(['body' => $snippet."\n".PERFECT_BODY, 'isHtml' => false]));

    expect($result->has(V::HTML_BODY))->toBeTrue();
})->with('html-snippets');

dataset('not-html-snippets', ['<gino@trattoria.example>', 'se 3 < 5 e 7 > 2', 'ti voglio <3', 'a<b']);

it('does not mistake angle brackets in plain text for HTML', function (string $snippet): void {
    expect(gate(perfectMessage(['body' => $snippet."\n".PERFECT_BODY]))->allowed)->toBeTrue();
})->with('not-html-snippets');

// ---------------------------------------------------- 11. sender identity

dataset('sender-fields', ['senderName', 'senderCompany', 'senderPostalAddress']);

it('blocks when a sender field is empty or blank', function (string $field): void {
    foreach (['', '   '] as $blank) {
        $result = gate(perfectMessage([$field => $blank]));
        $fields = array_map(
            static fn (OutreachGateViolationData $v): mixed => $v->context['field'] ?? null,
            array_values(array_filter(
                $result->violations,
                static fn (OutreachGateViolationData $v): bool => $v->code === V::SENDER_IDENTITY_MISSING,
            )),
        );

        expect($result->allowed)->toBeFalse()
            ->and($fields)->toBe([$field]);
    }
})->with('sender-fields');

it('blocks when neither the sender name nor the company appears in the body', function (): void {
    $body = "Ciao Gino, ti propongo una pagina di prenotazione.\nVia Roma 1, 20100 Milano\nNon desideri ricevere altre email? Rispondi stop.";
    $result = gate(perfectMessage(['body' => $body]));

    expect(codesOf(perfectMessage(['body' => $body])))->toBe([V::SENDER_NOT_IN_BODY->value])
        ->and($result->violations[0]->context)->toBe(['field' => 'name_or_company']);
});

dataset('identity-in-body', [
    'name only' => ["Marco Bianchi\nVia Roma 1, 20100 Milano\nrispondi stop"],
    'company only' => ["Laraxot S.r.l.\nVia Roma 1, 20100 Milano\nrispondi stop"],
    'company, case and trailing dots' => ["LARAXOT S.R.L\nVia Roma 1, 20100 Milano\nrispondi stop"],
    'address without commas' => ["Marco Bianchi\nvia roma 1 20100 MILANO\nrispondi stop"],
]);

it('accepts the name or the company, and tolerates case and punctuation', function (string $body): void {
    expect(gate(perfectMessage(['body' => 'Ciao Gino. '.$body]))->allowed)->toBeTrue();
})->with('identity-in-body');

it('requires the postal address in the body', function (): void {
    $body = "Ciao Gino.\nMarco Bianchi, Laraxot S.r.l.\nNon desideri ricevere altre email? Rispondi stop.";
    $result = gate(perfectMessage(['body' => $body]));

    expect(codesOf(perfectMessage(['body' => $body])))->toBe([V::SENDER_NOT_IN_BODY->value])
        ->and($result->violations[0]->context)->toBe(['field' => 'postal_address']);
});

it('matches sender data across accents and unicode', function (): void {
    $message = perfectMessage([
        'senderName' => 'Zoë Müller',
        'senderCompany' => 'Café Ünico S.p.A.',
        'senderPostalAddress' => 'Via Città 3, 00100 Roma',
        'body' => "Ciao Gino.\nZoe Muller\nvia citta 3 00100 roma\nNon desideri ricevere altre email? Rispondi stop.",
    ]);

    expect(gate($message)->allowed)->toBeTrue();
});

it('does not match a name that is only a fragment of another word', function (): void {
    $message = perfectMessage([
        'senderName' => 'Ada',
        'senderCompany' => 'Xy',
        'body' => "Ciao Gino, la strada e' piena.\nVia Roma 1, 20100 Milano\nNon desideri ricevere altre email? Rispondi stop.",
    ]);

    expect(codesOf($message))->toBe([V::SENDER_NOT_IN_BODY->value]);
});

// ------------------------------------------------------- 12. opt-out line

dataset('opt-out-lines', [
    'IT non desideri ricevere' => ['Se non desideri ricevere altre email, dimmelo.'],
    'IT non vuoi ricevere altre' => ['Non vuoi ricevere altre mail? Basta una riga.'],
    'IT non desideri piu ricevere (accent)' => ['Se non desideri più ricevere mie email, scrivimi.'],
    'IT cancellarti' => ['Se preferisci, posso cancellarti dalla lista.'],
    'IT opporti' => ['Puoi opporti in ogni momento al trattamento.'],
    'IT rispondi stop' => ['Per non ricevere altro, rispondi STOP.'],
    'IT rispondi con stop' => ['Rispondi con stop e basta.'],
    'EN unsubscribe' => ['You can unsubscribe at any time.'],
    'EN opt out' => ['Reply if you want to opt out.'],
    'EN opt-out' => ['This is a one-off message with a simple opt-out.'],
    'EN do not want to receive' => ["If you don't want to receive more emails, tell me."],
    'EN no longer wish' => ['If you no longer wish to receive my emails, just say so.'],
    'upper case and accents' => ['NON DESIDERI PIÙ RICEVERE MIE EMAIL?'],
    'typographic apostrophe' => ['If you don’t want to receive more emails, tell me.'],
]);

it('recognises an opt-out invitation in Italian and English', function (string $line): void {
    $body = "Ciao Gino. Ti propongo una pagina di prenotazione.\nMarco Bianchi\nLaraxot S.r.l., Via Roma 1, 20100 Milano\n".$line;

    expect(gate(perfectMessage(['body' => $body]))->allowed)->toBeTrue();
})->with('opt-out-lines');

it('blocks a body with no opt-out invitation', function (): void {
    $body = "Ciao Gino. Ti propongo una pagina di prenotazione.\nMarco Bianchi\nLaraxot S.r.l., Via Roma 1, 20100 Milano\nA presto.";

    expect(codesOf(perfectMessage(['body' => $body])))->toBe([V::MISSING_OPT_OUT_LINE->value]);
});

it('does not take a lone word like stop or ricevere as an opt-out', function (): void {
    $body = "Ciao Gino. Fermati: devo ricevere una risposta, non e' uno stop.\nMarco Bianchi\nLaraxot S.r.l., Via Roma 1, 20100 Milano";

    expect(codesOf(perfectMessage(['body' => $body])))->toBe([V::MISSING_OPT_OUT_LINE->value]);
});

// ------------------------------------- 13. AI-slop, long dashes, exclamation

dataset('banned-phrases', [
    'IT hope you are well' => ['Spero che questa email ti trovi bene.'],
    'IT hope you are well, mail, case and accents' => ['SPERO CHE QUESTA MAIL TI TROVI BENE.'],
    'IT hope you are well, e-mail' => ['Spero che questa e-mail ti trovi bene.'],
    'IT spero che tu stia bene' => ['Spero che tu stia bene.'],
    'IT mi permetto' => ['Mi permetto di contattarti per una proposta.'],
    'IT non esitare' => ['Non esitare a contattarmi.'],
    'IT avanguardia' => ["Una soluzione all'avanguardia per te."],
    'IT avanguardia, typographic apostrophe' => ['Una soluzione all’avanguardia per te.'],
    'EN finds you well' => ['I hope this email finds you well.'],
    'EN finds you well, message' => ['I hope this message finds you well.'],
    'EN doing well' => ["I hope you're doing well."],
    'EN reach out' => ['I wanted to reach out about your site.'],
    'EN do not hesitate' => ["Don't hesitate to reach out."],
    'spread over two lines' => ["I hope this email\nfinds you well."],
]);

it('blocks stock AI and cold-mail phrases', function (string $phrase): void {
    $result = gate(perfectMessage(['body' => $phrase."\n".PERFECT_BODY]));

    expect($result->has(V::BANNED_PHRASE))->toBeTrue()
        ->and($result->allowed)->toBeFalse();
})->with('banned-phrases');

it('also looks for banned phrases in the subject', function (): void {
    expect(codesOf(perfectMessage(['subject' => 'I hope this email finds you well'])))->toBe([V::BANNED_PHRASE->value]);
});

it('does not flag ordinary wording', function (): void {
    $body = "Ciao Gino, spero di non disturbarti. Posso contattarti la settimana prossima?\n".PERFECT_BODY;

    expect(gate(perfectMessage(['body' => $body]))->allowed)->toBeTrue();
});

dataset('long-dashes', [['em dash —'], ['en dash –'], ['spaced — dash']]);

it('blocks em and en dashes', function (string $snippet): void {
    expect(codesOf(perfectMessage(['body' => $snippet."\n".PERFECT_BODY])))->toBe([V::LONG_DASH->value]);
})->with('long-dashes');

it('accepts the plain hyphen', function (): void {
    expect(gate(perfectMessage(['body' => "Ciao Gino - e' un test, anti-spam.\n".PERFECT_BODY]))->allowed)->toBeTrue();
});

it('allows one exclamation mark and blocks several', function (): void {
    expect(gate(perfectMessage(['body' => "Ciao Gino!\n".PERFECT_BODY]))->allowed)->toBeTrue()
        ->and(codesOf(perfectMessage(['body' => "Ciao Gino!!\n".PERFECT_BODY])))->toBe([V::MULTIPLE_EXCLAMATION_MARKS->value])
        ->and(codesOf(perfectMessage(['body' => "Ciao! Gino!\n".PERFECT_BODY])))->toBe([V::MULTIPLE_EXCLAMATION_MARKS->value])
        ->and(codesOf(perfectMessage(['subject' => 'Ciao! Gino!'])))->toBe([V::MULTIPLE_EXCLAMATION_MARKS->value])
        ->and(codesOf(perfectMessage(['body' => "Ciao Gino！！\n".PERFECT_BODY])))->toBe([V::MULTIPLE_EXCLAMATION_MARKS->value]);
});

// ------------------------------------------------ everything at once, edges

it('collects every violation instead of stopping at the first', function (): void {
    $words = implode(' ', array_fill(0, 160, 'parola'));
    $message = new OutreachMessageData(
        recipientEmail: 'NOT-AN-EMAIL',
        prospectEmail: 'gino@trattoria.example',
        subject: str_repeat('Oggetto! ', 20),
        body: "I hope this email finds you well — {{nome}}!! <b>Offerta</b> https://a.example www.b.example TODO\n".$words,
        senderName: '',
        senderCompany: 'Azienda Misteriosa',
        senderPostalAddress: 'Via Inesistente 9',
        emailStatus: 'guess',
        subjectType: 'person',
        lawfulBasis: null,
        approvedByUserId: null,
        isHtml: true,
    );

    $result = gate($message, ['not-an-email']);
    $found = array_map(static fn (OutreachGateViolationData $v): string => $v->code->value, $result->violations);

    $unreachableTogether = [V::SUBJECT_EMPTY, V::BODY_EMPTY];
    $expected = array_map(
        static fn (V $case): string => $case->value,
        array_values(array_filter(V::cases(), static fn (V $case): bool => ! in_array($case, $unreachableTogether, true))),
    );

    expect($result->allowed)->toBeFalse()
        ->and(array_values(array_unique($found)))->toEqualCanonicalizing($expected);
});

it('reports empty subject and body together with everything else', function (): void {
    $result = gate(perfectMessage(['subject' => '', 'body' => '']));

    expect($result->allowed)->toBeFalse()
        ->and($result->has(V::SUBJECT_EMPTY))->toBeTrue()
        ->and($result->has(V::BODY_EMPTY))->toBeTrue()
        ->and($result->has(V::MISSING_OPT_OUT_LINE))->toBeTrue()
        ->and($result->has(V::SENDER_NOT_IN_BODY))->toBeTrue();
});

it('survives a message where every field is empty', function (): void {
    $message = new OutreachMessageData(
        recipientEmail: '',
        prospectEmail: '',
        subject: '',
        body: '',
        senderName: '',
        senderCompany: '',
        senderPostalAddress: '',
        emailStatus: '',
        subjectType: '',
        lawfulBasis: null,
        approvedByUserId: null,
        isHtml: false,
    );

    $result = gate($message, ['', '@']);

    expect($result->allowed)->toBeFalse()
        ->and($result->has(V::RECIPIENT_INVALID))->toBeTrue()
        ->and($result->has(V::RECIPIENT_SUPPRESSED))->toBeFalse()
        ->and($result->has(V::SENDER_IDENTITY_MISSING))->toBeTrue();
});

it('does not fail open on invalid UTF-8 and keeps checking the rest', function (): void {
    $result = gate(perfectMessage(['body' => "\xC3\x28 {{nome}}\n".PERFECT_BODY, 'subject' => "Ciao \xB1"]));

    expect($result->allowed)->toBeFalse()
        ->and($result->has(V::UNRESOLVED_PLACEHOLDER))->toBeTrue();
});

test('result helpers build and query violations', function (): void {
    $clean = OutreachGateResultData::fromViolations([]);
    $blocked = OutreachGateResultData::fromViolations([new OutreachGateViolationData(V::HTML_BODY, ['reason' => 'tags'])]);

    expect($clean->allowed)->toBeTrue()
        ->and($clean->has(V::HTML_BODY))->toBeFalse()
        ->and($blocked->allowed)->toBeFalse()
        ->and($blocked->has(V::HTML_BODY))->toBeTrue()
        ->and($blocked->has(V::BODY_EMPTY))->toBeFalse();
});

test('every violation code is a stable snake_case string', function (): void {
    foreach (V::cases() as $case) {
        expect($case->value)->toMatch('/^[a-z]+(_[a-z]+)*$/');
    }
});
