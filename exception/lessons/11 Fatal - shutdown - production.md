# 11. Fatal Errors, Shutdown Handling & Production Configuration — Part 1

## 1. Zašto nam treba još jedan sloj

Do sada imamo:

```text
traditional PHP warning / notice
↓
set_error_handler()
↓
ErrorException
↓
Throwable pipeline
```

i:

```text
Unhandled Throwable
↓
set_exception_handler()
↓
log
↓
HTTP response
```

Ali još uvek postoji pitanje:

> Šta ako PHP padne toliko ozbiljno da naš normalni error/exception lifecycle više ne može da nastavi?

Na primer:

```text
fatal runtime problem
parse problem
compile problem
memory exhaustion
execution terminated
```

Tu ulazimo u:

```php
register_shutdown_function()
```

i:

```php
error_get_last()
```

To nije zamena za prethodne handlere.

To je još jedan sloj zaštite.

Mentalno:

```text
set_error_handler()
→ traditional runtime errors

set_exception_handler()
→ unhandled Throwable

shutdown handler
→ poslednji pokušaj nakon završetka request-a
```

---

## 2. Nisu svi "fatal errors" isti kao nekada

U modernom PHP-u mnogi problemi koji su ranije delovali kao potpuno fatalni danas su predstavljeni kroz:

```text
Throwable
└── Error
```

Na primer:

```text
TypeError
ArgumentCountError
ValueError
```

možemo:

```php
try {
    // ...
} catch (\Throwable $e) {
    // ...
}
```

Ali to ne znači da je svaki mogući fatal PHP problem postao običan catchable exception.

Postoje problemi koje:

```php
set_error_handler()
```

ne može normalno obraditi.

I postoje problemi koji se dešavaju toliko rano da naš application bootstrap još nije ni stigao da registruje handler.

Zato razlikujemo:

```text
catchable Throwable
vs
traditional/non-recoverable PHP fatal condition
```

---

## 3. Greške koje `set_error_handler()` ne preuzima normalno

Postoje error kategorije koje user-defined error handler ne može standardno preuzeti kao običan warning/notice.

Tipično razmišljamo o kategorijama poput:

```text
E_ERROR
E_PARSE
E_CORE_ERROR
E_CORE_WARNING
E_COMPILE_ERROR
E_COMPILE_WARNING
```

Važna stvar nije da napamet naučiš svaki broj ili svaku konstantu.

Važnije je:

```text
set_error_handler()
≠ univerzalni handler za svaki PHP failure
```

Neke greške nastaju:

```text
tokom startup-a
tokom parsing-a
tokom compilation-a
ili u fatalnom stanju runtime-a
```

gde normalan user error handler nije dovoljan.

---

## 4. Parse error pre bootstrap-a

Zamisli da `index.php` sadrži:

```php
<?php

set_exception_handler(...);

echo 'Hello'
echo 'World';
```

Nedostaje `;`.

PHP mora prvo da parsira fajl.

Problem nastaje pre nego što izvršenje uopšte stigne do:

```php
set_exception_handler(...)
```

Mentalno:

```text
PHP učitava/parsirа index.php
↓
syntax problem
↓
application kod još nije počeo
↓
handler nije registrovan
```

Zato application-level handler ne može da spase nešto što se dogodilo:

```text
pre nego što je application lifecycle počeo
```

Tada se oslanjamo na:

```text
PHP logging
web server / FPM logs
development tooling
```

---

## 5. Parse error u kasnije uključenom fajlu

Postoji važna razlika.

Ako je glavni bootstrap već pokrenut i tek kasnije učitavamo drugi fajl koji ima problem, situacija može biti drugačija nego kada sam bootstrap ne može da se parsira.

Na primer konceptualno:

```text
index.php
↓
bootstrap pokrenut
↓
handler registrovan
↓
kasnije učitavanje drugog PHP koda
↓
problem
```

Ali ne treba graditi application design na ideji:

> Moj global handler će lepo rešiti svaki syntax/compile problem.

Takvi problemi uglavnom predstavljaju:

```text
development/deployment failure
```

i treba ih:

```text
otkriti pre production-a
↓
tests
static analysis
CI
deployment checks
```

