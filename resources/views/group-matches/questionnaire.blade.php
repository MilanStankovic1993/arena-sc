@php
    $questionName = fn (string $field): string => isset($questionnaireInput) ? $questionnaireInput.'['.$field.']' : $field;
    $answerKey = isset($questionnaireOld) ? $questionnaireOld.'.answers' : 'answers';
    $ratingKey = isset($questionnaireOld) ? $questionnaireOld.'.self_rating' : 'self_rating';
@endphp
<section class="group-match-questionnaire" aria-label="Nivo igre">
    <p class="group-match-questionnaire-title">Nivo igre</p>
    <p class="group-match-help">Izaberi po jedan odgovor za svako pitanje.</p>
    @foreach (\App\Services\GroupMatchQuestionnaire::questions($questionSport) as $index => [$question, $choices])
        <fieldset class="group-match-question" data-wizard-question>
            <legend>{{ $question }}</legend>
            <div class="group-match-answer-grid">
                @foreach ($choices as $score => $choice)
                    <label class="group-match-option">
                        <input type="radio" name="{{ $questionName('answers') }}[{{ $index }}]" value="{{ $score }}" required @checked((string) old($answerKey.'.'.$index, '') === (string) $score)>
                        <span>{{ $choice }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach
    <fieldset class="group-match-question" data-wizard-question>
        <legend>Kako ocenjuješ svoju igru od 1 do 10?</legend>
        <div class="group-match-rating-grid">
            @foreach (range(1, 10) as $rating)
                <label class="group-match-option">
                    <input type="radio" name="{{ $questionName('self_rating') }}" value="{{ $rating }}" required @checked((int) old($ratingKey) === $rating)>
                    <span>{{ $rating }}</span>
                </label>
            @endforeach
        </div>
        <div class="group-match-rating-labels"><span>1 · tek počinjem</span><span>10 · visok nivo</span></div>
    </fieldset>
</section>
