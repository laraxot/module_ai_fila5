<?php

declare(strict_types=1);

namespace Modules\AI\Filament\Resources\AiProspectResource\Pages;

use Modules\AI\Filament\Resources\AiProspectResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseCreateRecord;

class CreateAiProspect extends XotBaseCreateRecord
{
    protected static string $resource = AiProspectResource::class;

    protected function getRedirectUrl(): string
    {
        /** @var string */
        return $this->getResource()::getUrl('index');
    }
}
