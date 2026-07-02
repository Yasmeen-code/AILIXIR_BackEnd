<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */
    // AI agent
    'chemistry' => [
        'base_url' => env('CHEMISTRY_API_BASE_URL', 'https://shdwrow-ailixir-chat-bot.hf.space/'),
        'timeout' => env('CHEMISTRY_API_TIMEOUT', 60),
    ],

    'chemical_ai' => [
        'url' => env('CHEMICAL_AI_URL', 'http://chemical-rag:5000'),
    ],

    'admet' => [
        'url' => env('ADMET_AI_URL', 'http://admet:8000'),
    ],

    'drug_repurposing' => [
        'url' => env('DRUG_REPURPOSING_URL', 'http://drug-repurposing:8000'),
        'token' => env('DRUG_REPURPOSING_TOKEN'),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'md_simulation' => [
        'url' => env('MD_SIMULATION_URL', 'http://protein-ligand-md:5005'),
        'timeout' => env('MD_SIMULATION_TIMEOUT', 3600),
    ],

    'generation' => [
        'url' => env('GENERATION_SERVICE_URL', 'http://generation:8000'),
    ],
    'ai' => [
        'url' => env('AI_SERVICE_URL'),
        'jwt_secret' => env('JWT_SECRET'),
    ],

];
