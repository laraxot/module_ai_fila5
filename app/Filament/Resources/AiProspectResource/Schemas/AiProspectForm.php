<?php

declare(strict_types=1);

namespace Modules\AI\Filament\Resources\AiProspectResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Modules\AI\Enums\AiProspectSignalSourceEnum;
use Modules\AI\Enums\AiProspectStatusEnum;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceForm;

/**
 * Form dedicato di AiProspectResource.
 *
 * Nessun ->label(): le etichette arrivano da `ai::prospect.fields.*.label`
 * per convenzione (vedi docs/wiki/rules/no-filament-labels.md). Le opzioni delle
 * Select arrivano da AiProspectStatusEnum / AiProspectSignalSourceEnum
 * (HasLabel, chiavi lang dell'enum).
 */
class AiProspectForm extends XotBaseResourceForm
{
    /**
     * @return array<string, Component>
     */
    public function getFormSchema(): array
    {
        return [
            'section' => Section::make()
                ->schema([
                    'name' => TextInput::make('name')
                        ->required(),

                    'domain' => TextInput::make('domain')
                        ->required(),

                    'website' => TextInput::make('website'),

                    'source' => Select::make('source')
                        ->options(AiProspectSignalSourceEnum::class),

                    'source_url' => TextInput::make('source_url'),

                    'source_at' => DateTimePicker::make('source_at'),

                    'city' => TextInput::make('city'),

                    'region' => TextInput::make('region'),

                    'country_code' => TextInput::make('country_code'),

                    'industry' => TextInput::make('industry'),

                    'phone' => TextInput::make('phone'),

                    'summary' => Textarea::make('summary'),

                    'status' => Select::make('status')
                        ->options(AiProspectStatusEnum::class)
                        ->required(),
                ])
                ->columns(2),
        ];
    }
}
