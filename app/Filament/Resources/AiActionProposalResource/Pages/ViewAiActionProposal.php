<?php

declare(strict_types=1);

namespace Modules\AI\Filament\Resources\AiActionProposalResource\Pages;

use Modules\AI\Filament\Resources\AiActionProposalResource;
use Modules\AI\Filament\Resources\AiActionProposalResource\Schemas\AiActionProposalInfolist;
use Modules\Xot\Filament\Resources\Pages\XotBaseViewRecord;

class ViewAiActionProposal extends XotBaseViewRecord
{
    protected static string $resource = AiActionProposalResource::class;


    /**
     * @return array<string, \Filament\Schemas\Components\Component>
     */
    #[\Override]
    protected function getInfolistSchema(): array
    {
        return app(AiActionProposalInfolist::class)->getInfolistSchema();
    }
}
