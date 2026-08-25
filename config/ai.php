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
<<<<<<< HEAD
   'model' => config('services.openai.model', 'gpt-3.5-turbo-instruct'),
=======
    'model' => config('services.openai.model', 'gpt-3.5-turbo-instruct'),
>>>>>>> laraxot/dev

    /*
    |--------------------------------------------------------------------------
    | Chat Model
    |--------------------------------------------------------------------------
    |
    | The default model to use for chat completions.
    */
<<<<<<< HEAD
   'chat_model' => config('services.openai.chat_model', 'gpt-3.5-turbo'),
=======
    'chat_model' => config('services.openai.chat_model', 'gpt-3.5-turbo'),
>>>>>>> laraxot/dev

    /*
    |--------------------------------------------------------------------------
    | Temperature
    |--------------------------------------------------------------------------
    |
    | Controls randomness in outputs. Lower values are more deterministic.
    | Range: 0.0 - 2.0
    */
<<<<<<< HEAD
   'temperature' => config('services.openai.temperature', 0.7),
=======
    'temperature' => config('services.openai.temperature', 0.7),
>>>>>>> laraxot/dev

    /*
    |--------------------------------------------------------------------------
    | Max Tokens
    |--------------------------------------------------------------------------
    |
    | Maximum number of tokens to generate in the completion.
    */
<<<<<<< HEAD
   'max_tokens' => config('services.openai.max_tokens', 1500),
=======
    'max_tokens' => config('services.openai.max_tokens', 1500),
>>>>>>> laraxot/dev

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limiting API calls.
    */
    'rate_limit' => [
<<<<<<< HEAD
       'max_predictions_per_request' => is_numeric(config('services.openai.max_predictions', 100))
=======
        'max_predictions_per_request' => is_numeric(config('services.openai.max_predictions', 100))
>>>>>>> laraxot/dev
            ? (int) config('services.openai.max_predictions', 100)
            : 100,
        'delay_between_calls_ms' => is_numeric(config('services.openai.delay_ms', 1000))
            ? (int) config('services.openai.delay_ms', 1000)
            : 1000,
        'timeout_seconds' => is_numeric(config('services.openai.timeout', 30))
            ? (int) config('services.openai.timeout', 30)
            : 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging for AI operations.
    */
<<<<<<< HEAD
   'logging' => config('services.openai.logging', true),
=======
    'logging' => config('services.openai.logging', true),
>>>>>>> laraxot/dev

];
