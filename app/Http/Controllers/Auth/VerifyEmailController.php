<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return redirect()
                ->route('login')
                ->with('status', 'Verifikacioni link nije vazeci za ovaj nalog. Posaljite novi link ili pokusajte ponovo.');
        }

        if ($user->hasVerifiedEmail()) {
            Auth::login($user);

            return redirect(route('dashboard', ['verified' => 1], false));
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        Auth::login($user);

        return redirect()
            ->to(route('dashboard', ['verified' => 1], false))
            ->with('status', 'Email adresa je uspesno potvrdjena.');
    }
}
