<?php

declare(strict_types=1);

use Modules\AI\Actions\Prospect\ApplyProspectSuppressionAction;
use Modules\AI\Actions\Prospect\DraftProspectOutreachAction;
use Modules\AI\Actions\Prospect\NormalizeProspectImportAction;
use Modules\AI\Data\Prospect\ProspectLeadData;

test('normalize dedupes by identity', function (): void {
    $rows = [
        ['name' => 'Acme', 'website' => 'https://acme.example', 'email' => 'a@acme.example'],
        ['name' => 'Acme', 'website' => 'https://acme.example', 'email' => 'a@acme.example'],
        ['name' => '', 'website' => 'https://empty.example'],
    ];
    $leads = app(NormalizeProspectImportAction::class)->execute($rows);

    expect($leads)->toHaveCount(1)
        ->and($leads[0]->name)->toBe('Acme');
});

test('suppression blocks draft', function (): void {
    $lead = new ProspectLeadData(name: 'Acme', email: 'no@acme.example');
    expect(app(ApplyProspectSuppressionAction::class)->isSuppressed($lead, ['no@acme.example']))->toBeTrue();

    expect(fn () => app(DraftProspectOutreachAction::class)->execute($lead, [], null, ['no@acme.example']))
        ->toThrow(InvalidArgumentException::class);
});

test('draft requires approval and opt-out', function (): void {
    $lead = new ProspectLeadData(
        name: 'Acme',
        contactName: 'Ada Lovelace',
        email: 'ada@acme.example',
        signalKeys: ['no-booking'],
    );
    $draft = app(DraftProspectOutreachAction::class)->execute($lead, [
        'name' => 'Bob',
        'company' => 'Laraxot',
        'address' => 'Via Example 1',
        'offer' => 'ops software',
    ]);

    expect($draft)->toMatchArray(['requires_approval' => true])
        ->and($draft['body'])->toContain('no thanks')
        ->and($draft['missing'])->toBeEmpty();
});
