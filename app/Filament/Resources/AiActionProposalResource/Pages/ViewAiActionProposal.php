<?php

declare(strict_types=1);

namespace Modules\AI\Filament\Resources\AiActionProposalResource\Pages;

<<<<<<< HEAD
use Filament\Schemas\Components\Component;
=======
>>>>>>> laraxot/dev
use Modules\AI\Filament\Resources\AiActionProposalResource;
use Modules\AI\Filament\Resources\AiActionProposalResource\Schemas\AiActionProposalInfolist;
use Modules\Xot\Filament\Resources\Pages\XotBaseViewRecord;

class ViewAiActionProposal extends XotBaseViewRecord
{
    protected static string $resource = AiActionProposalResource::class;


    /**
     * @return array<string, \Filament\Schemas\Components\Component>
     */
<<<<<<< HEAD
    #[\Override]
    protected function getInfolistSchema(): array
=======
>>>>>>> laraxot/dev
    {
        return app(AiActionProposalInfolist::class)->getInfolistSchema();
    }
}
