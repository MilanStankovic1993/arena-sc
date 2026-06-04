<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactMessageConfirmationMail;
use App\Mail\ContactMessageReceivedMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    public function __invoke(StoreContactMessageRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        Mail::to(config('services.contact.address'))
            ->send(new ContactMessageReceivedMail($payload));

        Mail::to($payload['email'])
            ->send(new ContactMessageConfirmationMail($payload));

        $route = match ($payload['redirect_to'] ?? 'home') {
            'booking' => 'booking.index',
            default => 'home',
        };

        return redirect()
            ->route($route)
            ->with('status', 'Uspesno ste poslali poruku. Javicemo vam se uskoro.');
    }
}
