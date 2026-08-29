<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Max Context Tokens
    |--------------------------------------------------------------------------
    |
    | Fallback limit when no provider-specific or model-specific limit is set.
    | Estimated at ~2 chars per token for Vietnamese/mixed content.
    |
    */

    'max_context_tokens' => (int) env('CHATBOT_MAX_CONTEXT_TOKENS', 4000),

    /*
    |--------------------------------------------------------------------------
    | Provider & Model Token Limits
    |--------------------------------------------------------------------------
    |
    | Override the default limit per provider or per provider+model.
    | Model-specific limits take priority over provider-only limits,
    | which take priority over the global default.
    |
    | Keys must match the provider names in config/ai.php.
    | Model keys should match the model ID sent to the API.
    |
    */

    'context_limits' => [

        'anthropic' => [
            'max_tokens' => 200_000,
            'models' => [
                'claude-sonnet-4-20250514' => 200_000,
                'claude-3-5-haiku-20241022' => 200_000,
                'claude-3-opus-20240229' => 200_000,
            ],
        ],

        'openai' => [
            'max_tokens' => 128_000,
            'models' => [
                'gpt-4o' => 128_000,
                'gpt-4o-mini' => 128_000,
                'gpt-4-turbo' => 128_000,
                'gpt-4' => 8_192,
                'gpt-3.5-turbo' => 16_385,
                'o1' => 200_000,
                'o1-mini' => 128_000,
            ],
        ],

        'gemini' => [
            'max_tokens' => 1_000_000,
            'models' => [
                'gemini-2.0-flash' => 1_000_000,
                'gemini-1.5-pro' => 2_000_000,
                'gemini-1.5-flash' => 1_000_000,
            ],
        ],

        'deepseek' => [
            'max_tokens' => 64_000,
            'models' => [
                'deepseek-chat' => 64_000,
                'deepseek-reasoner' => 64_000,
            ],
        ],

        'groq' => [
            'max_tokens' => 128_000,
            'models' => [
                'llama-3.3-70b-versatile' => 128_000,
                'llama-3.1-8b-instant' => 128_000,
                'mixtral-8x7b-32768' => 32_768,
                'gemma2-9b-it' => 8_192,
            ],
        ],

        'mistral' => [
            'max_tokens' => 128_000,
            'models' => [
                'mistral-large-latest' => 128_000,
                'mistral-small-latest' => 128_000,
                'open-mixtral-8x22b' => 65_536,
            ],
        ],

        'zai' => [
            'max_tokens' => 128_000,
            'models' => [
                'GLM-4.5-flash' => 128_000,
                'GLM-4.5' => 128_000,
                'GLM-4-Plus' => 128_000,
            ],
        ],

        'ollama' => [
            'max_tokens' => 32_768,
            'models' => [
                'llama3.1' => 128_000,
                'llama3.2' => 128_000,
                'qwen2.5' => 128_000,
                'deepseek-r1' => 64_000,
                'phi3' => 128_000,
                'gemma2' => 8_192,
            ],
        ],

        'openrouter' => [
            'max_tokens' => 128_000,
        ],

        'xai' => [
            'max_tokens' => 128_000,
            'models' => [
                'grok-2' => 128_000,
                'grok-2-mini' => 128_000,
            ],
        ],

        'azure' => [
            'max_tokens' => 128_000,
        ],

    ],

];
