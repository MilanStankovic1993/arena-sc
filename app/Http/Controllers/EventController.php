<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventStatisticsService;
use Illuminate\View\View;

class EventController extends Controller
{
    private const PUBLIC_STATUSES = ['registration', 'ongoing', 'completed'];

    public function index(): View
    {
        return view('events.index', [
            'featuredEvent' => Event::query()
                ->whereIn('status', self::PUBLIC_STATUSES)
                ->where('is_featured', true)
                ->latest('start_date')
                ->first(),
            'events' => Event::query()
                ->whereIn('status', self::PUBLIC_STATUSES)
                ->withCount(['entries', 'matches'])
                ->orderByRaw("case when status = 'ongoing' then 0 when status = 'registration' then 1 else 2 end")
                ->orderBy('start_date')
                ->get(),
        ]);
    }

    public function show(Event $event, EventStatisticsService $statisticsService): View
    {
        abort_unless(in_array($event->status->value, self::PUBLIC_STATUSES, true), 404);

        $event->load([
            'entries.user',
            'matches' => fn ($query) => $query->with(['homeEntry.user', 'awayEntry.user'])->orderBy('scheduled_at'),
        ]);

        $standings = $statisticsService->buildStandings($event);
        $summary = $statisticsService->buildSummary($event, $standings);
        $matchGroups = $statisticsService->groupMatchesByRound($event);

        return view('events.show', [
            'event' => $event,
            'standings' => $standings,
            'summary' => $summary,
            'matchGroups' => $matchGroups,
        ]);
    }
}
