<?php

namespace App\Livewire\Auth;

use App\Rules\RecaptchaRule;
use App\Services\RecaptchaService;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class ForgotPassword extends Component
{
    public string $email = '';

    public string $recaptcha_token = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $rules = [
            'email' => ['required', 'string', 'email'],
        ];

        // Only require reCAPTCHA token if reCAPTCHA is enabled and configured
        if (config('recaptcha.enabled') && ! empty(config('recaptcha.api_site_key'))) {
            $rules['recaptcha_token'] = ['required', new RecaptchaRule(app(RecaptchaService::class), 'forgot_password')];
        }

        $this->validate($rules);

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('A reset link will be sent if the account exists.'));
    }
}
