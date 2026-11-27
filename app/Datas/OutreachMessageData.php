<?php

declare(strict_types=1);

namespace Modules\AI\Datas;

use Spatie\LaravelData\Data;

/**
 * Messaggio outreach pronto per l'invio, cosi' come lo vede il gate (solo scalari, nessun Carbon).
 *
 * `emailStatus`: verified|risky|guess|invalid.
 * `subjectType`: company|sole_proprietor|professional|person|unknown.
 */
class OutreachMessageData extends Data
{
    public function __construct(
        public string $recipientEmail,
        public string $prospectEmail,
        public string $subject,
        public string $body,
        public string $senderName,
        public string $senderCompany,
        public string $senderPostalAddress,
        public string $emailStatus,
        public string $subjectType,
        public ?string $lawfulBasis = null,
        public ?string $approvedByUserId = null,
        public bool $isHtml = false,
    ) {}
}
