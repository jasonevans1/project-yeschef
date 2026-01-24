<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\RecaptchaService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RecaptchaRule implements ValidationRule
{
    public function __construct(
        protected RecaptchaService $recaptchaService,
        protected string $action = 'submit'
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->recaptchaService->verify($value, $this->action)) {
            $fail('The verification failed. Please try again.');
        }
    }
}
