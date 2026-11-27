<?php

declare(strict_types=1);

use Modules\AI\Actions\Prospect\DraftProspectOutreachAction;
use Modules\AI\Data\Prospect\ProspectLeadData;

test('draft requires approval and includes opt-out', function (): void {
    $lead = new ProspectLeadData(
        name: 'Trattoria Demo',
        website: 'https://example.com',
        contactName: 'Marco Rossi',
        email: 'info@example.com',
        signalKeys: ['no-qr-menu'],
    );

    $draft = app(DraftProspectOutreachAction::class)->execute($lead, [
        'name' => 'Anna',
        'company' => 'Festionali',
        'address' => 'Via Roma 1, Milano',
        'offer' => 'ordini QR omnichannel',
    ]);

    expect($draft)->toMatchArray(['requires_approval' => true])
        ->and($draft['to'])->toBe('info@example.com')
        ->and($draft['body'])->toContain('no grazie')
        ->and($draft['body'])->toContain('Via Roma 1')
        ->and($draft['missing'])->toBeEmpty();
});