---

## 6. `register_shutdown_function()`

PHP omogućava registraciju funkcije koja će biti pozvana kada se request/script završava:

```php
register_shutdown_function(
    function (): void {
        // shutdown logic
    }
);
```

Na primer:

```php
register_shutdown_function(
    function (): void {
        error_log('PHP request finished.');
    }
);
```

Shutdown function se izvršava na kraju lifecycle-a u mnogim normalnim i abnormalnim završecima.

To nam daje poslednju priliku da:

```text
proverimo poslednju PHP grešku
logujemo fatal condition
eventualno pokušamo minimalan fallback
```

Ali:

```text
shutdown function
≠ potpuni recovery system
```

Ako je process u ozbiljno oštećenom stanju, mogućnosti su ograničene.

---

## 7. `error_get_last()`

Unutar shutdown handler-a možemo:

```php
$error = error_get_last();
```

Ako postoji poslednja tradicionalna PHP greška, možemo dobiti podatke poput:

```php
[
    'type' => ...,
    'message' => ...,
    'file' => ...,
    'line' => ...,
]
```

Mentalno:

```text
PHP request završava
↓
shutdown function
↓
error_get_last()
↓
da li je postojao poslednji PHP error?
```

Na primer:

```php
register_shutdown_function(
    function (): void {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        error_log(
            sprintf(
                '%s in %s:%d',
                $error['message'],
                $error['file'],
                $error['line']
            )
        );
    }
);
```

---

## 8. `error_get_last()` nije isto što i exception object

Kod exception-a imamo:

```php
$e->getMessage();
$e->getTrace();
$e->getPrevious();
```

Kod:

```php
error_get_last()
```

dobijamo mnogo jednostavniji array.

Tipično:

```text
type
message
file
line
```

Nemamo automatski ceo exception chain.

Nemamo nužno:

```text
structured stack trace
previous exception
custom exception semantics
```

Zato je normalni:

```text
Throwable pipeline
```

mnogo bogatiji i poželjniji.

Shutdown handling koristimo kao:

```text
fallback
```

a ne kao primarni error architecture.

---

## 9. Filtriranje fatal kategorija

Ne želimo da shutdown handler tretira baš svaku poslednju poruku kao fatal crash.

Možemo imati:

```php
$fatalTypes = [
    E_ERROR,
    E_PARSE,
    E_CORE_ERROR,
    E_COMPILE_ERROR,
];
```

pa:

```php
register_shutdown_function(
    function () use ($fatalTypes): void {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        if (!in_array(
            $error['type'],
            $fatalTypes,
            true
        )) {
            return;
        }

        error_log(
            sprintf(
                'Fatal PHP error: %s in %s:%d',
                $error['message'],
                $error['file'],
                $error['line']
            )
        );
    }
);
```

Mentalno:

```text
shutdown
↓
last error?
↓
fatal category?
├── NE → ignore for fatal handling
└── DA → log / fallback
```

---

## 10. Shutdown handler treba da bude veoma jednostavan

Ako smo stigli do fatal shutdown scenarija, ovo nije dobro mesto da radimo:

```php
$mailer->send(...)
$repository->save(...)
$templateEngine->render(...)
$externalApi->notify(...)
```

Zašto?

Jer možda je upravo:

```text
database unavailable
memory exhausted
filesystem problem
bootstrap partially broken
```

uzrok failure-a.

Shutdown fallback treba da bude što robusniji:

```text
minimalan logging
minimalan response
minimum dependencies
```

Drugim rečima:

> Što je failure ozbiljniji, fallback treba da zavisi od manje stvari.

# 11. Fatal Errors, Shutdown Handling & Production Configuration — Part 2

## 11. Možemo li poslati `500` iz shutdown handler-a?

U određenim situacijama možemo pokušati:

```php
if (!headers_sent()) {
    http_response_code(500);
}
```

pa poslati minimalan response.

Na primer:

```php
register_shutdown_function(
    function (): void {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        $fatalTypes = [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
        ];

        if (!in_array(
            $error['type'],
            $fatalTypes,
            true
        )) {
            return;
        }

        error_log(
            sprintf(
                'Fatal error: %s in %s:%d',
                $error['message'],
                $error['file'],
                $error['line']
            )
        );

        if (!headers_sent()) {
            http_response_code(500);
        }

        echo 'Something went wrong.';
    }
);
```

