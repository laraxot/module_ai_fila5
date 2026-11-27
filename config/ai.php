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
    'model' => config('services.openai.model', 'gpt-3.5-turbo-instruct'),

    /*
    |--------------------------------------------------------------------------
    | Chat Model
    |--------------------------------------------------------------------------
    |
    | The default model to use for chat completions.
    */
    'chat_model' => config('services.openai.chat_model', 'gpt-3.5-turbo'),

    /*
    |--------------------------------------------------------------------------
    | Temperature
    |--------------------------------------------------------------------------
    |
    | Controls randomness in outputs. Lower values are more deterministic.
    | Range: 0.0 - 2.0
    */
    'temperature' => config('services.openai.temperature', 0.7),

    /*
    |--------------------------------------------------------------------------
    | Max Tokens
    |--------------------------------------------------------------------------
    |
    | Maximum number of tokens to generate in the completion.
    */
    'max_tokens' => config('services.openai.max_tokens', 1500),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limiting API calls.
    */
    'rate_limit' => [
        'max_predictions_per_request' => is_numeric(config('services.openai.max_predictions', 100))
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
    'logging' => config('services.openai.logging', true),

    /*
    |--------------------------------------------------------------------------
    | Assistant (base di conoscenza + passaggio a una persona)
    |--------------------------------------------------------------------------
    |
    | Il modulo AI e' agnostico: le parole che fanno passare subito la domanda
    | a una persona le decide il modulo del progetto che usa l'assistente
    | (es. ['rimborso', 'reclamo', 'avvocato']). Vuoto = nessun filtro
    | deterministico, restano le regole del prompt.
    */
    'assistant' => [
        'handoff_keywords' => [],
        'max_sources' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Business prospecting (default OFF, no email transport)
    |--------------------------------------------------------------------------
    |
    | Must be enabled explicitly after the project has chosen its ICP, legal
    | basis, data-source license and allowed workspaces. No automatic outreach.
    */
    'prospecting' => [
        'enabled' => (bool) env('AI_PROSPECTING_ENABLED', false),
        'signal_weights' => [
            'manual_booking_only' => 35,
            'manual_order_only' => 30,
            'catalogue_link' => 10,
        ],
        // Lettura sicura dei siti e ricerca su OpenStreetMap (docs/prospecting.md, #33).
        // Regole OSM: max 1 richiesta/s, User-Agent con un contatto reale, cache, attribuzione, interruttore (enabled).
        'user_agent' => env('AI_PROSPECTING_USER_AGENT', 'LaraxotProspecting/1.0 (+'.env('APP_URL', 'http://localhost').')'),
        'nominatim_url' => env('AI_PROSPECTING_NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
        'overpass_url' => env('AI_PROSPECTING_OVERPASS_URL', 'https://overpass-api.de/api/interpreter'),
        // Categorie OSM `amenity` accettate dall'import: le decide l'host (qui il default del portale ristoranti).
        'osm_categories' => ['restaurant', 'cafe', 'fast_food', 'bar', 'pub'],
        'site_timeout' => 8,
        'max_bytes' => 524_288,
    ],

    /*
    |--------------------------------------------------------------------------
    | Public-page website signals (offline analyzeHtml)
    |--------------------------------------------------------------------------
    |
    | Overrides WebsiteSignalCatalog::defaults() weights. Set a key to 0 to
    | disable that gap detector. Remote execute() stays fail-closed.
    */
    'prospect' => [
        'signals' => [
            'weights' => [
                // 'not-mobile' => 4,
                // 'no-order-online' => 5,
                // 'no-chat' => 0,
            ],
            'slow_seconds' => 4.0,
        ],
        'draft' => [
            'send_permitted' => false,
            'daily_cap_hint' => 50,
        ],
    ],

];
