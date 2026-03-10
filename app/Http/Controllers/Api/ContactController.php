<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactController extends Controller
{
    /**
     * Display a paginated list of contacts.
     */
    public function index(): AnonymousResourceCollection
    {
        $contacts = Contact::query()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return ContactResource::collection($contacts);
    }

    /**
     * Store a newly created contact.
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = Contact::create($request->validated());

        return (new ContactResource($contact))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Unsubscribe a contact.
     */
    public function unsubscribe(Contact $contact): JsonResponse
    {
        $contact->update(['status' => 'unsubscribed']);

        return response()->json([
            'message' => 'Contact unsubscribed successfully',
            'data' => new ContactResource($contact),
        ]);
    }
}
