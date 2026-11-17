<?php

namespace App\Http\Controllers;

use App\Models\CollectionCase;
use App\Services\CustomerCommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class CollectionActionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:collection.action.create'];
    }

    public function store(Request $request, CollectionCase $collectionCase, CustomerCommunicationService $communication): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:Phone Call,SMS,Field Visit,Guarantor Contact,Demand Letter,Promise to Pay,Legal Action'],
            'result' => ['nullable', 'string', 'max:255'],
            'promise_to_pay_date' => ['nullable', 'date', 'after_or_equal:today'],
            'promise_to_pay_amount' => ['nullable', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'notify_customer' => ['nullable', 'boolean'],
        ]);

        $collectionCase->actions()->create($validated + [
            'performed_by' => $request->user()->id,
            'performed_at' => now(),
        ]);
        $collectionCase->update([
            'promise_to_pay_date' => $validated['promise_to_pay_date'] ?? $collectionCase->promise_to_pay_date,
            'promise_to_pay_amount' => $validated['promise_to_pay_amount'] ?? $collectionCase->promise_to_pay_amount,
            'last_action_at' => now(),
        ]);

        if ($request->boolean('notify_customer')) {
            $communication->collectionFollowUp($collectionCase->loan->customer, $validated['notes'] ?: 'Our team recorded a follow-up on your loan account. Please contact your branch.');
        }

        return back()->with('success', 'Collection action recorded.');
    }
}
