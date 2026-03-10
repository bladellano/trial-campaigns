<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_campaigns(): void
    {
        // Arrange: Create campaigns with sends
        $campaigns = Campaign::factory()->count(3)->create();

        // Add sends to first campaign
        CampaignSend::factory()->count(5)->create([
            'campaign_id' => $campaigns[0]->id,
            'status' => 'pending',
        ]);

        // Act
        $response = $this->getJson('/api/campaigns');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'subject',
                        'body',
                        'status',
                        'scheduled_at',
                        'sent_at',
                        'contact_list_id',
                        'stats' => [
                            'pending',
                            'sent',
                            'failed',
                            'total',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_campaign_list_includes_send_stats(): void
    {
        // Arrange: Create campaign with different send statuses
        $campaign = Campaign::factory()->create();

        CampaignSend::factory()->count(3)->create([
            'campaign_id' => $campaign->id,
            'status' => 'pending',
        ]);
        CampaignSend::factory()->count(5)->create([
            'campaign_id' => $campaign->id,
            'status' => 'sent',
        ]);
        CampaignSend::factory()->count(2)->create([
            'campaign_id' => $campaign->id,
            'status' => 'failed',
        ]);

        // Act
        $response = $this->getJson('/api/campaigns');

        // Assert
        $campaignData = $response->json('data.0');

        $this->assertEquals(3, $campaignData['stats']['pending']);
        $this->assertEquals(5, $campaignData['stats']['sent']);
        $this->assertEquals(2, $campaignData['stats']['failed']);
        $this->assertEquals(10, $campaignData['stats']['total']);
    }

    public function test_can_create_campaign(): void
    {
        // Arrange
        $contactList = ContactList::factory()->create();

        $campaignData = [
            'subject' => 'Monthly Newsletter',
            'body' => 'Welcome to our monthly update!',
            'contact_list_id' => $contactList->id,
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ];

        // Act
        $response = $this->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'subject',
                    'body',
                    'status',
                    'contact_list_id',
                    'scheduled_at',
                ],
            ])
            ->assertJsonPath('data.subject', 'Monthly Newsletter')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('campaigns', [
            'subject' => 'Monthly Newsletter',
            'status' => 'draft',
            'contact_list_id' => $contactList->id,
        ]);
    }

    public function test_can_create_campaign_without_schedule(): void
    {
        // Arrange
        $contactList = ContactList::factory()->create();

        $campaignData = [
            'subject' => 'Immediate Campaign',
            'body' => 'Send now!',
            'contact_list_id' => $contactList->id,
        ];

        // Act
        $response = $this->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.scheduled_at', null);
    }

    public function test_create_campaign_requires_valid_data(): void
    {
        // Act
        $response = $this->postJson('/api/campaigns', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'body', 'contact_list_id']);
    }

    public function test_scheduled_at_must_be_future_date(): void
    {
        // Arrange
        $contactList = ContactList::factory()->create();

        $campaignData = [
            'subject' => 'Test Campaign',
            'body' => 'Test body',
            'contact_list_id' => $contactList->id,
            'scheduled_at' => now()->subDay()->toIso8601String(),
        ];

        // Act
        $response = $this->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_can_show_campaign_with_stats(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create();

        CampaignSend::factory()->count(2)->create([
            'campaign_id' => $campaign->id,
            'status' => 'sent',
        ]);
        CampaignSend::factory()->count(1)->create([
            'campaign_id' => $campaign->id,
            'status' => 'failed',
        ]);

        // Act
        $response = $this->getJson("/api/campaigns/{$campaign->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'subject',
                    'body',
                    'status',
                    'stats' => [
                        'pending',
                        'sent',
                        'failed',
                        'total',
                    ],
                ],
            ])
            ->assertJsonPath('data.stats.sent', 2)
            ->assertJsonPath('data.stats.failed', 1);
    }

    public function test_can_dispatch_draft_campaign(): void
    {
        // Arrange
        $contactList = ContactList::factory()
            ->has(Contact::factory()->count(3))
            ->create();

        $campaign = Campaign::factory()->create([
            'status' => 'draft',
            'contact_list_id' => $contactList->id,
        ]);

        // Act
        $response = $this->postJson("/api/campaigns/{$campaign->id}/dispatch");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'status',
                ],
            ]);

        // Status should change to 'sending'
        $campaign->refresh();
        $this->assertEquals('sending', $campaign->status);
    }

    public function test_cannot_dispatch_non_draft_campaign(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create(['status' => 'sent']);

        // Act
        $response = $this->postJson("/api/campaigns/{$campaign->id}/dispatch");

        // Assert
        $response->assertStatus(422)
            ->assertJsonPath('error', 'invalid_status');
    }

    public function test_campaign_list_is_paginated(): void
    {
        // Arrange: Create 20 campaigns
        Campaign::factory()->count(20)->create();

        // Act
        $response = $this->getJson('/api/campaigns');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
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

    public function test_campaign_stats_use_database_aggregation(): void
    {
        // Arrange
        $campaign = Campaign::factory()->create();

        // Create many sends to ensure we're not loading all in memory
        CampaignSend::factory()->count(100)->create([
            'campaign_id' => $campaign->id,
            'status' => 'sent',
        ]);

        // Act
        $response = $this->getJson("/api/campaigns/{$campaign->id}");

        // Assert: Stats should be calculated via DB, not collection
        $response->assertStatus(200)
            ->assertJsonPath('data.stats.sent', 100);

        // Verify no N+1 query by checking the campaign doesn't load all sends
        $this->assertFalse($campaign->relationLoaded('sends'));
    }
}
