<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        return view('events.index', [
            'featuredEvent' => Event::query()->where('is_featured', true)->latest('start_date')->first(),
            'events' => Event::query()
                ->withCount(['entries', 'matches'])
                ->orderByRaw("case when status = 'ongoing' then 0 when status = 'registration' then 1 else 2 end")
                ->orderBy('start_date')
                ->get(),
        ]);
    }

    public function show(Event $event): View
    {
        $event->load([
            'entries' => fn ($query) => $query->orderByDesc('points')->orderByDesc('score_for'),
            'matches' => fn ($query) => $query->with(['homeEntry', 'awayEntry'])->orderBy('scheduled_at'),
        ]);

        return view('events.show', [
            'event' => $event,
        ]);
    }
}
