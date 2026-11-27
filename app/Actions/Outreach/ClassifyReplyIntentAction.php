<?php

declare(strict_types=1);

namespace Modules\AI\Actions\Outreach;

use Modules\AI\Enums\ReplyIntentEnum;
use RuntimeException;
use Spatie\QueueableAction\QueueableAction;

use function Safe\preg_match;
use function Safe\preg_replace;

/**
 * Classifica una risposta a un messaggio di outreach con sole regole deterministiche
 * (italiano e inglese): niente LLM, niente rete, niente DB.
 *
 * Contratto di sicurezza: UNSUBSCRIBE ed ESCALATE sono "bloccati da regola". Un modello
 * futuro puo' solo aggiungere cautela, mai declassarli. In caso di errore del motore
 * regex il risultato e' UNCLEAR (cioe' una persona), mai un'azione automatica.
 *
 * Precedenza (la prima regola che scatta vince):
 *   unsubscribe > escalate > wrong_person (dichiarazione esplicita) > out_of_office
 *   > wrong_person (semplice rinvio "scrivi a ...") > not_interested > interested
 *   > objection > question > unclear
 *
 * Si legge solo cio' che ha scritto la persona: righe citate (">"), intestazioni di
 * citazione ("Il giorno ... ha scritto:", "On ... wrote:", "-----Original Message-----",
 * "Da:/From:") con tutto cio' che segue, disclaimer legali, URL e indirizzi email sono scartati.
 *
 * Il testo viene normalizzato (minuscole, senza accenti, apostrofi tipografici, punteggiatura
 * come separatore di frase " | ") e confrontato con alternative di frasi ancorate ai confini
 * di parola. Le "lacune" tra parole sono limitate e possessive (niente backtracking
 * catastrofico) e non attraversano mai un confine di frase.
 */
final class ClassifyReplyIntentAction
{
    use QueueableAction;

    /**
     * Oltre questa soglia il testo e' troncato.
     * ponytail: una risposta umana non e' mai cosi' lunga, un opt-out oltre 100 KB e' ignorato; alzare la soglia se serve.
     */
    private const int MAX_BYTES = 100_000;

    private const string MONTHS = 'gennaio|febbraio|marzo|aprile|maggio|giugno|luglio|agosto|settembre|ottobre|novembre|dicembre'
        .'|january|february|april|june|july|august|september|october|november|december';

    private const string WEEKDAYS = 'lunedi|martedi|mercoledi|giovedi|venerdi|sabato|domenica'
        .'|monday|tuesday|wednesday|thursday|friday|saturday|sunday';

    /** Rinvii a data lontana: "sentiamoci a gennaio" e' un rinvio, non un si'. */
    private const string DEFER = self::MONTHS.'|prossim[oaie] (?:mese|anno|trimestre)|(?:mese|anno|trimestre) prossim[oaie]'
        .'|dopo l estate|dopo le feste|dopo ferragosto|next month|next year|next quarter|after (?:the )?summer';

    private const array TOKENS = [
        '<g1>' => '(?: \w++){0,1}',
        '<g2>' => '(?: \w++){0,2}',
        '<g3>' => '(?: \w++){0,3}',
        '<g4>' => '(?: \w++){0,4}',
        '<g6>' => '(?: \w++){0,6}',
        '<c>' => '(?: \|)?',
        '<L>' => '(?!(?: \w++){0,3} (?:'.self::DEFER.'))',
        '<M>' => self::MONTHS,
        '<W>' => self::WEEKDAYS,
    ];

