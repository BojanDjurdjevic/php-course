# 7. Where Should Exceptions Be Caught? — Part 1

## 1. Najvažnije pitanje nije „mogu li da catch-ujem?“

U PHP-u tehnički možemo skoro svuda napisati:

```php
try {
    $service->execute();
} catch (\Throwable $e) {
    // nešto
}
```

Ali pravo pitanje je:

> Da li ovaj sloj ima dovoljno konteksta da pravilno reaguje na ovaj problem?

To je ključ cele lekcije.

Dobar `catch` postoji zato što trenutni sloj ima **jasnu odgovornost**.

Loš `catch` postoji samo zato što:

```text
"ovde exception prolazi,
pa hajde da ga uhvatim"
```

Mentalno:

```text
catch ne pripada mestu
gde exception nastaje

catch pripada mestu
koje zna šta sa exception-om treba uraditi
```

---

## 2. Naša osnovna arhitektura

U Vanilla PHP aplikaciji imamo:

```text
Request
↓
Middleware
↓
Controller
↓
Service
↓
Repository
↓
PDO / Database
```

Ako database baci problem:

```text
PDO
throws
↑
Repository
↑
Service
↑
Controller
↑
Global Handler
```

Svaki sloj ima drugačiji nivo znanja.

Repository zna:

```text
SQL
PDO
database constraints
```

Service zna:

```text
business use case
transaction
orchestration
```

Controller zna:

```text
HTTP
redirect
response
status code
flash messages
```

Global handler zna:

```text
final fallback
logging
production-safe response
```

Zato catch odluka mora pratiti odgovornost sloja.

---

## 3. Repository catch-uje kada može da prevede infrastructure problem

Repository je prirodno mesto za:

```text
PDOException
```

ako zna šta konkretni DB problem znači.

Na primer:

```php
try {
    $statement->execute();
} catch (\PDOException $e) {
    if ($this->isDuplicateEmail($e)) {
        throw new DuplicateEmailException(
            previous: $e
        );
    }

    throw $e;
}
```

Repository ovde zna:

```text
SQL constraint
↓
značenje u persistence sloju
↓
duplicate email
```

To je dobar catch.

Repository radi:

```text
infrastructure language
↓
application language
```

---

## 4. Repository ne treba da rešava HTTP problem

Loše:

```php
catch (\PDOException $e) {
    http_response_code(500);

    require 'views/errors/500.php';

    exit;
}
```

Zašto?

Jer repository sada zna za:

```text
HTTP
HTML
views
response lifecycle
```

što nije njegova odgovornost.

Repository treba da zna:

```text
persistence
```

ne:

```text
presentation
```

Bolje:

```php
catch (\PDOException $e) {
    throw new UserPersistenceException(
        'Unable to persist user.',
        0,
        $e
    );
}
```

pa problem ide višem sloju.

---

## 5. Repository ne mora catch-ovati svaki `PDOException`

Ovo je isto veoma važno.

Ne treba automatski:

```php
try {
    $statement->execute();
} catch (\PDOException $e) {
    throw new UserPersistenceException(
        'Database error.',
        0,
        $e
    );
}
```

u svakoj metodi.

Ako ne dodajemo korisno značenje:

```text
nema potrebe za wrapping-om
```

Možemo pustiti:

```text
PDOException
```

da propagira dalje.

Repository treba catch-ovati kada:

```text
zna kako da mapira problem
ili
mora da doda smislen persistence context
```

Ne samo zato što PDO može da baci exception.

---

## 6. Service catch-uje kada use case zahteva reakciju

Service zna ceo use case.

Na primer password reset:

```text
update password
+
delete reset token
+
delete remember tokens
```

Ako nešto padne:

```text
sve mora rollback
```

Zato service ima smislen razlog za široki catch:

```php
$this->pdo->beginTransaction();

try {
    $this->users->updatePasswordHash(
        $userId,
        $hash
    );

    $this->resetTokens->deleteForUser(
        $userId
    );

    $this->rememberTokens->deleteForUser(
        $userId
    );

    $this->pdo->commit();
} catch (\Throwable $e) {
    if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
    }

    throw $e;
}
```

Ovde:

```text
Service ne zna nužno
kako konačno da obradi problem
```

ali zna:

```text
transaction mora biti vraćen
```

Zato:

```text
catch
→ rollback
→ rethrow
```

ima smisla.

---

## 7. Service može mapirati problem use case-a

Pretpostavimo:

```text
BookingRepository
→ StorageException
```

ali BookingService zna da se konkretna situacija u use case-u znači:

```text
booking conflict
```

Može:

```php
try {
    $this->bookings->create($booking);
} catch (InventoryConflictException $e) {
    throw new BookingCannotBeCreatedException(
        previous: $e
    );
}
```

Ovo ima smisla ako service stvarno dodaje:

