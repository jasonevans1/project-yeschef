<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use ReCaptcha\ReCaptcha;

class RecaptchaService
{
    public function verify(string $token, string $action = 'submit'): bool
    {
        // Skip in testing environment
        if (app()->environment('testing')) {
            return true;
        }

        // Skip if disabled or not configured
        if (! config('recaptcha.enabled', true) || empty(config('recaptcha.api_secret_key'))) {
            return true;
        }

        // Skip if IP is in skip list
        if (in_array(request()->ip(), config('recaptcha.skip_ip', []))) {
            return true;
        }

        try {
            $recaptcha = new ReCaptcha(config('recaptcha.api_secret_key'));
            $response = $recaptcha->verify($token, request()->ip());

            if (! $response->isSuccess()) {
                Log::warning('reCAPTCHA failed', [
                    'action' => $action,
                    'ip' => request()->ip(),
                    'errors' => $response->getErrorCodes(),
                ]);

                return false;
            }

            // For v3, check score threshold
            if (config('recaptcha.version') === 'v3') {
                $score = $response->getScore();
                $threshold = config('recaptcha.score_threshold', 0.5);

                Log::info('reCAPTCHA verified', [
                    'action' => $action,
                    'score' => $score,
                    'threshold' => $threshold,
                    'ip' => request()->ip(),
                ]);

                return $score >= $threshold;
            }

            return true;
        } catch (\Exception $e) {
            // Fail open: Log error but allow user (prevents blocking due to Google downtime)
            Log::error('reCAPTCHA verification failed', [
                'error' => $e->getMessage(),
                'action' => $action,
                'ip' => request()->ip(),
            ]);

            return true; // Fail open for better UX
        }
    }
}
