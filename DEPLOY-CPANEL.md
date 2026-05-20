# Arena SC deploy na cPanel

## 1. Potrebni podaci

- domen ili poddomen koji pokazuje na projekat
- MySQL baza: `scarenar_arena_sc`
- MySQL user: `scarenar_arena_user`
- lozinka za MySQL user
- putanja do projekta na hostingu

## 2. Važno pre deploy-a

- `filament/filament` mora biti instaliran kao produkcioni paket
- `public` folder mora biti web root
- ako cPanel ne dozvoljava da document root pokazuje direktno na Laravel `public`, onda treba podesiti addon domain/subdomain da root bude Laravel `public`

## 3. Preporučeni `.env` za produkciju

```env
APP_NAME="Arena SC"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tvoj-domen.rs

APP_LOCALE=sr
APP_FALLBACK_LOCALE=sr
APP_FAKER_LOCALE=sr_RS

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=scarenar_arena_sc
DB_USERNAME=scarenar_arena_user
DB_PASSWORD=OVDE_IDE_LOZINKA

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=public
QUEUE_CONNECTION=sync

CACHE_STORE=file

MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=465
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="no-reply@tvoj-domen.rs"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

## 4. Prvi deploy komande

Pokreni u root folderu projekta na serveru:

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm install
npm run build
```

Ako na hostingu nema Node.js:

- build uradi lokalno
- pa pushuj `public/build` manifest i assete na git ili uploaduj build fajlove ručno

## 5. Git deploy redosled

Ako si već povezao server sa git-om:

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ako imaš Node na serveru, dodaj:

```bash
npm ci
npm run build
```

## 6. cPanel / domen

Najčistije rešenje:

- domen ili poddomen treba da pokazuje na `.../arena-sc/public`

Ako domen trenutno pokazuje na root projekta umesto na `public`:

- promeni document root u cPanel-u
- nemoj da Laravel bude serviran iz root foldera bez `public`

## 7. Posle deploy-a proveri

- početna strana radi
- `/login` radi
- `/admin/login` radi
- možeš da se uloguješ sa seed admin nalogom
- upload slike radi iz admin panela
- rezervacija termina se upisuje u bazu

## 8. Seed admin nalog

- email: `admin@arena-sc.test`
- password: `password`

Odmah posle prvog logina promeni lozinku u admin panelu ili direktno kroz bazu.
