<?php

declare(strict_types=1);

namespace Modules\AI\Enums;

/**
 * Codici stabili delle violazioni del gate di invio outreach.
 *
 * Il gate non produce testo per l'utente: restituisce questi codici (piu' un `context`) e la
 * traduzione e' compito dell'UI. I valori sono parte del contratto: non rinominarli.
 */
enum OutreachGateViolationEnum: string
{
    case RECIPIENT_SUPPRESSED = 'recipient_suppressed';
    case RECIPIENT_INVALID = 'recipient_invalid';
    case RECIPIENT_MISMATCH = 'recipient_mismatch';
    case EMAIL_NOT_DELIVERABLE = 'email_not_deliverable';
    case MISSING_HUMAN_APPROVAL = 'missing_human_approval';
    case MISSING_LAWFUL_BASIS = 'missing_lawful_basis';
    case SUBJECT_REQUIRES_CONSENT = 'subject_requires_consent';
    case SUBJECT_EMPTY = 'subject_empty';
    case SUBJECT_TOO_LONG = 'subject_too_long';
    case BODY_EMPTY = 'body_empty';
    case BODY_TOO_LONG = 'body_too_long';
    case TOO_MANY_LINKS = 'too_many_links';
    case UNRESOLVED_PLACEHOLDER = 'unresolved_placeholder';
    case HTML_BODY = 'html_body';
    case SENDER_IDENTITY_MISSING = 'sender_identity_missing';
    case SENDER_NOT_IN_BODY = 'sender_not_in_body';
    case MISSING_OPT_OUT_LINE = 'missing_opt_out_line';
    case BANNED_PHRASE = 'banned_phrase';
    case LONG_DASH = 'long_dash';
    case MULTIPLE_EXCLAMATION_MARKS = 'multiple_exclamation_marks';
}
