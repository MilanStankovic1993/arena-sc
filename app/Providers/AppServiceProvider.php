<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Models\UserMembership;
use App\Observers\ReservationObserver;
use App\Observers\UserMembershipObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Reservation::observe(ReservationObserver::class);
        UserMembership::observe(UserMembershipObserver::class);

        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            $path = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
                absolute: false,
            );

            return url($path);
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage())
                ->subject('Potvrdite email adresu | Sportski centar Arena')
                ->view('emails.auth.verify-email', [
                    'url' => $url,
                    'user' => $notifiable,
                ]);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage())
                ->subject('Reset lozinke | Sportski centar Arena')
                ->greeting('Zahtev za reset lozinke')
                ->line('Primili smo zahtev za promenu lozinke na vasem nalogu.')
                ->action('Resetuj lozinku', $url)
                ->line('Ako niste trazili reset lozinke, nije potrebna nikakva dodatna akcija.');
        });
    }
}
