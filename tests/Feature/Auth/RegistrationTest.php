<?php

use App\Livewire\Auth\Register;
use App\Services\RecaptchaService;
use Livewire\Livewire;

beforeEach(function () {
    // Mock reCAPTCHA service to always return true in tests
    $this->mock(RecaptchaService::class)
        ->shouldReceive('verify')
        ->andReturn(true);
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('recaptcha_token', 'test-token')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('registration fails without recaptcha token', function () {
    // Enable reCAPTCHA for this test
    config(['recaptcha.enabled' => true]);
    config(['recaptcha.api_site_key' => 'test-site-key']);

    $response = Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response->assertHasErrors(['recaptcha_token']);
});

test('registration fails with invalid recaptcha', function () {
    // Enable reCAPTCHA for this test
    config(['recaptcha.enabled' => true]);
    config(['recaptcha.api_site_key' => 'test-site-key']);

    // Override mock for this specific test
    $this->mock(RecaptchaService::class)
        ->shouldReceive('verify')
        ->andReturn(false);

    $response = Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('recaptcha_token', 'invalid-token')
        ->call('register');

    $response->assertHasErrors(['recaptcha_token']);
});
