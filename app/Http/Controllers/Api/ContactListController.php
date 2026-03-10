<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddContactToListRequest;
use App\Http\Requests\Api\StoreContactListRequest;
use App\Http\Resources\ContactListResource;
use App\Models\ContactList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactListController extends Controller
{
    /**
     * Display a list of all contact lists.
     */
    public function index(): AnonymousResourceCollection
    {
        $contactLists = ContactList::query()
            ->withCount('contacts')
            ->orderBy('created_at', 'desc')
            ->get();

        return ContactListResource::collection($contactLists);
    }

    /**
     * Store a newly created contact list.
     */
    public function store(StoreContactListRequest $request): JsonResponse
    {
        $contactList = ContactList::create($request->validated());

        return (new ContactListResource($contactList))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Add a contact to a list.
     */
    public function addContact(AddContactToListRequest $request, ContactList $contactList): JsonResponse
    {
        $contactId = $request->validated('contact_id');

        // Attach contact if not already attached
        $contactList->contacts()->syncWithoutDetaching([$contactId]);

        return response()->json([
            'message' => 'Contact added to list successfully',
            'data' => new ContactListResource($contactList->load('contacts')),
        ]);
    }
}
