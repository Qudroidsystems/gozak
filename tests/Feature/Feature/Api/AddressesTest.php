<?php

use App\Models\User;
use App\Models\Address;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('returns list of user addresses', function () {
    Address::factory()->count(3)->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson('/api/addresses');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'addresses' => [
                '*' => [
                    'id',
                    'name',
                    'street',
                    'city',
                    'state',
                    'postal_code',
                    'country',
                    'phone_number',
                    'is_default',
                    'created_at',
                    'updated_at',
                ],
            ],
            'message',
        ])
        ->assertJsonCount(3, 'addresses')
        ->assertJsonPath('success', true);
});

it('creates a new address and sets it as default', function () {
    $payload = [
        'name'         => 'Office',
        'street'       => '42 Broad Street',
        'city'         => 'Ikeja',
        'state'        => 'Lagos',
        'postal_code'  => '101233',
        'country'      => 'Nigeria',
        'phone_number' => '+2348123456789',
        'is_default'   => true,
    ];

    $response = $this->postJson('/api/addresses', $payload);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Address created successfully')
        ->assertJsonPath('address.street', '42 Broad Street')
        ->assertJsonPath('address.is_default', true);

    $this->assertDatabaseHas('addresses', [
        'user_id'      => $this->user->id,
        'street'       => '42 Broad Street',
        'is_default'   => true,
    ]);
});

it('prevents creating address with invalid postal code', function () {
    $response = $this->postJson('/api/addresses', [
        'street'       => 'Invalid',
        'city'         => 'Lagos',
        'state'        => 'Lagos',
        'postal_code'  => 'abc123',          // invalid format
        'country'      => 'Nigeria',
        'phone_number' => '+2348123456789',
    ]);

    $response->assertUnprocessable()
             ->assertJsonValidationErrors('postal_code');
});

it('updates an existing address', function () {
    $address = Address::factory()->create([
        'user_id' => $this->user->id,
        'is_default' => false,
    ]);

    $response = $this->putJson("/api/addresses/{$address->id}", [
        'street'     => 'New Street 99',
        'is_default' => true,
    ]);

    $response->assertOk()
             ->assertJsonPath('success', true)
             ->assertJsonPath('address.street', 'New Street 99')
             ->assertJsonPath('address.is_default', true);
});

it('deletes an address', function () {
    $address = Address::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->deleteJson("/api/addresses/{$address->id}")
         ->assertOk()
         ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
});

it('forbids unauthenticated access', function () {
    Sanctum::actingAs(null); // remove auth

    $this->getJson('/api/addresses')
         ->assertUnauthorized();
});
