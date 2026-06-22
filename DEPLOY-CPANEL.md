# Sportski centar Arena deploy na cPanel / shared hosting

Ovaj dokument je usklađen sa trenutnim stanjem projekta:
- Laravel 13
- Filament admin panel
- javni uploadovi slika preko `public` diska
- email verifikacija registracije
- reset lozinke
- kontakt forma na početnoj
- mail potvrde za rezervacije i otkazivanja

## 1. Šta mora da radi na serveru

Pre produkcije server mora da ima:
- PHP kompatibilan sa projektom
- MySQL bazu
- mogućnost pokretanja `composer install`
- Laravel `public` folder kao document root
- `storage/app/public` writable
- `public/storage` link ka `storage/app/public`

Bez ova 3 uslova mail i slike neće raditi kako treba:
- ispravan `APP_URL`
- `FILESYSTEM_DISK=public`
- `php artisan storage:link`

## 2. Preporučeni document root

Najčistije rešenje:

- projekat: `/home/scarenar/arena-sc`
- web root domena: `/home/scarenar/arena-sc/public`

Ako hosting to još nije prebacio, traži od podrške da primarni domen `scarena.rs` pokazuje direktno na Laravel `public` folder.

## 3. Produkcioni `.env`

Koristi ovo kao osnovu i zameni stvarne vrednosti:

```env
APP_NAME="Sportski centar Arena"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://scarena.rs

APP_LOCALE=sr
APP_FALLBACK_LOCALE=sr
APP_FAKER_LOCALE=sr_RS

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

SEED_ADMIN_NAME="Arena Administrator"
SEED_ADMIN_EMAIL=OVDE_UNESI_ADMIN_EMAIL
SEED_ADMIN_PASSWORD=OVDE_UNESI_JAKU_UNIKATNU_LOZINKU

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=scarenar_arena_sc
DB_USERNAME=scarenar_arena_user
DB_PASSWORD=OVDE_UNESI_LOZINKU_BAZE

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database

CACHE_STORE=file

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_SCHEME=ssl
MAIL_HOST=mail.scarena.rs
MAIL_PORT=465
MAIL_USERNAME=info@scarena.rs
MAIL_PASSWORD=OVDE_UNESI_MAIL_LOZINKU
MAIL_FROM_ADDRESS="info@scarena.rs"
MAIL_FROM_NAME="${APP_NAME}"
CONTACT_MAIL_TO="info@scarena.rs"
CONTACT_MAIL_NAME="Sportski centar Arena"
CONTACT_PHONE="+381 60 111 222"
CONTACT_INSTAGRAM="https://www.instagram.com/scarena.rs/"
LOCATION_NAME="Sportski centar Arena"
LOCATION_ADDRESS="Adranska 114, Kraljevo"
LOCATION_LABEL="Kraljevo, Srbija"
LOCATION_MAPS_URL="https://maps.app.goo.gl/acaW8mYdBzuqCuuMA"
LOCATION_MAP_EMBED_URL="https://www.openstreetmap.org/export/embed.html?bbox=20.6583283%2C43.7362758%2C20.6783283%2C43.7462758&amp;layer=mapnik&amp;marker=43.7412758%2C20.6683283"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
```

Napomena:
- koristi tačne SMTP podatke iz cPanel email naloga `info@scarena.rs`
- ako host traži `tls` umesto `ssl`, promeni `MAIL_SCHEME`
- `CONTACT_MAIL_TO` je adresa na koju stižu poruke sa kontakt forme i admin obaveštenja o rezervacijama
- `CONTACT_PHONE`, `CONTACT_INSTAGRAM` i `LOCATION_*` menjaju kontakt i mapu na jednom mestu kroz ceo sajt

## 4. Mail funkcionalnosti koje sada postoje

Na produkciji će slati mejlove za:
- potvrdu email adrese posle registracije
- slanje novog verifikacionog linka
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
- aktivaciju članarine
- podsetnik pred istek članarine

Zato je ispravan SMTP obavezan pre puštanja sajta uživo.

## 5. Deploy redosled

