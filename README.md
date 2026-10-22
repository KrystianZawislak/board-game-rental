# Pionek — Board Game Rental

A board game rental MVP. It has a public catalog with client-side filtering, guest reservations and a staff panel for managing games and the reservation lifecycle. The UI is in Polish.

**Stack:** PHP 8.2.35+, 8.3.36+, 8.4.27+ or 8.5.12+, Symfony 7.4, Twig, Stimulus, SCSS, Doctrine, MySQL

## Features

- Netflix-style catalog with filters (category, players, play time) and a scroll-driven canvas hero
- Guest reservations with date-overlap checks, row locking against race conditions and per-IP rate limiting
- Staff panel with roles (`ROLE_STAFF`, `ROLE_MANAGER`): game CRUD and the reservation flow *pending → confirmed → issued → returned*
- Security: login throttling, CSRF, CSP and security headers, audit log, custom 404/500 pages

## Getting started

```bash
composer install

# local DB credentials (not committed)
echo 'DATABASE_URL="mysql://USER:PASSWORD@127.0.0.1:3306/board_games_rental?serverVersion=8.0&charset=utf8mb4"' > .env.local

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
php bin/console sass:build

symfony server:start -d --no-tls   # or: php -S 127.0.0.1:8000 -t public
```

Open http://127.0.0.1:8000. The staff login is at `/login`.

| Role | E-mail | Password |
|---|---|---|
| Manager | `manager@pionek.test` | `manager1234` |
| Staff | `pracownik@pionek.test` | `pracownik1234` |

To test on a phone, run `symfony server:start -d --no-tls --allow-all-ip` and open `http://<your-ip>:8000`.

## Tests

The `test` environment doesn't load `.env.local`, so put its credentials in `.env.test.local`:

```bash
mysql -u root -e "CREATE DATABASE board_games_rental_test CHARACTER SET utf8mb4;"
echo 'DATABASE_URL="mysql://USER:PASSWORD@127.0.0.1:3306/board_games_rental?serverVersion=8.0&charset=utf8mb4"' > .env.test.local
php bin/phpunit
```

## Notes

- **Game images:** drop a file into `public/images/games/` and enter its filename when editing the game. Games without an image show a placeholder.
- **Production:** set `APP_ENV=prod` (this enables CSP and the custom error pages), then run `composer install --no-dev` and `php bin/console asset-map:compile`.
