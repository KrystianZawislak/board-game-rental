# Pionek — wypożyczalnia gier planszowych

Publiczny katalog gier z filtrowaniem, rezerwacja przez gościa i ręcznie zbudowany panel personelu.
Projekt rekrutacyjny (MVP). Stack: **PHP 8.2+ · Symfony 7.4 · Twig · SCSS · Doctrine + MySQL**.

## Uruchomienie

```bash
# 1. zależności
composer install

# 2. dane do bazy — utwórz .env.local (NIE jest commitowany), podmień usera/hasło
echo 'DATABASE_URL="mysql://USER:HASŁO@127.0.0.1:3306/board_games_rental?serverVersion=8.0&charset=utf8mb4"' > .env.local

# 3. baza + struktura + dane startowe
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# 4. style + start
php bin/console sass:build
symfony server:start -d --no-tls          # albo: php -S 127.0.0.1:8000 -t public
```

Aplikacja: **http://127.0.0.1:8000** · logowanie: **/login**

### Konta demo

| Rola | E-mail | Hasło |
|------|--------|-------|
| Manager | `manager@pionek.test` | `manager1234` |
| Pracownik | `pracownik@pionek.test` | `pracownik1234` |

## Podgląd na telefonie (ta sama sieć WiFi)

```bash
symfony server:start -d --no-tls --allow-all-ip
# adres komputera: ipconfig getifaddr en0  →  na telefonie: http://<IP>:8000
```

## Testy

Środowisko `test` nie ładuje `.env.local`, więc dane do bazy testowej podaj w `.env.test.local`:

```bash
mysql -u root -e "CREATE DATABASE board_games_rental_test CHARACTER SET utf8mb4;"
echo 'DATABASE_URL="mysql://USER:HASŁO@127.0.0.1:3306/board_games_rental?serverVersion=8.0&charset=utf8mb4"' > .env.test.local
php bin/phpunit
```

## Zdjęcia gier

Domyślnie placeholder. Aby dodać zdjęcie: wrzuć plik do `public/images/games/`, a w panelu (edycja gry)
wpisz jego nazwę w polu „Nazwa pliku zdjęcia".

## Produkcja

Ustaw `APP_ENV=prod` (wyłącza profiler, włącza CSP i własne strony 404/500), wymuś HTTPS,
`composer install --no-dev` + `php bin/console asset-map:compile`.
