<?php

declare(strict_types=1);

namespace Modules\AI\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Modules\AI\Datas\AIConfigData;
use Modules\Xot\Filament\Pages\BaseSettingPage;

/**
 * Impostazioni di AI (`config/{tenant}/ai.php`), solo super-admin: modelli, temperature, limiti e
 * rete delle richieste OpenAI. La chiave API non e' un campo: resta in `.env`.
 *
 * @see AIConfigData
 */
class SettingPage extends BaseSettingPage
{
    /**
     * @return array<string, TextInput>
     */
    protected function getFormSchema(): array
    {
        return [
            'model' => TextInput::make('model')
                ->required()
                ->maxLength(100),
            'chat_model' => TextInput::make('chat_model')
                ->required()
                ->maxLength(100),
            'temperature' => TextInput::make('temperature')
                ->numeric()
                ->minValue(0)
                ->maxValue(2)
                ->step(0.1)
                ->required(),
            'chat_temperature' => TextInput::make('chat_temperature')
                ->numeric()
                ->minValue(0)
                ->maxValue(2)
                ->step(0.1)
                ->required(),
            'max_tokens' => TextInput::make('max_tokens')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(32768)
                ->required(),
            'timeout' => TextInput::make('timeout')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(300)
                ->required(),
            'retry_attempts' => TextInput::make('retry_attempts')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(10)
                ->required(),
            // Senza barra finale: MakeAIRequestAction aggiunge `/chat/completions`.
            'openai_base_url' => TextInput::make('openai_base_url')
                ->url()
                ->required()
                ->maxLength(255)
                ->dehydrateStateUsing(static fn (mixed $state): string => rtrim(is_string($state) ? $state : '', '/')),
        ];
    }
}
