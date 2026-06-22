<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventEntry;
use App\Models\EventMatch;
use Illuminate\Support\Collection;

class EventStatisticsService
{
    public function buildStandings(Event $event): Collection
    {
        $entries = $event->entries
            ->mapWithKeys(fn (EventEntry $entry) => [
                $entry->id => [
                    'entry' => $entry,
                    'entry_id' => $entry->id,
                    'team_name' => $entry->team_name,
                    'user_name' => $entry->user?->name,
                    'played' => 0,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'points' => 0,
                    'score_for' => 0,
                    'score_against' => 0,
                    'score_difference' => 0,
                ],
            ]);

        $event->matches
            ->filter(fn (EventMatch $match) => $match->status === 'finished')
            ->each(function (EventMatch $match) use (&$entries): void {
                if (! $match->home_entry_id || ! $match->away_entry_id) {
                    return;
                }

                if (! isset($entries[$match->home_entry_id], $entries[$match->away_entry_id])) {
                    return;
                }

                if (is_null($match->home_score) || is_null($match->away_score)) {
                    return;
                }

                $home = $entries[$match->home_entry_id];
                $away = $entries[$match->away_entry_id];

                $home['played']++;
                $away['played']++;
                $home['score_for'] += $match->home_score;
                $home['score_against'] += $match->away_score;
                $away['score_for'] += $match->away_score;
                $away['score_against'] += $match->home_score;

                if ($match->home_score > $match->away_score) {
                    $home['wins']++;
                    $home['points'] += 3;
                    $away['losses']++;
                } elseif ($match->home_score < $match->away_score) {
                    $away['wins']++;
                    $away['points'] += 3;
                    $home['losses']++;
                } else {
                    $home['draws']++;
                    $away['draws']++;
                    $home['points'] += 1;
                    $away['points'] += 1;
                }

                $home['score_difference'] = $home['score_for'] - $home['score_against'];
                $away['score_difference'] = $away['score_for'] - $away['score_against'];

                $entries[$match->home_entry_id] = $home;
                $entries[$match->away_entry_id] = $away;
            });

        return collect($entries->values())
            ->sort(function (array $left, array $right): int {
                return [
                    $right['points'] <=> $left['points'],
                    $right['wins'] <=> $left['wins'],
                    $right['score_difference'] <=> $left['score_difference'],
                    $right['score_for'] <=> $left['score_for'],
                    strcmp($left['team_name'], $right['team_name']),
                ][0]
                    ?: ($right['wins'] <=> $left['wins'])
                    ?: ($right['score_difference'] <=> $left['score_difference'])
                    ?: ($right['score_for'] <=> $left['score_for'])
                    ?: strcmp($left['team_name'], $right['team_name']);
            })
            ->values()
            ->map(function (array $row, int $index): array {
                $row['position'] = $index + 1;

                return $row;
            });
    }

    public function buildSummary(Event $event, Collection $standings): array
    {
        $matches = $event->matches;
        $finishedMatches = $matches->where('status', 'finished');

        $topEntry = $standings->first();

        return [
            'entries_count' => $event->entries->count(),
            'matches_count' => $matches->count(),
            'finished_matches_count' => $finishedMatches->count(),
            'scheduled_matches_count' => $matches->where('status', 'scheduled')->count(),
            'cancelled_matches_count' => $matches->where('status', 'cancelled')->count(),
            'leader_name' => $topEntry['team_name'] ?? null,
            'leader_points' => $topEntry['points'] ?? null,
            'top_attack' => $standings->sortByDesc('score_for')->first()['team_name'] ?? null,
            'best_defense' => $standings->sortBy('score_against')->first()['team_name'] ?? null,
        ];
    }

    public function groupMatchesByRound(Event $event): Collection
    {
        return $event->matches
            ->groupBy(fn (EventMatch $match) => $match->round_label ?: 'Raspored')
            ->map(fn (Collection $matches, string $round): array => [
                'round' => $round,
                'matches' => $matches->values(),
            ])
            ->values();
    }

    public function generateLeagueSchedule(Event $event): int
    {
        $entries = $event->entries()->orderBy('id')->get()->values();

        if ($entries->count() < 2) {
            return 0;
        }

        if ($event->matches()->exists()) {
            return 0;
        }

        $participants = $entries->all();
        $isOdd = count($participants) % 2 !== 0;

        if ($isOdd) {
            $participants[] = null;
        }

        $participantCount = count($participants);
        $rounds = $participantCount - 1;
        $half = intdiv($participantCount, 2);
        $created = 0;

        for ($round = 0; $round < $rounds; $round++) {
            for ($pair = 0; $pair < $half; $pair++) {
                $home = $participants[$pair];
                $away = $participants[$participantCount - 1 - $pair];

                if (! $home || ! $away) {
                    continue;
                }

                $event->matches()->create([
                    'home_entry_id' => $round % 2 === 0 ? $home->id : $away->id,
                    'away_entry_id' => $round % 2 === 0 ? $away->id : $home->id,
                    'round_label' => ($round + 1).'. kolo',
                    'status' => 'scheduled',
                ]);

                $created++;
            }

            $fixed = array_shift($participants);
            $last = array_pop($participants);
            array_unshift($participants, $fixed);
            array_splice($participants, 1, 0, [$last]);
        }

        return $created;
    }
}