    private const array ACCENTS = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n', 'ý' => 'y', 'ÿ' => 'y', 'ß' => 'ss', 'œ' => 'oe', 'æ' => 'ae',
        // Contrazioni inglesi (apostrofo gia' uniformato), poi ogni apostrofo residuo e' uno spazio.
        "can't" => 'cannot', "won't" => 'will not', "shan't" => 'shall not', "let's" => 'let us',
        "n't" => ' not', "i'm" => 'i am', "'re" => ' are', "'ll" => ' will', "'ve" => ' have', "'d" => ' would',
        "'" => ' ',
    ];

    /** Frasi che sembrano minacce ma non lo sono, rimosse prima del controllo escalation. */
    private const array ESCALATION_FALSE_FRIENDS = [
        'sede legale', 'legale rappresentante', 'rappresentante legale', 'ragione sociale', 'forma legale',
        'domicilio legale', 'nome legale', 'legal name', 'legal entity', 'legal address', 'registered office',
        'spam folder', 'spam filter', 'junk folder', 'cartella spam', 'cartella dello spam', 'nello spam',
        'negli spam', 'in spam', 'anti spam', 'antispam', 'privacy policy', 'cookie policy',
        'informativa privacy', 'informativa sulla privacy',
    ];

    private const array UNSUBSCRIBE = [
        // Italiano
        'cancellami', 'cancellatemi', 'cancellarmi', 'cancellateci', 'rimuovimi', 'rimuovetemi', 'rimuovermi',
        'rimuovetici', 'eliminami', 'eliminatemi', 'eliminarmi', 'eliminateci', 'disiscriv\w*', 'disiscrizione',
        'togli\w*<g3> (?:lista|liste|mailing|newsletter|elenco|database|archivio|contatti|indirizzario|anagrafica|dati|email|mail)',
        '(?:rimuov|cancell|elimin)\w*<g3> (?:lista|liste|mailing|newsletter|elenco|database|archivio|contatti|indirizzario'
            .'|anagrafica|dati|email|mail|indirizzo|nominativo|account|iscrizione|numero)',
        '(?:annull|cancell|disd)\w*<g3> iscrizione',
        'non (?:scrivermi|scrivetemi|scrivetici|scriverci|contattarmi|contattatemi|contattateci|contattarci|disturbarmi'
            .'|disturbatemi|importunarmi|importunatemi|mandarmi|mandatemi|mandarci|mandateci|inviarmi|inviatemi|inviarci'
            .'|inviateci|ricontattarmi|ricontattatemi|ricontattarci|ricontattateci|cercarmi|cercatemi)',
        'smett\w+ di (?:scriver|contattar|inviar|mandar|disturbar|importunar)\w*',
        'basta (?:con )?(?:le |queste |questi )?(?:email|mail|messaggi|comunicazioni|spam)',
        'lasciat?(?:emi|eci|ami|aci) in pace',
        'non (?:voglio|vogliamo|desidero|desideriamo|gradisco|gradiamo|intendo|intendiamo)<g2> (?:ricevere|essere contattat\w+'
            .'|essere disturbat\w+|sentire|leggere|altre|altri|ulterior\w+)',
        '(?:diritto|diritti) (?:di |all |alla |dell |d )?(?:opposizione|oblio|cancellazione)',
        'mi oppongo', 'opposizione al trattamento', 'revoc\w+<g3> consenso',
        // Inglese
        'unsubscribe', 'unsub', 'opt out', 'optout', 'opt me out', 'opted out',
        'remove (?:me|us)', 'remove my<g2> (?:name|email|mail|address|details|data|contact|number|info|information)',
        'take (?:me|us) off', 'take my (?:email|name|address)<g2> off',
        'delete (?:me|us)', 'delete my<g2> (?:email|mail|data|details|info|information|name|address|contact|account|number)',
        '(?:do not|never|no longer) (?:contact|email|mail|message|write|text|send|bother|spam)<g2> (?:me|us)',
        'stop (?:emailing|mailing|contacting|messaging|writing|spamming|texting|bothering|harassing|sending|spam)',
        'stop (?:these|the|this|all|any|further)<g2> (?:emails?|mails?|messages?|spam|contacts?|communications?)',
        'no more (?:emails?|mails?|messages?|contacts?|spam|communications?)',
        '(?:do not|no longer) (?:want|wish)<g3> (?:emails?|mails?|messages?|spam|communications?|contact)',
        '(?:do not|no longer) (?:want|wish)<g2> (?:receive|hear|get|be contacted|be emailed)',
        'leave (?:me|us) alone', '(?:withdraw|revoke)<g2> consent', 'cease<g2> (?:contact|communications?|emails?|mailing)',
        'right to (?:be forgotten|erasure|object)',
    ];

    /** Un'intera frase fatta della sola parola di stop ("Stop", "Please stop", "Basta cosi'"). */
    private const string BARE_OPT_OUT = '/^(?:(?:please|pls|per favore|per piacere|ok|no|basta) )?'
        .'(?:stop|unsubscribe|unsub|basta|remove|cancel|quit|end|optout|opt out|rimuovi|cancella|annulla|disiscrivi|stop emails?)'
        .'(?: (?:please|pls|now|ora|subito|immediately|adesso|grazie|thanks|thank you|per favore|per piacere|cosi|la|everything|all|emails?|mail))*$/';

    private const array ESCALATE = [
        // Italiano
        'garante', 'autorita', 'privacy', 'gdpr', 'regolamento (?:ue|europeo)', 'd ?lgs 196', 'protezione dei dati',
        'titolare del trattamento', 'diffid\w*', 'avvocat\w*', 'legal[ei]', 'legalmente', 'illegal[ei]', 'illegalita',
        'illecit\w*', 'denunc\w*', 'querel\w*', 'tribunale', 'giudice', 'carabinieri', 'polizia', 'guardia di finanza',
        'agcom', 'agcm', 'segnalazion[ei]', 'segnaler\w*', 'reclam(?:o|i|are)', 'violazion[ei]', 'violat\w*', 'sanzion\w*',
        'molesti[ae]', 'persecu\w*', 'vessa\w*', 'stalking', 'spam', 'spammer\w*',
        '(?:far\w*|faccio|facciamo)<g2> causa', '(?:mi riservo|ci riserviamo|ci riserveremo)<g4> (?:azion\w+|tutela|diritti|vie)',
        '(?:dove|come|da dove|in che modo)<g3> (?:preso|avuto|ottenuto|trovato|recuperato|ricavato|reperito|saputo)<g4> '
            .'(?:dati|email|mail|indirizzo|contatto|numero|nominativo|recapito)',
        'non<g3> (?:dato|autorizzato|acconsentito|prestato|concesso)<g3> (?:consenso|autorizzazione|permesso)',
        'senza (?:il )?(?:mio |nostro )?(?:consenso|autorizzazione)',
        '(?:diritto|diritti) (?:di |all |alla |dell |d )?(?:accesso|opposizione|oblio|cancellazione|rettifica|limitazione|portabilita)',
        'esercit\w+<g3> diritt\w+',
        // Inglese
        'lawyers?', 'attorney', 'solicitor', 'barrister', 'legal counsel', 'cease and desist', 'lawsuit', 'sue', 'suing',
        'sued', 'in court', 'to court', 'court (?:order|action)', 'illegal', 'illegally', 'unlawful', 'unlawfully', 'legally',
        'legal (?:action|team|department|dept|notice|proceedings|advice|advisor|adviser|steps|means|consequences|ramifications)',
        'harass\w*', 'stalk\w*', 'police', 'authorities', 'regulators?', 'data protection', 'ccpa', 'can spam',
        'unsolicited', 'complain\w*', 'reported? (?:you|your (?:company|business|emails?))',
        'report(?:ing)? this<g2> (?:authorit\w+|police|regulator\w*|ico)', 'breach\w*', 'violat\w*', 'infring\w*',
        '(?:where|how) did you<g3> (?:get|find|obtain|source|acquire)<g3> (?:email|mail|data|details|info|information|address|number|contact|name)',
        'personal data', 'subject access', 'data subject', 'right to (?:erasure|object|access|be forgotten)',
        '(?:exercise|exercising)<g3> rights?', 'formal (?:complaint|notice)',
    ];

    private const array WRONG_PERSON_EXPLICIT = [
        'non sono<g2> persona (?:giusta|corretta|adatta|indicata|competente|di riferimento|incaricata|preposta|responsabile)',
        'non sono<g3> (?:responsabile|referente|il contatto|competente|incaricat\w+)',
        'non (?:mi |ci |ne )?occup(?:o|iamo)', 'non e di (?:mia|nostra) competenza', 'non mi riguarda',
        'non rientra<g3> (?:competenz\w+|ambito|mansion\w+)', 'non e il mio (?:ambito|settore|campo|ruolo|compito|reparto)',
        '(?:ha|hanno) lasciato (?:l azienda|la societa|la ditta|l ufficio|il lavoro)',
        '(?:ha|hanno) cambiato (?:azienda|lavoro|ruolo|societa)',
        'non (?:lavora|lavorano)<g2> (?:piu|qui|presso|con|per)', 'non fa piu parte', 'non e piu (?:in|presso|con)',
        'wrong (?:person|contact|address|email|number|department|company)',
        'not the (?:right|correct|best) (?:person|contact|one)', 'not really the right person',
        'i am not<g3> (?:responsible|in charge|the decision maker|the owner|the contact|involved|handling|dealing)',
        'not responsible', 'not my (?:department|area|role|job|responsibility|field|team|remit)',
        '(?:i|we) do not (?:handle|deal with|manage|oversee|look after)',
        'no longer (?:works?|with|at|employed|here|part)', '(?:has|have) left (?:the|our|this) (?:company|organi[sz]ation|business|firm)',
        'left the company', 'you have the wrong', 'this is not my',
    ];

    private const array WRONG_PERSON_REDIRECT = [
        'scriv(?:i|ete|ere) (?:a|al|alla|all|ai|agli|alle|direttamente)', 'contatta(?:te)?', 'rivolgiti', 'rivolgetevi', 'rivolgersi',
        'consiglio di (?:contattare|scrivere|rivolgervi|rivolgerti|sentire|parlare)', 'la persona giusta (?:e|sarebbe)',
        '(?:il|la) (?:referente|responsabile|contatto)<g3> e (?:il|la|lo|l|sig|signor\w*|signora|dott\w*|ing|avv|dr)',
        'ti metto in contatto', 'vi metto in contatto', 'in copia', 'in cc', 'ti presento', 'vi presento',
        '(?:giro|giriamo|inoltro|inoltriamo|passo|passiamo|ho girato|ho inoltrato|ho passato)<g6> '
            .'(?:collega|responsabile|ufficio|titolare|direttore|socio|capo|referente|reparto|persona giusta)',
        'please (?:contact|reach out to|email|write to|speak (?:to|with)|talk to|get in touch with)', 'reach out to',
        'you (?:should|might|may|could|can)<g1> (?:contact|speak|talk|email|write|reach|try|ask)',
        'try (?:contacting|reaching|emailing|asking|speaking|writing)', 'the right person (?:is|would be)', 'best person (?:is|to)',
        '(?:forwarded|forwarding|redirected|redirecting)<g6> to', '(?:passed|passing|handed|handing)<g6> (?:to|on to|over to)',
        'looping in', 'cc d',
    ];

    private const array OUT_OF_OFFICE = [
        // Italiano
        'fuori ufficio', 'fuori sede', 'fuori citta', 'in ferie', 'in vacanza', 'in congedo', 'in permesso', 'in malattia',
        'in maternita', 'in paternita', '(?:sono|saro|siamo|saremo|resto|rimarro) assent[ei]', 'assente (?:dal|dall|fino|da|per|sino)',
        'risposte? automatich[ae]', 'messaggio automatico', 'messaggio generato automaticamente', 'email automatica',
        'mail automatica', 'risposta di cortesia', 'chiusura (?:estiva|natalizia|per ferie|aziendale|pasquale)',
        'chiuso per ferie', 'ufficio chiuso', 'siamo chiusi', 'resteremo chiusi', 'riapr(?:iamo|ira|ono)',
        'non sono (?:in ufficio|raggiungibile|reperibile)', 'non saro (?:in ufficio|raggiungibile|reperibile|disponibile)',
        'rientr(?:o|ero|iamo|eremo)<g2> (?:il|<W>|dal|entro|tra|fra|a|dopo|in ufficio|settimana|domani|presto)',
        'torner(?:o|emo)<g2> (?:il|<W>|dal|entro|tra|fra|a|dopo|in ufficio|settimana|domani|presto)',
        'mio rientro', 'al rientro', 'risponder\w+<g3> (?:rientro|ritorno)', 'accesso limitato<g2> (?:posta|email|mail)',
        // Inglese
        'out of (?:the )?office', 'ooo', 'auto ?reply', 'auto ?responder', 'automatic (?:reply|response)',
        'automated (?:reply|response|message|email|mail)', 'this is an automated',
        '(?:annual|sick|maternity|paternity|parental|medical|extended|bereavement|sabbatical|study) leave', 'on leave',
        'on vacation', 'on holiday', 'on pto', 'away from (?:my |the )?(?:desk|office|email|computer|keyboard)',
        '(?:currently|presently|temporarily) away', 'i am (?:now )?away',
        'limited (?:access|connectivity)<g3> (?:email|mail)', 'back in (?:the )?office', 'back at (?:my )?(?:desk|work)',
        'back (?:on|by|after|from)(?: the| next| this)? (?:\d+\w*|<W>|<M>|week|month|christmas|new year)',
        'return(?:ing)?(?: to (?:the )?office)? (?:on|by|after|from)(?: the| next| this)? (?:\d+\w*|<W>|<M>|week|month)',
        '(?:upon|on|after) my return', 'respond<g3> return', '(?:mailbox|inbox) is (?:not monitored|unmonitored)',
        'not monitored', 'office (?:is )?closed', 'closed (?:for|until)',
    ];

    private const array NOT_INTERESTED = [
        // Italiano
        'non<g3> interessa', 'non<g3> interessat[oaie]', 'non<g2> interessant[ei]',
        'non (?:ci|mi|ne|lo|la) (?:serve|servono|occorre|occorrono|necessita|necessitano|abbisogna)',
        'non<g3> (?:bisogno|necessita|interesse)', 'nessun (?:interesse|bisogno)', 'nessuna necessita',
        'non fa (?:per (?:noi|me)|al caso (?:nostro|mio)|per niente)', 'non e per (?:noi|me)', 'non e (?:quello|cio) che cerchiamo',
        'non (?:cerchiamo|cerco|stiamo cercando|siamo alla ricerca)',
        'no<c> grazie', 'no gracias', 'grazie<c> no\b(?! problem| worries| rush| hurry)', 'grazie ma no',
        'direi (?:proprio )?di no', 'ma anche no', '(?:assolutamente|decisamente|proprio|certo che) no', 'per niente interessat\w*',
        'declin(?:o|iamo)', 'rifiut(?:o|iamo|a)', 'siamo a posto', 'sono a posto', 'lasciat?e?(?:mi)? perdere', 'lasciamo perdere',
        '(?:se|qualora)<g4> interessat[oaie]',
        // Inglese
        '(?:not|never)<g3> interested', 'not<g2> (?:interesting|relevant|useful)', 'no (?:interest|thanks|thank you)',
        'no need<c> (?:thanks|thank you)', 'no need for (?:this|that|it|your)', '(?:we|i) do not need', '(?:we|i) have no need',
        '(?:we|i)(?: will| would| shall)? pass\b', 'pass on (?:this|that|it|your|the)', 'not for (?:us|me)',
        'not a (?:good |great |right )?fit', 'not the right fit', 'not relevant', 'not looking', 'not in the market',
        '(?:we|i) decline', 'all set', 'we are good(?: thanks| thank you)',
        'if (?:we|i|anything|that|this|you)<g4> interested',
    ];

    /** Messaggio fatto solo di un rifiuto secco. */
    private const string WHOLE_NOT_INTERESTED = '/^(?:no|nope|nah|nein|niet)$/';

    private const array INTERESTED = [
        // Italiano
        'mi interessa(?:no)?', 'ci interessa(?:no)?', 'interessat[oaie]', 'interessant[ei]', 'sentiamoci<L>', 'ne parliamo<L>',
        'parliamone<L>', 'chiamami', 'chiamatemi', 'chiamaci', 'chiamateci', 'mi (?:chiami|chiamate|richiami|richiamate)',
        'richiamami', 'richiamatemi', 'telefonami', 'telefonatemi', 'mi (?:telefoni|telefonate)', 'chiama(?:te)? pure',
        'contattami', 'contattatemi', 'mi (?:contatti|contattate)', 'fissiamo', 'fissare', 'incontriamoci', 'vediamoci',
        'organizziamo', 'prenotiamo', 'prendiamo(?: pure)? (?:un|il)', 'facciamo (?:una|un)',
        'possiamo (?:parlarne|sentirci|vederci|fare|organizzare|fissare|incontrarci|parlare)', 'ne possiamo parlare',
        '(?:mand|invi)\w*<g4> (?:informazioni|info|dettagli|maggiori|ulteriori|presentazione|preventivo|offerta|listino|brochure'
            .'|proposta|materiale|documentazione|demo|prezzi|tariffe|catalogo)',
        '(?:mi|ci) piacerebbe<g3> (?:sapere|saperne|conoscere|capire|vedere|parlare|approfondire|provare|sentire|avere|ricevere)',
        '(?:vorrei|vorremmo)<g2> (?:sapere|saperne|conoscere|capire|vedere|parlare|approfondire|provare|maggiori|ulteriori'
            .'|un appuntamento|fissare|prenotare|ricevere|avere|una demo)',
        'saperne di piu', 'approfondire', 'dimmi (?:pure|di piu|tutto)', 'raccontami', 'suona bene',
        'mi sembra (?:interessante|una buona idea|utile|buona)', '(?:buona|ottima|bella) idea', 'volentieri', 'con piacere',
        'quando (?:siete|sei|sarete|sarai|siamo|possiamo|potete|puoi|potresti)<g1> (?:disponibil\w*|liber\w*|sentirci|vederci|parlare|chiamare|chiamarmi|incontrarci)',
        '(?:sono|siamo) disponibil[ei]<g1> (?:a|per|ad)<g2> (?:chiamata|call|incontro|appuntamento|demo|videocall|parlare|sentirci)',
        'saremmo lieti', 'ci farebbe piacere',
        // Inglese
        'interested', 'interesting', 'intrigued', 'curious', 'sounds (?:good|great|interesting|promising|perfect|fine)',
        'looks (?:good|great|interesting|promising)', 'seems (?:interesting|good|useful|relevant)', '(?:good|great) idea',
        '(?:would )?love to\b', 'like to (?:learn|know|hear|see|talk|discuss|schedule|book|set up|try|chat|meet|speak|find out|understand)',
        'happy to (?:talk|chat|connect|speak|meet|learn|hear|discuss|jump|schedule|have|take|hop)', 'tell me more', 'more (?:info|information|details)',
        '(?:send|share|forward)<g3> (?:info|information|details|pricing|prices|quote|brochure|deck|proposal|presentation|catalogue'
            .'|catalog|samples?|examples?|case studies)',
        '(?:let us|lets) (?:talk|chat|connect|speak|schedule|set up|jump|catch up|discuss|meet|book|get|have|hop)<L>',
        'call me', 'give me a (?:call|ring)', 'ring me',
        '(?:book|set up|schedule) (?:a|the|me)<g1> (?:call|meeting|demo|slot|time)',
        '(?:can|could) we (?:talk|chat|speak|meet|schedule|jump|hop|set up|book|discuss)',
        'when (?:are you|can we|could we|can you|could you|would you)<g1> (?:free|available|talk|chat|meet|speak|call)',
        'demo', 'open to (?:a|an|the|talking|chatting|discussing|hearing|learning|it)', 'worth a (?:chat|call|conversation|look|try|shot)',
    ];

    /** Messaggio fatto solo di un si' secco. */
    private const string WHOLE_INTERESTED = '/^(?:si|yes|yep|yeah|sure|certo|certamente|volentieri|assolutamente si|absolutely|definitely|of course|please do)'
        .'(?: \|)?(?: (?:please|grazie|thanks|thank you|volentieri|certo|sure|certamente|per favore))?$/';

    private const array OBJECTION = [
        // Italiano
        'troppo (?:caro|cara|costos\w+)', '(?:e|sono|sarebbe|mi sembra|ci sembra)<g1> (?:caro|cara|costos\w+)', 'costan?o troppo',
        'prezzi? (?:alto|alti|elevato|elevati|troppo)', 'fuori (?:dal |dai )?(?:nostro |mio |nostri )?budget', 'non (?:abbiamo|ho|c e)<g1> budget',
        'senza budget', 'budget (?:limitato|ridotto|esaurito|bloccato|chiuso)', '(?:poco|niente|zero) budget',
        'non rientra nel (?:nostro |mio )?budget', 'non (?:possiamo|posso) permetter\w*',
        '(?:usiamo|utilizziamo) (?:gia|un|una|il|lo|la)', 'abbiamo gia', 'ho gia', 'gia (?:usiamo|utilizziamo|abbiamo|ce l abbiamo|ho)',
        'ce l abbiamo gia', 'ci appoggiamo a', 'siamo gia (?:coperti|serviti|con|a posto)', 'siamo (?:coperti|serviti)', 'siamo soddisfatti',
        'fornitore attuale', 'gia sotto contratto', 'contratto in corso', 'siamo (?:vincolati|legati)',
        'non e (?:il momento|il periodo|un buon momento|una priorita|prioritario)', 'non (?:adesso|ora)', 'non in questo momento',
        '(?:al|per il) momento no', 'per ora no', '(?:adesso|ora) no', 'momento sbagliato', 'brutto momento',
        'siamo (?:molto )?(?:impegnat\w+|presi|pieni)', 'non (?:abbiamo|ho) (?:il )?tempo', 'poco tempo', 'manca il tempo',
        'magari (?:piu avanti|dopo|tra|fra|in futuro|l anno|a)', 'piu avanti', 'in futuro', 'in seguito',
        'forse (?:piu avanti|in futuro|l anno prossimo|tra|fra|dopo)', '(?:l )?anno prossimo', '(?:il )?prossimo (?:anno|trimestre|mese|quarter)',
        'tra qualche (?:mese|settimana)', 'fra qualche (?:mese|settimana)', '(?:tra|fra) (?:un|due|tre|quattro|sei) mesi',
        'dopo l estate', 'dopo le (?:feste|vacanze)', 'dopo (?:ferragosto|pasqua|natale)', 'in (?:autunno|primavera)', 'a fine anno',
        'a inizio anno',
        '(?:ci sentiamo|ci risentiamo|sentiamoci|risentiamoci|ci vediamo|ci aggiorniamo|ne parliamo|ne riparliamo|parliamone|riparliamone'
            .'|ricontatt\w+|richiam\w+|riscriv\w+|risent\w+|ripar\w+|aggiorniamoci)<g4> (?:<M>|prossim[oaie]|dopo|tra|fra)',
        '(?:<M>)<g3> (?:ci sentiamo|ne parliamo|riparliamo|ricontatt\w+|richiam\w+|riscriv\w+|risent\w+)',
        'ne parlo\b', 'devo (?:sentire|chiedere|verificare|valutare|consultare|pensarci)', 'dobbiamo (?:valutare|pensarci)',
        'ci (?:devo|dobbiamo) pensare', 'ci pensiamo', 'ci penso', 'valuteremo', '(?:vi|ti) (?:faremo|faro|faccio) sapere', 'faremo sapere',
        'terremo (?:presente|in considerazione)', 'teniamo (?:il vostro contatto|presente)', '(?:vi|ti) ricontatteremo',
        '(?:mi sembra|e) (?:troppo )?(?:complicat\w+|difficile|complesso)', 'troppo (?:complicat\w+|complesso|difficile)',
        'non (?:ci |mi )?convince', 'non siamo convinti', 'non sono convinto', 'dubbi', 'perpless\w+',
        // Inglese
        'too (?:expensive|costly|pricey|pricy|much|high)', '(?:is|seems|looks|sounds) (?:expensive|costly|pricey)',
        '(?:can not|cannot) afford', '(?:do not|did not) have the budget', 'no budget', '(?:out of|over|beyond) (?:our |my )?budget',
        'budget (?:is )?(?:tight|limited|frozen|closed|exhausted|constrained|cut)', '(?:low|limited|little) budget',
        'price is (?:too )?(?:high|steep)', 'not (?:in|within) (?:our |the )?budget',
        'not (?:now|yet|right now|at the moment|at this time|this (?:quarter|year|month)|a priority|the right time|a good time|the time)',
        '(?:bad|wrong) (?:timing|time)', 'not our priority', '(?:maybe|perhaps|possibly) (?:later|next|in the future|down the road|sometime|another)',
        'later (?:this|next|in)', '(?:next|the following) (?:quarter|year|month)', 'in the future', 'down the road',
        'at a later (?:date|time|stage)', 'some other time', 'another time', 'circle back',
        'touch base (?:later|next|in|after)',
        '(?:come|get|reach|check|talk|speak|write|call|ping|follow up|reconnect)<g3> (?:later|again|next|after|back)',
        'already (?:use|using|have|work|working|got|has|signed|under|with|covered|partner|happy|set|in place)',
        'we are (?:currently )?(?:using|working with)', 'we have (?:an?|our|the) (?:existing|current|in house|internal|own)',
        'happy with (?:our|my|the) (?:current|existing)', 'under contract', 'locked in', 'in house',
        '(?:existing|current) (?:provider|vendor|supplier|solution|tool|system)', '(?:went|going|gone) with (?:someone|another|a different)',
        'need to (?:think|discuss|talk|check|consult|run|speak|ask|review|evaluate|look)',
        '(?:talk|speak|check|discuss)<g1> (?:with|to)<g2> (?:my|our|the) (?:boss|partner|team|manager|colleagues?|cto|ceo|director|board|wife|husband|cofounder|supervisor)',
        'let me (?:think|discuss|check|talk|run)', 'we will (?:think|consider|discuss|evaluate|review|let you know|get back)',
        'will let you know', '(?:keep|have) (?:you|your (?:details|info|contact|number|email))<g2> (?:on file|in mind)',
        'too busy', '(?:very|really|super) busy', 'swamped', 'no time', '(?:do not|did not) have (?:the )?time', 'bandwidth', 'not ready',
        'not sure (?:if|we)', '(?:need|want) (?:more )?time', '(?:still|currently) evaluating',
    ];

    private const string QUESTION_START = '/^(?:come|quanto|quanta|quanti|quante|cosa|che cosa|dove|chi|perche|quando|quale|quali|in che modo|come mai'
        .'|how|what|who|when|where|why|which|do you|does it|does this|is it|is there|are you|are there|can you|could you)\b/';

    /**
     * @return ReplyIntentEnum UNSUBSCRIBE ed ESCALATE non vanno mai declassati da un modello.
     */
    public function execute(string $replyText): ReplyIntentEnum
    {
        try {
            return $this->classify($replyText);
        } catch (RuntimeException) {
            // Il motore regex si e' arreso: nessuna azione automatica, lo guarda una persona.
            return ReplyIntentEnum::UNCLEAR;
        }
    }

    /**
     * La risposta contiene una minaccia/richiesta legale o privacy, anche se l'intento
     * vincente e' un altro (tipicamente UNSUBSCRIBE: "non scrivetemi piu' o chiamo l'avvocato").
     * Chi riceve UNSUBSCRIBE deve controllare anche questo e, se vero, passare il caso a una persona.
     */
    public function hasEscalationSignal(string $replyText): bool
    {
        try {
            $normalized = $this->normalize($this->ownText($replyText));

            return $normalized !== '' && $this->isEscalation($normalized);
        } catch (RuntimeException) {
            return true; // Non so valutare: meglio che lo veda una persona.
        }
    }

    private function classify(string $replyText): ReplyIntentEnum
    {
        $own = $this->ownText($replyText);
        $norm = $this->normalize($own);

        if ($norm === '') {
            return ReplyIntentEnum::UNCLEAR;
        }

        return match (true) {
            $this->isUnsubscribe($norm) => ReplyIntentEnum::UNSUBSCRIBE,
            $this->isEscalation($norm) => ReplyIntentEnum::ESCALATE,
            $this->hasMatch($this->anyOf(self::WRONG_PERSON_EXPLICIT), $norm) => ReplyIntentEnum::WRONG_PERSON,
            $this->hasMatch($this->anyOf(self::OUT_OF_OFFICE), $norm) => ReplyIntentEnum::OUT_OF_OFFICE,
            $this->hasMatch($this->anyOf(self::WRONG_PERSON_REDIRECT), $norm) => ReplyIntentEnum::WRONG_PERSON,
            $this->isNotInterested($norm) => ReplyIntentEnum::NOT_INTERESTED,
            $this->isInterested($norm) => ReplyIntentEnum::INTERESTED,
            $this->hasMatch($this->anyOf(self::OBJECTION), $norm) => ReplyIntentEnum::OBJECTION,
            $this->isQuestion($own, $norm) => ReplyIntentEnum::QUESTION,
            default => ReplyIntentEnum::UNCLEAR,
        };
    }

    private function isUnsubscribe(string $norm): bool
    {
        if ($this->hasMatch($this->anyOf(self::UNSUBSCRIBE), $norm)) {
            return true;
        }

        foreach (explode(' | ', $norm) as $clause) {
            if ($this->hasMatch(self::BARE_OPT_OUT, $clause)) {
                return true;
            }
        }

        return false;
    }

    private function isEscalation(string $norm): bool
    {
        $cleaned = $this->replace('/\b(?:'.implode('|', self::ESCALATION_FALSE_FRIENDS).')\b/', ' ', $norm);

        return $this->hasMatch($this->anyOf(self::ESCALATE), $cleaned);
    }

    private function isNotInterested(string $norm): bool
    {
        return $this->hasMatch(self::WHOLE_NOT_INTERESTED, $norm)
            || $this->hasMatch($this->anyOf(self::NOT_INTERESTED), $norm);
    }

    private function isInterested(string $norm): bool
    {
        return $this->hasMatch(self::WHOLE_INTERESTED, $norm)
            || $this->hasMatch($this->anyOf(self::INTERESTED), $norm);
    }

    private function isQuestion(string $own, string $norm): bool
    {
        return str_contains($own, '?')
            || str_contains($own, '？')
            || $this->hasMatch(self::QUESTION_START, $norm);
    }

    /**
     * Solo cio' che ha scritto la persona: niente citazioni, disclaimer, URL, indirizzi.
     */
    private function ownText(string $raw): string
    {
        $text = mb_scrub(substr($raw, 0, self::MAX_BYTES));

        if (str_contains($text, '<') || str_contains($text, '&')) {
            $text = $this->htmlToText($text);
        }

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $text));
        $kept = [];

        foreach ($lines as $index => $line) {
            if ($this->startsQuotedBlock($lines, $index)) {
                break;
            }

            if (! str_starts_with(ltrim($line), '>')) {
                $kept[] = $line;
            }
        }

        return $this->replace(
            ['~(?:https?://|www\.)\S{1,500}~iu', '~[\w.+-]{1,64}@[\w-]{1,63}(?:\.[\w-]{1,63}){1,5}~u'],
            ' ',
            implode("\n", $kept),
        );
    }

    private function htmlToText(string $text): string
    {
        $text = $this->replace('~</?(?:br|p|div|blockquote|li|tr|table|ul|ol|h[1-6])\b[^<>]{0,300}>~i', "\n", $text);
        // I tag sono limitati e ben formati: un "<" sciolto resta testo (nessuna perdita di parole).
        $text = $this->replace('~</?[a-z][a-z0-9]{0,15}(?:\s[^<>]{0,300})?/?>~i', ' ', $text);

        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * La riga $index apre un blocco citato, un inoltro o un disclaimer? Da qui in poi non e' piu' la persona.
     *
     * @param  list<string>  $lines
     */
    private function startsQuotedBlock(array $lines, int $index): bool
    {
        $line = substr(ltrim($lines[$index]), 0, 300);

        if ($line === '') {
            return false;
        }

        if ($this->hasMatch('/^(?:-{2,}\s*(?:original message|messaggio originale|forwarded message|messaggio inoltrato)|_{8,}|begin forwarded message)/i', $line)) {
            return true;
        }

        if ($this->hasMatch('/^(?:il|on|le|am)\b/i', $line)) {
            // L'intestazione "... ha scritto:" puo' andare a capo fino a due volte.
            $window = $line.' '.substr(ltrim($lines[$index + 1] ?? ''), 0, 300).' '.substr(ltrim($lines[$index + 2] ?? ''), 0, 300);

            return $this->hasMatch('/\b(?:ha scritto|wrote|schrieb)\s*:/i', $window);
        }

        if ($this->hasMatch('/^(?:da|from|de|von)\s*:/i', $line)) {
            return $this->isMailHeaderBlock($lines, $index, $line);
        }

        return $this->hasMatch(
            '/^(?:ai sensi (?:del|dell|dei|degli)|informativa (?:sulla |ai sensi )?(?:privacy|trattamento)|disclaimer|avviso di riservatezza'
            .'|nota di riservatezza|confidentiality notice|il presente messaggio (?:e|ed) (?:i|gli) suoi allegati'
            .'|le informazioni (?:contenute|trasmesse) in (?:questo|questa)|this (?:e-?mail|message) (?:and (?:any|its) (?:attachments|files)'
            .'|is (?:confidential|intended|private)|may contain|contains confidential)|the information (?:contained|transmitted)'
            .'|if you are not the intended recipient|if you have received this (?:e-?mail|message) in error)/i',
            $line,
        );
    }

    /**
     * "Da:/From:" apre una citazione solo se sembra un'intestazione: contiene un indirizzo
     * oppure e' seguita da campi tipo Inviato/Sent/A/To/Oggetto/Subject.
     *
     * @param  list<string>  $lines
     */
    private function isMailHeaderBlock(array $lines, int $index, string $line): bool
    {
        if (str_contains($line, '@')) {
            return true;
        }

        for ($next = $index + 1; $next <= $index + 4 && isset($lines[$next]); $next++) {
            if ($this->hasMatch('/^\s*(?:inviato|sent|date|data|to|a|cc|oggetto|subject|an|betreff)\s*:/i', substr($lines[$next], 0, 100))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Minuscole, senza accenti, apostrofi uniformati; tutto cio' che non e' [a-z0-9] diventa spazio e
     * la punteggiatura di fine frase (o una riga vuota) diventa il separatore " | ".
     */
    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = $this->replace('/[\x{200B}-\x{200D}\x{2060}\x{FEFF}\x{00AD}]/u', '', $text);
        $text = $this->replace('/[\x{2018}\x{2019}\x{02BC}\x{2032}`\x{00B4}]/u', "'", $text);
        $text = strtr($text, self::ACCENTS);
        $text = $this->replace('/\n(?:[ \t]*\n)+/', ' | ', $text);
        $text = $this->replace('/[,;:.!?()\[\]{}\x{2026}\x{3002}\x{FF0C}\x{FF01}\x{FF1F}]+/u', ' | ', $text);
        $text = $this->replace('/[^a-z0-9|]+/', ' ', $text);

        return trim($this->replace('/(?: ?\|)+ ?/', ' | ', $text), ' |');
    }

    /**
     * @param  list<string>  $fragments
     */
    private function anyOf(array $fragments): string
    {
        return '/\b(?:'.strtr(implode('|', $fragments), self::TOKENS).')\b/';
    }

    private function hasMatch(string $pattern, string $subject): bool
    {
        return preg_match(strtr($pattern, self::TOKENS), $subject) === 1;
    }

    /**
     * @param  string|list<string>  $pattern
     */
    private function replace(string|array $pattern, string $replacement, string $subject): string
    {
        return preg_replace($pattern, $replacement, $subject);
    }
}
