<?php

namespace Tests\Feature\Api;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_contacts(): void
    {
        // Arrange: Create 5 contacts
        Contact::factory()->count(5)->create();

        // Act: Make GET request
        $response = $this->getJson('/api/contacts');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(5, 'data');
    }

    public function test_can_create_contact(): void
    {
        // Arrange
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
        ];

        // Act
        $response = $this->postJson('/api/contacts', $contactData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.email', 'john@example.com')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('contacts', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
        ]);
    }

    public function test_create_contact_defaults_to_active_status(): void
    {
        // Arrange: Don't provide status
        $contactData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ];

        // Act
        $response = $this->postJson('/api/contacts', $contactData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('contacts', [
            'email' => 'jane@example.com',
            'status' => 'active',
        ]);
    }

    public function test_cannot_create_contact_with_duplicate_email(): void
    {
        // Arrange: Create existing contact
        Contact::factory()->create(['email' => 'duplicate@example.com']);

        // Act: Try to create another with same email
        $response = $this->postJson('/api/contacts', [
            'name' => 'Another User',
            'email' => 'duplicate@example.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_create_contact_requires_name_and_email(): void
    {
        // Act
        $response = $this->postJson('/api/contacts', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_can_unsubscribe_contact(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['status' => 'active']);

        // Act
        $response = $this->postJson("/api/contacts/{$contact->id}/unsubscribe");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'unsubscribed')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'status' => 'unsubscribed',
        ]);
    }

    public function test_contact_list_is_paginated(): void
    {
        // Arrange: Create 20 contacts (more than default page size)
        Contact::factory()->count(20)->create();

        // Act
        $response = $this->getJson('/api/contacts');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertEquals(15, count($response->json('data')));
        $this->assertEquals(20, $response->json('meta.total'));
    }
}
