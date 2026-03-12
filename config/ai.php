<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI Model
    |--------------------------------------------------------------------------
    |
    | The default model to use for completions.
    */
    'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo-instruct'),

    /*
    |--------------------------------------------------------------------------
    | Chat Model
    |--------------------------------------------------------------------------
    |
    | The default model to use for chat completions.
    */
    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-3.5-turbo'),

    /*
    |--------------------------------------------------------------------------
    | Temperature
    |--------------------------------------------------------------------------
    |
    | Controls randomness in outputs. Lower values are more deterministic.
    | Range: 0.0 - 2.0
    */
    'temperature' => env('OPENAI_TEMPERATURE', 0.7),

    /*
    |--------------------------------------------------------------------------
    | Max Tokens
    |--------------------------------------------------------------------------
    |
    | Maximum number of tokens to generate in the completion.
    */
    'max_tokens' => env('OPENAI_MAX_TOKENS', 1500),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limiting API calls.
    */
    'rate_limit' => [
        'max_predictions_per_request' => (int) env('AI_MAX_PREDICTIONS', 100),
        'delay_between_calls_ms' => (int) env('AI_DELAY_MS', 1000),
        'timeout_seconds' => (int) env('AI_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging for AI operations.
    */
    'logging' => env('AI_LOGGING', true),

];
