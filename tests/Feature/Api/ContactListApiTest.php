<?php

namespace Tests\Feature\Api;

use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactListApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_contact_lists(): void
    {
        // Arrange
        ContactList::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/contact-lists');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'contacts_count',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_contact_list(): void
    {
        // Arrange
        $listData = [
            'name' => 'Newsletter Subscribers',
            'description' => 'Monthly newsletter recipients',
        ];

        // Act
        $response = $this->postJson('/api/contact-lists', $listData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.name', 'Newsletter Subscribers')
            ->assertJsonPath('data.description', 'Monthly newsletter recipients');

        $this->assertDatabaseHas('contact_lists', [
            'name' => 'Newsletter Subscribers',
            'description' => 'Monthly newsletter recipients',
        ]);
    }

    public function test_can_create_contact_list_without_description(): void
    {
        // Arrange
        $listData = [
            'name' => 'VIP Customers',
        ];

        // Act
        $response = $this->postJson('/api/contact-lists', $listData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'VIP Customers');

        $this->assertDatabaseHas('contact_lists', [
            'name' => 'VIP Customers',
        ]);
    }

    public function test_create_contact_list_requires_name(): void
    {
        // Act
        $response = $this->postJson('/api/contact-lists', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_add_contact_to_list(): void
    {
        // Arrange
        $contactList = ContactList::factory()->create();
        $contact = Contact::factory()->create();

        // Act
        $response = $this->postJson("/api/contact-lists/{$contactList->id}/contacts", [
            'contact_id' => $contact->id,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'contacts',
                ],
            ]);

        $this->assertDatabaseHas('contact_contact_list', [
            'contact_id' => $contact->id,
            'contact_list_id' => $contactList->id,
        ]);
    }

    public function test_adding_same_contact_twice_doesnt_duplicate(): void
    {
        // Arrange
        $contactList = ContactList::factory()->create();
        $contact = Contact::factory()->create();

        // Act: Add contact twice
        $this->postJson("/api/contact-lists/{$contactList->id}/contacts", [
            'contact_id' => $contact->id,
        ]);

        $response = $this->postJson("/api/contact-lists/{$contactList->id}/contacts", [
            'contact_id' => $contact->id,
        ]);

        // Assert
        $response->assertStatus(200);

        // Verify only one relationship exists
        $this->assertEquals(
            1,
            $contactList->contacts()->where('contact_id', $contact->id)->count()
        );
    }

    public function test_add_contact_to_list_requires_valid_contact_id(): void
    {
        // Arrange
        $contactList = ContactList::factory()->create();

        // Act: Try with non-existent contact ID
        $response = $this->postJson("/api/contact-lists/{$contactList->id}/contacts", [
            'contact_id' => 99999,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contact_id']);
    }

    public function test_contact_list_shows_contacts_count(): void
    {
        // Arrange
        $contactList = ContactList::factory()
            ->has(Contact::factory()->count(5))
            ->create();

        // Act
        $response = $this->getJson('/api/contact-lists');

        // Assert
        $response->assertStatus(200);

        $list = collect($response->json('data'))
            ->firstWhere('id', $contactList->id);

        $this->assertEquals(5, $list['contacts_count']);
    }
}
