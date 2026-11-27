<?php

declare(strict_types=1);

namespace Modules\AI\Filament\Resources\AiProspectResource\Pages;

use Modules\AI\Filament\Resources\AiProspectResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseListRecords;

class ListAiProspects extends XotBaseListRecords
{
    protected static string $resource = AiProspectResource::class;
}
