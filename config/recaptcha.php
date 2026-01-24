<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA API Keys
    |--------------------------------------------------------------------------
    |
    | Set the site key and secret key from your Google reCAPTCHA admin panel.
    | Get your keys at: https://www.google.com/recaptcha/admin
    |
    */

    'api_site_key' => env('RECAPTCHA_SITE_KEY', ''),
    'api_secret_key' => env('RECAPTCHA_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Version
    |--------------------------------------------------------------------------
    |
    | The version of reCAPTCHA to use (v2 or v3).
    | v3: Invisible, score-based verification
    | v2: Checkbox or invisible challenge
    |
    */

    'version' => env('RECAPTCHA_VERSION', 'v3'),

    /*
    |--------------------------------------------------------------------------
    | Score Threshold (v3 only)
    |--------------------------------------------------------------------------
    |
    | For reCAPTCHA v3, this is the minimum score required to pass verification.
    | Score ranges from 0.0 (likely a bot) to 1.0 (likely a human).
    | Recommended values:
    | - 0.5: Balanced (default)
    | - 0.7: Stricter
    | - 0.3: More lenient
    |
    */

    'score_threshold' => env('RECAPTCHA_SCORE_THRESHOLD', 0.5),

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable reCAPTCHA verification.
    | Useful for quickly disabling reCAPTCHA without removing keys.
    |
    */

    'enabled' => env('RECAPTCHA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Skip IPs
    |--------------------------------------------------------------------------
    |
    | IP addresses that should skip reCAPTCHA verification.
    | Useful for development or testing environments.
    |
    */

    'skip_ip' => [],
];
