<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Provider
    |--------------------------------------------------------------------------
    |
    | Supported: "meta", "twilio", "360dialog"
    |
    */

    'provider' => env('WHATSAPP_PROVIDER', 'meta'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Verification Token
    |--------------------------------------------------------------------------
    |
    | Used to verify webhook requests from WhatsApp
    |
    */

    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Meta (Facebook) Configuration
    |--------------------------------------------------------------------------
    */

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'app_secret' => env('WHATSAPP_APP_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Twilio Configuration
    |--------------------------------------------------------------------------
    */

    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    'from_number' => env('TWILIO_WHATSAPP_FROM'),

    /*
    |--------------------------------------------------------------------------
    | 360Dialog Configuration
    |--------------------------------------------------------------------------
    */

    'api_key' => env('360DIALOG_API_KEY'),
    'api_url' => env('360DIALOG_API_URL', 'https://waba.360dialog.io/v1'),

];