Ali ovo je:

```text
best effort
```

ne garancija.

Ako je response već delimično poslat:

```text
headers/status možda više ne možemo pravilno menjati
```

Ako je process u teškom stanju:

```text
ni renderovanje response-a nije sigurno
```

---

## 12. Output buffering može pomoći, ali nije čarobni štit

Ako aplikacija koristi output buffering:

```php
ob_start();
```

možemo imati više kontrole nad response-om pre slanja client-u.

Na fatal fallback-u ponekad možemo:

```php
if (ob_get_level() > 0) {
    ob_clean();
}
```

pa poslati bezbedan 500 response.

Mentalno:

```text
app output
↓
buffer
↓
tek kasnije browser
```

To može sprečiti scenario:

```text
pola HTML stranice
+
fatal error output
```

Ali opet:

```text
output buffering
≠ recovery od svakog fatal failure-a
```

To je samo dodatna kontrola nad response output-om.

---

## 13. `error_reporting()`

PHP omogućava da definišemo koje tradicionalne error kategorije želimo da prijavljujemo.

Na primer:

```php
error_reporting(E_ALL);
```

U development-u je veoma korisno:

```text
prijavi sve što može pomoći da uočimo problem
```

Generalno ne želimo razvojnu strategiju:

```php
error_reporting(0);
```

samo da bi aplikacija izgledala kao da nema grešaka.

To ne rešava problem.

Samo ga skriva.

Mentalno:

```text
error_reporting
→ koje PHP probleme smatramo reportable
```

---

## 14. `display_errors`

Možemo imati:

```php
ini_set('display_errors', '1');
```

ili u konfiguraciji:

```ini
display_errors = On
```

To znači:

```text
PHP detalji mogu biti prikazani output-u/client-u
```

U development-u:

```text
korisno
```

U production-u:

```text
opasno
```

jer error može otkriti:

```text
filesystem path
SQL details
class names
internal architecture
server configuration
```

Production princip:

```text
display_errors = Off
```

---

## 15. `log_errors`

Odvojeno imamo:

```ini
log_errors = On
```

To znači:

```text
problem ne mora biti prikazan korisniku
ali treba biti zabeležen
```

Production mentalni model:

```text
DISPLAY
→ OFF

LOG
→ ON
```

Dakle:

```text
user
→ safe response

developer / operations
→ diagnostic information
```

Ovo je jedna od najvažnijih production konfiguracija.

---

## 16. Development vs production setup

Development konceptualno:

```php
error_reporting(E_ALL);

ini_set(
    'display_errors',
    '1'
);

ini_set(
    'log_errors',
    '1'
);
```

Cilj:

```text
problem treba da bude veoma vidljiv
```

Production:

```php
error_reporting(E_ALL);

ini_set(
    'display_errors',
    '0'
);

ini_set(
    'log_errors',
    '1'
);
```

Važna nijansa:

Ne moramo u production-u isključiti reporting samo zato što ne želimo prikaz korisniku.

Naprotiv:

```text
report/log
→ želimo

display
→ ne želimo
```

To su potpuno različite odluke.

---

## 17. Nemoj koristiti `@` kao error-handling strategiju

PHP ima error-control operator:

```php
$content = @file_get_contents($path);
```

`@` potiskuje prikaz/prijavljivanje određenih PHP grešaka za taj expression.

To izgleda primamljivo:

```text
warning nestao
→ problem rešen?
```

Ne.

Problem i dalje može postojati.

Samo smo ga učinili manje vidljivim.

Loš mentalni model:

```text
@ operation
→ safe operation
```

Ispravnije:

```text
@ operation
→ suppressed diagnostic signal
```

Ponekad postoje specifični legacy/API scenariji gde se koristi promišljeno, ali kao opšta application error-handling strategija:

```text
izbegavaj
```

Mnogo je bolje imati jasan contract i eksplicitno handling ponašanje.

---

## 18. Kompletan bootstrap model