Samo pri prvoj instalaciji, dok je `APP_KEY` u produkcionom `.env` prazan, pokreni:

```bash
php artisan key:generate
```

`APP_KEY` nikada ne generisi ponovo pri redovnom deploy-u. Rotacija kljuca prekida postojece
sesije i moze uciniti ranije sifrovane podatke necitljivim.

Ako postoji terminal / SSH:

```bash
cd /home/scarenar/arena-sc
git pull
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ako nema Node.js na serveru:
- `npm run build` radiš lokalno
- `public/build` i manifest idu kroz Git

Ako server ima Node.js:

```bash
npm ci
npm run build
```

## 6. Šta ne ide preko Git-a

Ne idu preko Git-a:
- slike koje uploaduješ kroz admin panel

One se čuvaju na serveru u:
- `storage/app/public`

Zato je bitno:
- da `storage` bude writable
- da postoji `public/storage` link

## 7. Dozvole

Proveri da server može da piše u:
- `storage`
- `bootstrap/cache`

Tipično:
- folderi `755` ili `775`
- fajlovi `644`

## 8. Scheduler / cron

U cPanel Cron Jobs dodaj zadatak koji se izvrsava svakog minuta:

```cron
* * * * * cd /home/scarenar/arena-sc && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Scheduler pokrece i kratkotrajni queue worker, pa kontakt i rezervacijski emailovi ne blokiraju HTTP zahtev. Proveri da je `QUEUE_CONNECTION=database` i da minutni cron radi.

Za brz odziv PHP-a hosting mora imati ukljucen OPcache. U MultiPHP INI Editor-u proveri najmanje `opcache.enable=1` i `opcache.memory_consumption=128`; bez OPcache-a Laravel pri svakom zahtevu ponovo ucitava framework fajlove.

Ako je PHP putanja na hostingu drugacija, uzmi tacnu PHP 8.3+ CLI putanju iz cPanel-a.
Bez ovog cron-a automatski podsetnici za istek clanarine nece biti poslati.

## 9. Produkcioni QA posle deploy-a

Obavezno proveri:

### Javni sajt
- početna radi
- `Tereni` radi
- detalj terena radi
- `Oprema` radi
- `O nama` radi
- `Dogadjaji` radi
- `Rezervisi termin` radi
- slike iz `public/media/...` rade
- slike iz admin uploadova rade

### Auth
- registracija radi
- stiže verifikacioni email
- potvrda emaila radi
- login radi
- forgot password šalje mejl
- reset lozinke radi

### Rezervacije
- rezervacija sa sajta se kreira kao `Rezervisana`
- korisnik dobija mejl potvrde
- admin dobija mejl o novoj rezervaciji
- korisnik može da otkaže
- otkazivanje šalje mejlove

### Admin
- `/admin/login` radi
- upload slika radi za:
  - sportove
  - terene
  - opremu
  - događaje
- statistika radi
- kalendar radi
- dugi `create/edit` modali skroluju normalno

## 10. Ako server nema terminal

Ako nemaš terminal i `composer install` ne možeš da pokreneš ručno:
- traži od hostinga da pokrene `composer install --no-dev --optimize-autoloader`
- neka urade i:

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Od hostinga trazi i podesavanje minutnog cron zadatka za `php artisan schedule:run`.

## 11. Seed podaci

Pre `php artisan migrate:fresh --seed` ili `php artisan db:seed` postavi
`SEED_ADMIN_EMAIL` i jedinstveni `SEED_ADMIN_PASSWORD` od najmanje 12 karaktera.
Ako ih ne postavis, katalog ce biti kreiran bez admin naloga.

Seeder dodatno ubacuje osnovni produkcioni katalog:
- sportove `Padel` i `Basket 3x3`
- 3 padel terena i 1 basket 3x3 teren
- osnovni cenovnik za radne dane i vikend

Seeder ne ubacuje demo korisnike, demo rezervacije, demo opremu ni demo događaje.

Posle deploy-a kroz admin panel dopuni slike, opremu, događaje i eventualno koriguj cene ako se promene.
