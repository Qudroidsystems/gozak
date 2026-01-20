<?php

use App\Models\Brand;
use App\Models\Category;

beforeEach(function () {
    $this->actingAsAdmin(); // helper if you have admin guard / role
});

it('lists brands with pagination and products count', function () {
    Brand::factory()->count(15)->create();

    $response = $this->getJson('/api/brands?per_page=5');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id', 'name', 'logo', 'is_featured',
                    'products_count', 'categories',
                ],
            ],
            'pagination' => [
                'current_page', 'last_page', 'total',
            ],
        ])
        ->assertJsonCount(5, 'data');
});

it('creates a brand with logo and categories', function () {
    $category = Category::factory()->create();

    $response = $this->postJson('/api/brands', [
        'name'       => 'Adidas Originals',
        'is_featured' => true,
        'categories' => [$category->id],
    ])->withFile('logo', $this->createFakeImage('logo.jpg'));

    $response->assertCreated()
             ->assertJsonPath('success', true)
             ->assertJsonPath('data.name', 'Adidas Originals')
             ->assertJsonPath('data.categories.0.id', $category->id);
});
