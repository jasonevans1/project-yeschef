<?php

use App\Livewire\GroceryLists\Create;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('create view renders successfully', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->assertStatus(200)
        ->assertSee('Create Standalone Grocery List')
        ->assertSee('Standalone Shopping List')
        ->assertSee('Create a shopping list not linked to any meal plan')
        ->assertSee('List Name')
        ->assertSee('Create List')
        ->assertSee('Cancel');
});

test('create view has working form submission', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', 'Test Shopping List')
        ->call('save')
        ->assertRedirect();
});

test('create view shows validation errors', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('create view shows loading state on submit', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Create::class);

    // Check that both states are in the view
    $component->assertSee('Create List')
        ->assertSee('Creating...');
});

test('create view has info box explaining standalone lists', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->assertSee('Perfect for parties, special occasions, or general shopping trips');
});

test('create view cancel button links to index', function () {
    Livewire::actingAs($this->user)
        ->test(Create::class)
        ->assertSee('grocery-lists');
});
