# Arena SC

Laravel + Filament sistem za:
- javni sajt sportskog centra
- online rezervacije termina
- opremu za iznajmljivanje i prodaju
- događaje, lige i turnire
- admin analitiku i upravljanje sadržajem

## Lokalni razvoj

Projekat je podešen za lokalni Laragon domen:

```env
APP_URL=http://arena-sc.test
DB_CONNECTION=mysql
DB_DATABASE=arena_sc
FILESYSTEM_DISK=public
```

Pokretanje iz čistog stanja:

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run build
php artisan test
```

## Glavne funkcionalnosti

- premium javni sajt u zeleno-zlatnoj paleti
- registracija i prijava korisnika
- email verifikacija naloga
- reset lozinke
- rezervacija termina po sportu, danu, vremenu, trajanju i terenu
- otkazivanje rezervacije od strane korisnika i admina
- oprema uz termin
- kontakt forma sa mejl potvrdom
- događaji sa listingom i detaljima
- Filament admin panel na srpskom
- statistika i analitika rezervacija, korisnika i terena

## Mail tokovi

Sistem trenutno šalje mejlove za:
- potvrdu email adrese pri registraciji
- ponovno slanje verifikacionog linka
- reset lozinke
- kontakt formu:
  - poruka adminu
  - potvrda korisniku
- novu rezervaciju:
  - potvrda korisniku
  - obaveštenje adminu
- otkazivanje rezervacije:
  - obaveštenje korisniku
  - obaveštenje adminu

## Slike i storage

Postoje 2 vrste slika:

1. Statičke projektne slike
- nalaze se u `public/`
- idu preko Git-a

2. Slike uploadovane iz admin panela
- čuvaju se u `storage/app/public`
- ne idu preko Git-a
- zahtevaju `php artisan storage:link`

## Produkcija

Za produkciju pogledaj:
- [DEPLOY-CPANEL.md](C:\laragon\www\arena-sc\DEPLOY-CPANEL.md)
- [\.env.production.example](C:\laragon\www\arena-sc\.env.production.example)

Ključne produkcione stvari:
- `APP_URL=https://scarena.rs`
- `FILESYSTEM_DISK=public`
- ispravan SMTP za `info@scarena.rs`
- `storage/app/public` writable
- `public/storage -> storage/app/public`
- `composer install --no-dev --optimize-autoloader`

## Brzi QA pre live puštanja

Proveri:
- početnu
- `Tereni`
- `Oprema`
- `O nama`
- `Dogadjaji`
- `Rezervisi termin`
- registraciju i verifikaciju emaila
- reset lozinke
- upload slika iz admina
- rezervaciju i otkazivanje termina
- admin statistiku

## Seed admin nalog

Ako koristiš seed podatke:
- email: `admin@arena-sc.test`
- password: `password`

Odmah promeni pristupne podatke pre produkcije.
