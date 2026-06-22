<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactMessageConfirmationMail;
use App\Mail\ContactMessageReceivedMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactMessageController extends Controller
{
    public function __invoke(StoreContactMessageRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        Mail::to(config('arena.contact.email'))
            ->queue(new ContactMessageReceivedMail($payload));

        try {
            Mail::to($payload['email'])
                ->queue(new ContactMessageConfirmationMail($payload));
        } catch (Throwable $exception) {
            Log::warning('Contact confirmation email failed.', [
                'recipient' => $payload['email'],
                'error' => $exception->getMessage(),
            ]);
        }

        $route = match ($payload['redirect_to'] ?? 'home') {
            'booking' => 'booking.index',
            default => 'home',
        };

        return redirect()
            ->route($route)
            ->with('status', 'Uspesno ste poslali poruku. Javicemo vam se uskoro.');
    }
}
