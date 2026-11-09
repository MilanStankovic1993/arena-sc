<div class="group-match-fields">
    <label>Ime i prezime
        <input name="name" autocomplete="name" maxlength="255" required value="{{ $account['name'] ?? old('name') }}" @readonly($account !== null)>
    </label>
    <label>Mejl za potvrdu mesta
        <input type="email" name="email" autocomplete="email" maxlength="255" required value="{{ $account['email'] ?? old('email') }}" @readonly($account !== null)>
    </label>
</div>
@if ($account !== null)
    <p class="group-match-help">Ocena sa naloga za ovaj termin <strong>{{ $account['profile_rating'] }}/10</strong>.</p>
@endif
