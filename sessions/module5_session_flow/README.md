# Module 5 — Vanilla PHP Sessions/Auth Flow

Ovo je mala vežba koja spaja završni teorijski deo modula Sessions + Cookies.

## Fajlovi

- `session_bootstrap.php` — centralizovan session config + `session_start()`
- `flash_helpers.php` — one-request flash state
- `fake_user_store.php` — hardkodovan user koji glumi DB rezultat
- `auth_helpers.php` — auth check + 30 min idle timeout
- `signin_view.php` — login forma
- `signin_submit.php` — validation + `password_verify()` + `session_regenerate_id(true)`
- `member_area.php` — protected page
- `signout_submit.php` — kompletan logout

## Test login

- Email: `bojan@example.com`
- Password: `Secret123!`

## Pokretanje

Iz foldera:

```bash
php -S 127.0.0.1:8000
```

Zatim otvori:

```text
http://127.0.0.1:8000/signin_view.php
```

## Šta namerno NIJE ubačeno

- MySQL
- PDO
- remember-me token
- CSRF token

Razlog: ova vežba je fokusirana na session lifecycle. MySQL/PDO i persistent remember-me flow imaju više smisla kada stignemo do narednih modula.

## Važna napomena za `Secure`

`session_bootstrap.php` automatski postavlja `secure=true` kada je request preko HTTPS-a, a `false` na običnom lokalnom HTTP development serveru.

U produkciji autentifikovana aplikacija treba da radi preko HTTPS-a.
