<?php

return [
    'ollama_base_url'    => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
    'openrouter_api_key' => env('AI_OPENROUTER_API_KEY', ''),
    'default_provider'   => env('AI_DEFAULT_PROVIDER', 'local'),
    'default_model'      => env('AI_DEFAULT_MODEL', 'llama3.1:8b'),
];
