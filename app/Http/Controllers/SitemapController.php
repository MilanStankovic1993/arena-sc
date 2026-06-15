<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Event;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            [
                'loc' => route('home'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'loc' => route('about'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'loc' => route('sports.index'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ],
            [
                'loc' => route('booking.index'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => route('price-list.index'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ],
            [
                'loc' => route('equipment.index'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
            [
                'loc' => route('events.index'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
        ])->concat(
            Court::query()
                ->where('is_active', true)
                ->get()
                ->map(fn (Court $court): array => [
                    'loc' => route('courts.show', $court),
                    'lastmod' => optional($court->updated_at)->toDateString() ?? now()->toDateString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ])
        )->concat(
            Event::query()
                ->whereIn('status', ['registration', 'ongoing', 'completed'])
                ->get()
                ->map(fn (Event $event): array => [
                    'loc' => route('events.show', $event),
                    'lastmod' => optional($event->updated_at)->toDateString() ?? now()->toDateString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ])
        );

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
