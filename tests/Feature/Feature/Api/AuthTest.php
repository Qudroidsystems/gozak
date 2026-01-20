<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

it('registers a new user and sends verification email event', function () {
    Event::fake([Registered::class]);

    $response = $this->postJson('/api/register', [
        'first_name'    => 'Test',
        'last_name'     => 'User',
        'email'         => 'testuser@example.com',
        'password'      => 'password123',
        'phone_number'  => '+2348123456789',
    ]);

    $response
        ->assertCreated()
        ->assertJsonStructure([
            'success',
            'token',
            'user' => [
                'id', 'first_name', 'last_name', 'email', 'username',
                'phone_number', 'profile_image', 'social_provider',
                'gender', 'date_of_birth', 'email_verified_at',
            ],
            'message',
        ])
        ->assertJsonPath('success', true)
        ->assertJsonPath('user.email', 'testuser@example.com');

    $this->assertDatabaseHas('users', [
        'email' => 'testuser@example.com',
        'username' => 'testuser@example.com',
    ]);

    Event::assertDispatched(Registered::class);
});

it('rejects duplicate email during registration', function () {
    User::factory()->create(['email' => 'duplicate@example.com']);

    $this->postJson('/api/register', [
        'first_name' => 'Duplicate',
        'last_name'  => 'User',
        'email'      => 'duplicate@example.com',
        'password'   => 'password123',
    ])
    ->assertUnprocessable()
    ->assertJsonValidationErrors('email');
});

it('logs in successfully with correct credentials', function () {
    $user = User::factory()->create([
        'email'    => 'login@example.com',
        'password' => Hash::make('correct123'),
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson('/api/login', [
        'email'    => 'login@example.com',
        'password' => 'correct123',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('token')->not()->toBeEmpty();
});

it('rejects login with wrong password', function () {
    $user = User::factory()->create([
        'email'    => 'wrong@example.com',
        'password' => Hash::make('correct123'),
    ]);

    $this->postJson('/api/login', [
        'email'    => 'wrong@example.com',
        'password' => 'wrongpass',
    ])
    ->assertUnauthorized()
    ->assertJsonPath('message', 'Invalid credentials');
});

it('rejects login if email not verified', function () {
    $user = User::factory()->create([
        'email'             => 'unverified@example.com',
        'password'          => Hash::make('password123'),
        'email_verified_at' => null,
    ]);

    $this->postJson('/api/login', [
        'email'    => 'unverified@example.com',
        'password' => 'password123',
    ])
    ->assertForbidden()
    ->assertJsonPath('message', 'Email not verified. Please verify your email to log in.');
});
