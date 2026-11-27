<?php

declare(strict_types=1);

namespace Modules\AI\Filament\Resources\AiProspectResource\Tables;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Modules\AI\Enums\AiProspectStatusEnum;
use Modules\UI\Filament\Tables\Columns\IDColumn;
use Modules\Xot\Filament\Resources\Tables\XotBaseResourceTable;

class AiProspectsTable extends XotBaseResourceTable
{
    /** @return array<string, Column> */
    public function getTableColumns(): array
    {
        return [
            'id' => IDColumn::make('id')->sortable(),
            'name' => TextColumn::make('name')->searchable()->sortable(),
            'industry' => TextColumn::make('industry')->badge(),
            'city' => TextColumn::make('city')->searchable(),
            'status' => TextColumn::make('status')->badge(),
            'source' => TextColumn::make('source')->badge(),
            'source_at' => TextColumn::make('source_at')->dateTime('d/m/Y H:i')->sortable(),
        ];
    }

    /** @return array<string, SelectFilter> */
    public function getTableFilters(): array
    {
        return [
            'status' => SelectFilter::make('status')
                ->options(AiProspectStatusEnum::class)
                ->multiple(),
        ];
    }
}
