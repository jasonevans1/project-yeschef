<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Rules\RecaptchaRule;
use App\Services\RecaptchaService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $recaptcha_token = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ];

        // Only require reCAPTCHA token if reCAPTCHA is enabled and configured
        if (config('recaptcha.enabled') && ! empty(config('recaptcha.api_site_key'))) {
            $rules['recaptcha_token'] = ['required', new RecaptchaRule(app(RecaptchaService::class), 'register')];
        }

        $validated = $this->validate($rules);

        $validated['password'] = Hash::make($validated['password']);

        // Remove recaptcha_token before saving
        unset($validated['recaptcha_token']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        Session::regenerate();

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}