Do sada naš Vanilla PHP bootstrap može konceptualno izgledati ovako:

```php
<?php

require_once __DIR__
    . '/../vendor/autoload.php';

error_reporting(E_ALL);

ini_set(
    'display_errors',
    $isDevelopment ? '1' : '0'
);

ini_set(
    'log_errors',
    '1'
);

set_error_handler(
    function (
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException(
            $message,
            0,
            $severity,
            $file,
            $line
        );
    }
);

set_exception_handler(
    function (\Throwable $e): void {
        error_log(
            $e->__toString()
        );

        http_response_code(500);

        echo 'Something went wrong.';
    }
);

register_shutdown_function(
    function (): void {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        $fatalTypes = [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
        ];

        if (!in_array(
            $error['type'],
            $fatalTypes,
            true
        )) {
            return;
        }

        error_log(
            sprintf(
                'Fatal PHP error: %s in %s:%d',
                $error['message'],
                $error['file'],
                $error['line']
            )
        );

        if (!headers_sent()) {
            http_response_code(500);
        }
    }
);

$app->run();
```

Ne mora svaka aplikacija imati identičan kod.

Bitna je arhitektura.

---

## 19. Četiri nivoa zaštite

Sada već možemo nacrtati kompletnu mapu:

```text
1. NORMAL APPLICATION HANDLING
   ↓
   specific try/catch

2. PHP RUNTIME ERROR CONVERSION
   ↓
   set_error_handler()
   ↓
   ErrorException

3. UNHANDLED THROWABLE
   ↓
   set_exception_handler()

4. FATAL SHUTDOWN FALLBACK
   ↓
   register_shutdown_function()
   ↓
   error_get_last()
```

A ispod svega postoji još:

```text
PHP / FPM / web server / system logs
```

To je defense-in-depth pristup.

Nijedan pojedinačni handler nije odgovoran za sve.

---

## 20. Šta ne treba očekivati od shutdown handler-a

Shutdown handler ne treba posmatrati kao:

```text
"catch za fatal errors"
```

u istom smislu kao:

```php
catch (\Throwable $e)
```

On ne vraća program na mesto gde je problem nastao.

Ne možemo:

```text
nastaviti business operation
popraviti pola transaction-a
bezbedno retry-ovati arbitrary code
```

On radi kada se script već završava.

Zato je njegova prava uloga:

```text
last-resort diagnostics
+
minimal fallback
```

a ne:

```text
normal application recovery
```

---

# Najvažnije za zapamtiti

`set_error_handler()` nije dovoljan za sve PHP probleme.

Neki ozbiljni error tipovi nisu normalno obrađivi kroz user-defined error handler.

`register_shutdown_function()`:

```text
izvršava callback pri završetku script lifecycle-a
```

i zajedno sa:

```php
error_get_last()
```

može pomoći da prepoznamo poslednji fatal PHP error.

Tipičan fatal filter:

```php
[
    E_ERROR,
    E_PARSE,
    E_CORE_ERROR,
    E_COMPILE_ERROR,
]
```

Shutdown handler treba da bude:

```text
minimalan
robustan
sa malo dependencies
```

jer se izvršava upravo kada je aplikacija možda ozbiljno oštećena.

`error_reporting()`:

```text
određuje šta prijavljujemo
```

`display_errors`:

```text
da li detalji idu client-u
```

`log_errors`:

```text
da li tehnički problem ide u log
```

Production princip:

```text
error_reporting = široko
display_errors = Off
log_errors = On
```

Drugim rečima:

```text
ne skrivamo problem od developera
ali ga skrivamo od korisnika
```

Ne koristi:

```php
@
```

kao opštu error-handling strategiju.

On uglavnom:

```text
potiskuje signal
```

umesto da rešava uzrok.

Kompletna mapa:

```text
specific catch
↓
set_error_handler()
↓
ErrorException
↓
set_exception_handler()
↓
shutdown fallback
↓
PHP/server logs
```

I centralni mentalni model:

```text
normal exception handling
→ pokušava pravilno da obradi problem

shutdown handling
→ pretpostavlja da je normalan tok već gotov
  i pokušava samo da ostavi trag
  i eventualno bezbedan finalni response
```