<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $campaignService
    ) {}

    /**
     * Display a paginated list of campaigns with send stats.
     */
    public function index(): AnonymousResourceCollection
    {
        $campaigns = Campaign::query()
            ->with('contactList')
            ->withCount([
                'sends as pending_count' => fn($query) => $query->where('status', 'pending'),
                'sends as sent_count' => fn($query) => $query->where('status', 'sent'),
                'sends as failed_count' => fn($query) => $query->where('status', 'failed'),
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return CampaignResource::collection($campaigns);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = Campaign::create($request->validated());

        return (new CampaignResource($campaign))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified campaign with send stats.
     */
    public function show(Campaign $campaign): CampaignResource
    {
        $campaign->loadCount([
            'sends as pending_count' => fn($query) => $query->where('status', 'pending'),
            'sends as sent_count' => fn($query) => $query->where('status', 'sent'),
            'sends as failed_count' => fn($query) => $query->where('status', 'failed'),
        ]);

        $campaign->load('contactList');

        return new CampaignResource($campaign);
    }

    /**
     * Dispatch a campaign immediately.
     */
    public function dispatch(Campaign $campaign): JsonResponse
    {
        if ($campaign->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft campaigns can be dispatched',
                'error' => 'invalid_status',
            ], 422);
        }

        $this->campaignService->dispatch($campaign);

        return response()->json([
            'message' => 'Campaign dispatched successfully',
            'data' => new CampaignResource($campaign->refresh()),
        ]);
    }
}