```text
use-case meaning
```

Ali ako samo:

```php
catch (\Throwable $e) {
    throw $e;
}
```

onda catch nema vrednost.

---

## 8. Service ne treba da vraća HTTP response

Loše:

```php
public function register(...): Response
{
    try {
        ...
    } catch (DuplicateEmailException $e) {
        return new Response(
            'Email already taken.',
            422
        );
    }
}
```

Sada `RegistrationService` zna za:

```text
HTTP
status code
Response object
```

To spaja application logic i transport.

Bolje:

```php
public function register(...): User
```

i:

```text
DuplicateEmailException
```

propagira do sloja koji zna HTTP.

Service treba da zna:

```text
registration
```

a ne:

```text
web response
```

---

## 9. Controller catch-uje kada zna kako problem izgleda preko HTTP-a

Controller je HTTP boundary.

Zato ima smisla:

```php
try {
    $registration->register(
        $name,
        $email,
        $password
    );
} catch (DuplicateEmailException $e) {
    $session->flashSet(
        'errors',
        [
            'email' => 'Email already taken.',
        ]
    );

    return $response->redirect('/register');
}
```

Controller zna:

```text
DuplicateEmailException
↓
šta user treba da vidi?
↓
gde redirect?
↓
koji HTTP response?
```

To je njegova odgovornost.

---

## 10. Controller ne treba da catch-uje sve

Loše:

```php
try {
    $registration->register(...);
} catch (\Throwable $e) {
    return $response->view(
        'errors/500.php',
        status: 500
    );
}
```

Ako svaki Controller radi ovo:

```text
RegisterController
LoginController
BookingController
ProfileController
...
```

dobijamo ogromno dupliranje.

Još gore, Controller možda slučajno proguta:

```text
TypeError
PDOException
programming bug
```

i svaki put radi svoju verziju 500 handling-a.

Takvi problemi treba da odu:

```text
global exception handler-u
```

# 7. Where Should Exceptions Be Caught? — Part 2

## 11. Global handler je poslednja granica

Ako exception niko lokalno nije obradio:

```text
Repository
↑
Service
↑
Controller
↑
Global Handler
```

Global handler predstavlja:

```text
last resort
```

On može da uradi:

```text
log exception
↓
odredi production-safe response
↓
postavi status code
↓
vrati HTML ili JSON
```

Na primer:

```php
set_exception_handler(
    function (\Throwable $e): void {
        error_log(
            $e->__toString()
        );

        http_response_code(500);

        echo 'Something went wrong.';
    }
);
```

Ovo ćemo detaljno graditi u posebnoj lekciji.

Za sada:

```text
global handler
→ ništa ne bi trebalo da prođe iznad njega
```

u normalnom request lifecycle-u.

---

## 12. Expected exception može biti lokalno obrađen

Na primer:

```text
DuplicateEmailException
```

Controller zna šta da uradi:

```text
flash email error
redirect /register
```

I tu exception završava.

Mentalno:

```text
expected application problem
↓
specific catch
↓
controlled response
```

Nema potrebe da ide global handler-u.

---

## 13. Unexpected exception treba često pustiti dalje

Pretpostavimo:

```php
try {
    $registration->register(...);
} catch (DuplicateEmailException $e) {
    // handled
}
```

Ako repository baci:

```text
PDOException:
database unavailable
```

taj exception ne odgovara catch-u.

Zato ide dalje:

```text
Controller
↑
Global Handler
```

i završava:

```text
log
+
500
```

To je dobro.

Controller ne treba da izmišlja:

```text
"Email already taken."
```

za database outage.

---

## 14. Not Found nije uvek exception

Ovo je bitna arhitektonska odluka.

Repository:

```php
public function findById(int $id): ?User
```

može normalno vratiti:

```text
User
ili
null
```

To znači:

```text
missing user
→ deo normalnog contract-a
```

Service onda odlučuje:

```php
$user = $this->users->findById($id);

if ($user === null) {
    throw new UserNotFoundException();
}
```

Ako je za konkretni use case missing user exceptional.

Dakle:

```text
Repository
→ možda null

Service
→ odlučuje šta null znači za use case
```

Ovo je često čistije nego da svaki repository lookup automatski baca exception.

---

## 15. Controller vs global handler za 404

Postoje dva validna pristupa.

### Lokalno

```php
try {
    $booking = $service->show($id);
} catch (BookingNotFoundException $e) {
    return $response->notFound();
}
```

### Centralno

Controller samo:

```php
$booking = $service->show($id);
```

a global handler zna:

```text
BookingNotFoundException
→ 404
```

Drugi pristup često smanjuje dupliranje.

Na primer:

```text
UserNotFoundException
BookingNotFoundException
HotelNotFoundException
```

mogu svi imati centralno mapiranje.

To ćemo kasnije još preciznije organizovati.

