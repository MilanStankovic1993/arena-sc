<x-mail-layout title="Obrisana rezervacija | Sportski centar Arena">
    <h1>Obrisana rezervacija #{{ $snapshot['id'] }}</h1>
    <p>Rezervacija je obrisana i termin je oslobođen.</p>
    <p><strong>Korisnik/gost:</strong> {{ $snapshot['customer'] }}</p>
    <p><strong>Telefon:</strong> {{ $snapshot['phone'] ?: '—' }}</p>
    <p><strong>Sport / teren:</strong> {{ $snapshot['sport'] }} / {{ $snapshot['court'] }}</p>
    <p><strong>Termin:</strong> {{ $snapshot['starts_at'] }} – {{ $snapshot['ends_at'] }}</p>
    <p><strong>Cena:</strong> {{ number_format((float) $snapshot['total_price'], 0, ',', '.') }} RSD</p>
    <p><strong>Obrisao:</strong> {{ $snapshot['actor'] }}</p>
    <p><strong>Vreme brisanja:</strong> {{ $snapshot['deleted_at'] }}</p>
</x-mail-layout>
