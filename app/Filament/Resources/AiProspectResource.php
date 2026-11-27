<?php

declare(strict_types=1);

namespace Modules\AI\Filament\Resources;

use Filament\Widgets\Widget;
use Modules\AI\Filament\Resources\AiProspectResource\Pages;
use Modules\AI\Models\AiProspect;
use Modules\Xot\Filament\Resources\XotBaseResource;

/** Read-only prospect queue; outreach remains an explicit human-approved draft. */
class AiProspectResource extends XotBaseResource
{
    protected static ?string $model = AiProspect::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    /** @return array<class-string<Widget>> */
    public static function getHeaderWidgets(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return array_merge(parent::getPages(), [
            'index' => Pages\ListAiProspects::route('/'),
            'create' => Pages\CreateAiProspect::route('/create'),
            'view' => Pages\ViewAiProspect::route('/{record}'),
            'edit' => Pages\EditAiProspect::route('/{record}/edit'),
        ]);
    }
}
