<?php

namespace App\Services;

class GroupMatchQuestionnaire
{
    public const TeamSizes = ['padel' => 2, 'basket-3x3' => 3];

    public static function questions(string $sport): array
    {
        $frequency = ['Koliko često igraš?', ['Tek počinjem', 'Povremeno', 'Otprilike jednom nedeljno', 'Više puta nedeljno']];

        return match ($sport) {
            'padel' => [
                $frequency,
                ['Koliko lako održavaš razmenu?', ['Još učim osnovne udarce', 'Kratke razmene sporijim tempom', 'Stabilno pri srednjem tempu', 'Stabilno i pod pritiskom']],
                ['Kako koristiš staklo u igri?', ['Još ga ne koristim', 'Vraćam jednostavne lopte', 'Koristim ga redovno u odbrani', 'Namerno biram udarce posle odbijanja']],
            ],
            'basket-3x3' => [
                $frequency,
                ['Kako se snalaziš s loptom pod pritiskom?', ['Još učim osnove', 'Siguran sam kad imam prostora', 'Uglavnom čuvam loptu i nalazim pas', 'Redovno pravim dobre odluke pod pritiskom']],
                ['Kakvo iskustvo imaš u ekipnoj igri?', ['Tek počinjem', 'Povremeno igram rekreativno', 'Redovno igram 3 na 3 i poznajem kretanje u ekipi', 'Igram organizovane lige ili turnire']],
            ],
        };
    }

    public static function level(array $answers, int $selfRating): int
    {
        return (int) round((array_sum($answers) + 1 + $selfRating) / 2);
    }
}