---

## 16. Catch boundary treba da bude što bliže mestu odluke

Dobar mentalni model:

```text
exception nastaje
↓
propagira
↓
prvi sloj koji stvarno zna šta treba uraditi
↓
catch
```

Ne:

```text
exception nastaje
↓
odmah catch
```

samo zato što je tu nastao.

Primer:

```text
PDOException nastaje u repository-ju
```

ali repository možda nema šta korisno da uradi.

Onda:

```text
ne catch-uje
```

i exception ide dalje.

---

## 17. Jedan failure može imati više boundary-ja

Pretpostavimo transaction:

```text
Repository
→ PDOException
```

Service mora:

```text
rollback
```

pa radi:

```php
catch (\Throwable $e) {
    $this->pdo->rollBack();

    throw $e;
}
```

Global handler kasnije:

```text
log
+
500
```

Dakle isti exception ima dva catch boundary-ja:

```text
Service
→ cleanup boundary

Global Handler
→ final handling boundary
```

To nije dupliranje odgovornosti.

Svaki catch radi nešto drugo.

---

## 18. Odgovornost svakog sloja

Najkorisnija mapa:

```text
REPOSITORY
→ Da li mogu da prevedem infrastructure problem
  u smislen persistence/application problem?

SERVICE
→ Da li use case zahteva rollback,
  cleanup, retry ili semantic mapping?

CONTROLLER
→ Da li znam kako expected problem
  treba da izgleda kroz HTTP/UI?

GLOBAL HANDLER
→ Sve što niko drugi nije obradio:
  log + safe response
```

Ovo nije apsolutno pravilo za svaki projekat.

Ali je veoma dobar default.

---

## 19. Primer kompletne registration putanje

Repository:

```php
try {
    $statement->execute();
} catch (\PDOException $e) {
    if ($this->isDuplicateEmail($e)) {
        throw new DuplicateEmailException(
            previous: $e
        );
    }

    throw $e;
}
```

Service:

```php
public function register(...): User
{
    $hash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    return $this->users->create(
        $name,
        $email,
        $hash
    );
}
```

Controller:

```php
try {
    $registration->register(...);

    return $response->redirect('/login');

} catch (DuplicateEmailException $e) {
    $session->flashSet(
        'errors',
        [
            'email' => 'Email already taken.',
        ]
    );

    return $response->redirect('/register');
}
```

Ako nastane:

```text
UNIQUE violation
```

tok:

```text
PDOException
↓
Repository maps
↓
DuplicateEmailException
↓
Controller catches
↓
controlled response
```

Ako database potpuno padne:

```text
PDOException
↓
Repository ne zna specifično mapiranje
↓
Service
↓
Controller
↓
Global Handler
↓
log + 500
```

To je veoma čista arhitektura.

---

## 20. Primer kompletne password-reset putanje

Service:

```php
$this->pdo->beginTransaction();

try {
    $this->users->updatePasswordHash(
        $userId,
        $hash
    );

    $this->resetTokens->deleteForUser(
        $userId
    );

    $this->rememberTokens->deleteForUser(
        $userId
    );

    $this->pdo->commit();

} catch (\Throwable $e) {
    if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
    }

    throw $e;
}
```

Service catch-uje:

```text
Throwable
```

ali ne zato da problem pretvori u:

```text
false
```

nego zbog:

```text
atomicity
```

Ako nešto padne:

```text
rollback
↓
rethrow
↓
eventualno global handler
```

To je primer pravilnog širokog catch-a.

---

# Najvažnije za zapamtiti

Najvažnije pitanje:

> **Da li ovaj sloj zna šta konkretno treba da uradi sa ovim exception-om?**

Ako ne zna:

```text
ne catch-uj
```

Repository obično catch-uje kada:

```text
može da prevede infrastructure problem
u smisleniji persistence/application problem
```

Primer:

```text
PDOException
↓
DuplicateEmailException
```

Service catch-uje kada use case zahteva:

```text
rollback
cleanup
retry
semantic mapping
```

Controller catch-uje kada zna:

```text
kako expected application problem
treba pretvoriti u HTTP/UI ponašanje
```

Global handler:

```text
poslednja granica
→ logging
→ production-safe response
→ 500 ili centralno mapiran status
```

Ne catch-uj svaki exception na svakom sloju.

Loše:

```text
Repository catches
Service catches
Controller catches
Global Handler catches

a svi rade isto
```

Dobro:

```text
svaki catch ima svoju odgovornost
```

Centralni mentalni model:

```text
Repository
→ infrastructure meaning

Service
→ use-case meaning / transaction boundary

Controller
→ HTTP meaning

Global Handler
→ final fallback
```

I najvažnije pravilo:

```text
Exception treba da propagira
sve dok ne stigne do prvog sloja
koji stvarno zna kako da reaguje.
```