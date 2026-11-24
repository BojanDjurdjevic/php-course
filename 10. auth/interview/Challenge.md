# Module 10 — Authentication bez frameworka

## Coding Checkpoint — 5 zadataka

**Konačni rezultat: 9.4/10**

Dokument sadrži:

- originalni tekst svakog zadatka,
- moje originalno rešenje,
- ocenu,
- korekcije i objašnjenja,
- finalnu ispravljenu verziju gde je bila potrebna.

---

# Coding zadatak 1/5 — `AuthService::attempt()`

## Zadatak

Postoje:

```php
interface UserRepository
{
    public function findByEmail(string $email): ?User;

    public function updatePasswordHash(
        int $userId,
        string $passwordHash
    ): void;
}
```

```php
final class User
{
    public function __construct(
        private int $id,
        private string $email,
        private string $passwordHash,
        private bool $active
    ) {}

    public function id(): int
    {
        return $this->id;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
```

```php
final class Session
{
    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
```

Napisati:

```php
final class AuthService
```

sa constructor DI za:

```text
UserRepository
Session
```

i metodom:

```php
public function attempt(
    string $email,
    string $password
): bool
```

Metoda treba da:

1. normalizuje email,
2. pronađe user-a,
3. vrati `false` ako ne postoji,
4. proveri password,
5. vrati `false` ako password nije validan,
6. proveri da li je account active,
7. uradi rehash ako je potreban,
8. regeneriše session ID,
9. upiše `user_id`,
10. vrati `true`.

Ne koristiti direktno:

```text
$_POST
$_SESSION
PDO
SQL
header()
```

---

## Moje originalno rešenje

```php
<?php

namespace App\Auth;

use App\Contracts\UserRepository;
use App\Dto\UserLoginData;
use App\Enums\LoginResult;
use App\Models\User;
use App\Session\Session;
use DomainException;

final class AuthService {

    private ?User $currentUser = null;

    private bool $userResolved = false;

    public function __construct(
        private UserRepository $users,
        private Session $session
    ) {}

    public function id(): ?int 
    {
        $id = $this->session->get('user_id');

        return $id === null ? null : (int) $id;
    }

    public function user(): ?User
    {
        if($this->userResolved) return $this->currentUser;

        $this->userResolved = true;

        $id = $this->id();

        if($id === null) return null;

        $user = $this->users->findById($id);

        if($user === null || !$user->isActive() || !$user->isEmailVerified()) {
            $this->session->forget('user_id');
            $this->session->forget('auth_version');

            return null;
        }

        if($user->authVersion() !== $this->session->get('auth_version')) {
            $this->session->forget('user_id');
            $this->session->forget('auth_version');

            return null;
        }

        $this->currentUser = $user;

        return $this->currentUser;
    }

    public function check(): bool 
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function attempt(UserLoginData $data): bool
    {
        $normalizedEmail = mb_strtolower(trim($data->email));

        $password = $data->password;

        $user = $this->users->findByEmail($normalizedEmail);

        if($user === null)
            return false;
        }

        if(!password_verify($password, $user->passwordHash())) {
            return false;
        }

        if(!$user->isActive()) {
            return false;
        }

        if(!$user->isEmailVerified()) {
            return false;
        }

        if(password_needs_rehash($user->passwordHash(), PASSWORD_DEFAULT)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            if(!$this->users->rehashIfUnchanged(
                $user->id(),
                $user->passwordHash(),
                $user->authVersion(),
                $hashed
            )) {
                return false;
            }
        }

        // $this->login($user); ovde bih zvao login posebno:

        $this->session->regenerate();

        $this->session->put('user_id', $user->id());
        $this->session->put('auth_version', $user->authVersion());

        $this->currentUser = $user;
        $this->userResolved = true;

        return true;
    }

    public function login(User $user): void
    {
        $this->session->regenerate();

        $this->session->put('user_id', $user->id());
        $this->session->put('auth_version', $user->authVersion());

        $this->currentUser = $user;
        $this->userResolved = true;
    }

    public function logout(): void
    {
        //$this->session->forget('user_id');
        $this->session->destroy();

        $this->currentUser = null;
        $this->userResolved = true;
    }
}
```

Napomena uz originalno rešenje:

> Iskreno ja bih je napisao ovako, sa razdvojenim attempt i login a attempt poziva login, bas kao sto smo ucili kroz lekcije, ali posto zadatak trazi sta trazi.. Idemo po zadatku sto se tice attempt a ostalo pisem kako bih pisao da imam odvojeno login i attempt.

Takođe koristim strožiji CAS-style password rehash radi concurrency zaštite.

---

## Ocena

**8.7/10**

---

## Korekcije

### 1. Syntax error

Originalno:

```php
if($user === null)
    return false;
}
```

Treba:

```php
if ($user === null) {
    return false;
}
```

### 2. Promenjen je zadati public contract

Traženo:

```php
public function attempt(
    string $email,
    string $password
): bool
```

Originalno:

```php
public function attempt(UserLoginData $data): bool
```

DTO je vrlo dobar realni dizajn, ali coding zadatak treba rešavati unutar zadatog API-ja.

### 3. Korišćene su metode koje nisu postojale u zadatom contract-u

Na primer:

```text
findById()
rehashIfUnchanged()
isEmailVerified()
authVersion()
get()
forget()
destroy()
```

Sve mogu biti dobre u stvarnoj aplikaciji, ali nisu bile deo konkretnog zadatka.

### 4. `login(User)` je bolji realni dizajn

U stvarnoj aplikaciji je čistije:

```php
$this->login($user);

return true;
```

jer `login()` centralizuje:

```text
session regeneration
user_id
auth_version
current-user cache
```

i može ga koristiti i `RememberMeService`.

### 5. CAS rehash nijansa

Ako dva legitimna login request-a istovremeno pokušaju rehash, jedan CAS može pasti iako password nije promenjen.

CAS failure ne mora automatski značiti invalid credentials.

Moguće je:

```text
CAS failed
↓
reload fresh User
↓
ponovo proveri password / auth_version
↓
odluči
```

### 6. Rehash i `auth_version`

Transparentni rehash istog password-a ne mora nužno povećavati `auth_version`.

Bolja razlika:

```text
password rehash
→ password_hash update
→ auth_version ostaje isti
```

dok:

```text
password change/reset
→ password_hash update
→ auth_version++
```

---

## Finalna verzija prema zadatom contract-u

```php
final class AuthService
{
    public function __construct(
        private UserRepository $users,
        private Session $session
    ) {}

    public function attempt(
        string $email,
        string $password
    ): bool {
        $email = mb_strtolower(trim($email));

        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return false;
        }

        if (
            !password_verify(
                $password,
                $user->passwordHash()
            )
        ) {
            return false;
        }

        if (!$user->isActive()) {
            return false;
        }

        if (
            password_needs_rehash(
                $user->passwordHash(),
                PASSWORD_DEFAULT
            )
        ) {
            $newHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $this->users->updatePasswordHash(
                $user->id(),
                $newHash
            );
        }

        $this->session->regenerate();

        $this->session->put(
            'user_id',
            $user->id()
        );

        return true;
    }
}
```

---

# Coding zadatak 2/5 — Current User Cache

## Zadatak

Postoje:

```php
interface UserRepository
{
    public function findById(int $id): ?User;
}
```

```php
final class User
{
    public function __construct(
        private int $id,
        private bool $active
    ) {}

    public function id(): int
    {
        return $this->id;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
```

```php
final class Session
{
    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        return $_SESSION[$key] ?? $default;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
```

Napisati:

```php
private ?User $currentUser = null;
private bool $userResolved = false;

public function id(): ?int;

public function user(): ?User;

public function check(): bool;

public function guest(): bool;
```

Uslovi:

```text
user() sme napraviti najviše jedan
findById() query tokom jednog request-a

missing user
→ očisti user_id

inactive user
→ očisti user_id

ponovni user()
→ ne sme ponovo u bazu
```

---

## Moje originalno rešenje

```php
private ?User $currentUser = null;

private bool $userResolved = false;

public function __construct(
    private UserRepository $users,
    private Session $session
) {}

public function id(): ?int 
{
    $id = $this->session->get('user_id');

    return $id === null ? null : (int) $id;
}

public function user(): ?User
{
    if($this->userResolved) return $this->currentUser;

    $this->userResolved = true;

    $id = $this->id();

    if($id === null) return null;

    $user = $this->users->findById($id);

    if($user === null || !$user->isActive()) {
        $this->session->forget('user_id');

        return null;
    }

    $this->currentUser = $user;

    return $this->currentUser;
}

public function check(): bool 
{
    return $this->user() !== null;
}

public function guest(): bool
{
    return !$this->check();
}
```

---

## Ocena

**10/10**

---

## Zašto je rešenje dobro

Dva property-ja:

```php
private ?User $currentUser = null;
private bool $userResolved = false;
```

razlikuju tri stanja:

```text
1. userResolved = false
   → lookup još nije urađen

2. userResolved = true
   currentUser = null
   → lookup je urađen, nema validnog user-a

3. userResolved = true
   currentUser = User
   → user je uspešno resolved
```

Zato:

```php
$auth->user();
$auth->check();
$auth->user();
$auth->guest();
```

pravi najviše:

```text
1 × findById()
```

tokom istog request-a.

Takođe:

```php
if ($user === null || !$user->isActive()) {
    $this->session->forget('user_id');

    return null;
}
```

čisti stale authentication state.

Jedina stilska preporuka je koristiti braces i za jednolinijske `if` blokove:

```php
if ($this->userResolved) {
    return $this->currentUser;
}
```

ali to nije funkcionalna greška.

---

# Coding zadatak 3/5 — Password Reset Transaction

## Zadatak

Postoje:

```php
interface UserRepository
{
    public function updatePasswordHash(
        int $userId,
        string $passwordHash
    ): void;
}
```

```php
interface PasswordResetTokenRepository
{
    public function deleteForUser(int $userId): void;
}
```

```php
interface RememberTokenRepository
{
    public function deleteForUser(int $userId): void;
}
```

PDO se dobija kroz constructor DI.

Napisati:

```php
final class PasswordResetService
```

sa:

```php
public function reset(
    int $userId,
    string $newPassword
): void
```

Metoda treba da:

1. napravi novi password hash,
2. otvori transaction,
3. promeni password hash,
4. obriše password-reset token,
5. obriše Remember Me tokene,
6. commit ako sve uspe,
7. rollback ako nešto baci exception,
8. ponovo baci originalni exception.

Svi repository-ji koriste isti PDO connection.

---

## Moje originalno rešenje

```php
final class PasswordResetService {
    public function __construct(
        private PasswordResetTokenRepository $reset,
        private UserRepository $users,
        private RememberTokenRepository $remember,
        private PDO $pdo
    ) {}

    public function reset(int $userId, string $newPassword)
    {
        $hashedPassword = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        $this->pdo->beginTransaction();

        try {
            $this->users->updatePasswordHash(
                $userId,
                $hashedPassword
            );

            $this->reset->deleteForUser($userId);

            $this->remember->deleteForUser($userId);
            
            $this->pdo->commit();

            return true;

        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
```

---

## Ocena

**9.7/10**

---

## Korekcije

Glavna greška je nepoštovanje zadatog return type-a.

Traženo:

```php
public function reset(
    int $userId,
    string $newPassword
): void
```

Originalno nije imalo `: void` i vraćalo je:

```php
return true;
```

Kod `void` metode uspeh se podrazumeva ako nije bačen exception.

Dobro je što se:

```php
password_hash()
```

radi **pre početka transaction-a**, jer nema razloga držati DB transaction otvorenu tokom relativno skupe CPU operacije.

Transaction boundary je pravilno postavljen u service:

```text
BEGIN
↓
update password
↓
delete reset token
↓
delete remember tokens
↓
COMMIT
```

A u failure slučaju:

```text
ROLLBACK
↓
rethrow original exception
```

---

## Finalna verzija

```php
final class PasswordResetService
{
    public function __construct(
        private PasswordResetTokenRepository $reset,
        private UserRepository $users,
        private RememberTokenRepository $remember,
        private PDO $pdo
    ) {}

    public function reset(
        int $userId,
        string $newPassword
    ): void {
        $hashedPassword = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        $this->pdo->beginTransaction();

        try {
            $this->users->updatePasswordHash(
                $userId,
                $hashedPassword
            );

            $this->reset->deleteForUser($userId);

            $this->remember->deleteForUser($userId);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
```

---

# Coding zadatak 4/5 — Remember Me Restore

## Zadatak

Postoje:

```php
interface RememberTokenRepository
{
    public function findBySelector(
        string $selector
    ): ?RememberToken;

    public function deleteBySelector(
        string $selector
    ): void;
}
```

```php
final class RememberToken
{
    public function userId(): int;
    public function validatorHash(): string;
    public function expiresAt(): \DateTimeImmutable;
}
```

```php
interface UserRepository
{
    public function findById(int $id): ?User;
}
```

i:

```php
final class AuthService
{
    public function login(User $user): void;
}
```

Napisati:

```php
public function restore(
    string $cookieValue
): bool
```

Cookie format:

```text
selector:validator
```

Metoda treba da:

1. parsira cookie,
2. odbije nevalidan format,
3. pronađe token po selector-u,
4. odbije ako token ne postoji,
5. proveri expiry,
6. hash-uje validator sa SHA-256,
7. koristi `hash_equals()`,
8. pronađe user-a,
9. odbije missing/inactive user-a,
10. pozove `$this->auth->login($user)`,
11. vrati `true`.

Ako token postoji, ali je expired ili validator nije validan, token se briše iz baze.

---

## Moje originalno rešenje

```php
public function restore(string $cookieValue): bool
{
    if(!is_string($cookieValue)) return false;

    $parts = explode(':', $cookieValue);

    if(count($parts) !== 2) {
        $this->clearCookie();
        return false;
    }

    [$selector, $validator] = $parts;

    if (
        !preg_match('/\A[a-f0-9]{18}\z/', $selector) ||
        !preg_match('/\A[a-f0-9]{64}\z/', $validator)
    ) {
        $this->clearCookie();
        return false;
    }

    $data = $this->remember->findBySelector($selector);

    if($data === null) {
        return false;
    } 

    if($data->expiresAt <= gmdate('Y-m-d H:i:s')) {
        $this->remember->deleteBySelector($selector);
        return false;
    } 

    $hashedValidator = hash('sha256', $validator);

    if(!hash_equals($data->validatorHash, $hashedValidator)) {
        $this->remember->deleteBySelector($selector);
        return false;
    }

    $user = $this->users->findById($data->userId());

    if($user === null || !$user->isActive()) return false;

    $this->auth->login($user);

    return true;
}
```

---

## Ocena

**8.8/10**

---

## Korekcije

### 1. `RememberToken` koristi metode, ne public properties

Dato je:

```php
public function userId(): int;
public function validatorHash(): string;
public function expiresAt(): \DateTimeImmutable;
```

Zato treba:

```php
$data->userId()
$data->validatorHash()
$data->expiresAt()
```

a ne:

```php
$data->validatorHash
$data->expiresAt
```

### 2. Expiry je `DateTimeImmutable`

Originalno:

```php
$data->expiresAt <= gmdate('Y-m-d H:i:s')
```

pokušava da poredi konceptualno:

```text
DateTimeImmutable
vs
string
```

Bolje:

```php
if (
    $token->expiresAt()
    <= new \DateTimeImmutable()
) {
    ...
}
```

ili koristiti eksplicitni UTC timezone ako ga aplikacija standardizuje.

### 3. `is_string()` je redundantno

Method signature već kaže:

```php
restore(string $cookieValue)
```

pa type system već garantuje string.

### 4. Format validation je dobra

Ako je selector generisan:

```php
bin2hex(random_bytes(9))
```

onda očekujemo:

```text
18 hex karaktera
```

Ako je validator:

```php
bin2hex(random_bytes(32))
```

onda očekujemo:

```text
64 hex karaktera
```

Zato su regex provere korisne pre DB lookup-a.

### 5. `clearCookie()` nije deo datog zadatka

To je dobra realna funkcionalnost, ali nije bilo potrebno implementirati u ovom zadatku.

### 6. `AuthService::login()` je pravilno ponovo iskorišćen

Nakon uspešne validacije:

```php
$this->auth->login($user);
```

je mnogo bolje nego ručno ponavljati session login lifecycle.

---

## Finalna verzija

```php
public function restore(string $cookieValue): bool
{
    $parts = explode(':', $cookieValue);

    if (count($parts) !== 2) {
        return false;
    }

    [$selector, $validator] = $parts;

    if (
        !preg_match('/\A[a-f0-9]{18}\z/', $selector) ||
        !preg_match('/\A[a-f0-9]{64}\z/', $validator)
    ) {
        return false;
    }

    $token = $this->remember->findBySelector(
        $selector
    );

    if ($token === null) {
        return false;
    }

    if (
        $token->expiresAt()
        <= new \DateTimeImmutable()
    ) {
        $this->remember->deleteBySelector(
            $selector
        );

        return false;
    }

    $hashedValidator = hash(
        'sha256',
        $validator
    );

    if (
        !hash_equals(
            $token->validatorHash(),
            $hashedValidator
        )
    ) {
        $this->remember->deleteBySelector(
            $selector
        );

        return false;
    }

    $user = $this->users->findById(
        $token->userId()
    );

    if (
        $user === null ||
        !$user->isActive()
    ) {
        return false;
    }

    $this->auth->login($user);

    return true;
}
```

---

# Coding zadatak 5/5 — Registration Race Condition

## Zadatak

Postoje:

```php
interface UserRepository
{
    public function findByEmail(
        string $email
    ): ?User;

    /**
     * Može baciti DuplicateEmailException.
     */
    public function create(
        string $name,
        string $email,
        string $passwordHash
    ): User;
}
```

```php
final class DuplicateEmailException
    extends \DomainException
{
}
```

Napisati:

```php
final class RegistrationService
```

sa:

```php
public function register(
    string $name,
    string $email,
    string $password
): User
```

Treba da:

1. normalizuje email,
2. prvo uradi `findByEmail()` radi UX-a,
3. baci `DuplicateEmailException` ako postoji,
4. hash-uje password,
5. pozove `create()`,
6. ne proguta `DuplicateEmailException` koji može nastati zbog race condition-a,
7. vrati kreiranog `User`.

Cilj je pokazati oba nivoa zaštite:

```text
application pre-check
+
database UNIQUE invariant
```

---

## Moje originalno rešenje

```php
final class RegistrationService {

    public function __construct(
        private UserRepository $users
    ) {}

    public function register(
        string $name,
        string $email,
        string $password
    ): User
    {
        $email = strtolower(trim($email)); 
        
        $user = $this->users->findByEmail($email);

        if($user !== null)
            throw new DuplicateEmailException(
                'Email already taken.'
            );

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        return $this->users->create(
            $name,
            $email,
            $passwordHash
        );
    }
}
```

---

## Ocena

**10/10**

---

## Zašto je rešenje dobro

Application-level check:

```php
$user = $this->users->findByEmail($email);

if ($user !== null) {
    throw new DuplicateEmailException(
        'Email already taken.'
    );
}
```

daje normalan UX u uobičajenom slučaju.

Ali race condition i dalje može izgledati:

```text
Request A
→ findByEmail()
→ null

Request B
→ findByEmail()
→ null

A → INSERT
→ success

B → INSERT
→ database UNIQUE violation
```

Zato database mora imati:

```sql
UNIQUE(email)
```

a concrete repository treba taj database error da mapira u:

```php
DuplicateEmailException
```

`RegistrationService` ne treba da proguta exception.

Originalni:

```php
return $this->users->create(
    $name,
    $email,
    $passwordHash
);
```

upravo omogućava da:

```text
normal create
→ User

race-condition duplicate
→ DuplicateEmailException prolazi dalje
```

što je pravilno ponašanje.

`mb_strtolower()` može biti opcija za širu Unicode podršku, ali `strtolower(trim())` je potpuno prihvatljiv za ovaj zadatak.

---

# Konačni rezultat

| Zadatak | Tema | Ocena |
|---|---|---:|
| 1/5 | `AuthService::attempt()` | 8.7/10 |
| 2/5 | Current User Cache | 10/10 |
| 3/5 | Password Reset Transaction | 9.7/10 |
| 4/5 | Remember Me Restore | 8.8/10 |
| 5/5 | Registration Race Condition | 10/10 |

## Ukupno

**9.4/10**

---

# Najjače oblasti

Posebno dobro razumem:

```text
authentication lifecycle
session state
current-user resolution
per-request caching
password hashing
password reset transactions
Remember Me mental model
repository/service separation
race conditions
database invariants
```

---

# Najvažnija lekcija iz coding checkpoint-a

Najveće greške nisu bile u auth logici.

Bile su uglavnom u:

```text
zadati contract
vs
moja realna arhitektura
```

Na primer, u stvarnoj aplikaciji:

```php
attempt(UserLoginData $data)
```

može biti odličan dizajn.

Ali ako coding zadatak kaže:

```php
attempt(
    string $email,
    string $password
): bool
```

treba prvo rešiti **tačno taj contract**.

Isto važi za:

```text
metode koje postoje u zadatim interface-ima
return types
property vs method access
object type vs string comparison
```

Na tehničkom intervjuu:

```text
dobar dizajn
+
precizno poštovanje contract-a
```

zajedno daju kompletno rešenje.

---

# Završna mentalna mapa

```text
LOGIN

credentials
↓
AuthService
↓
UserRepository
↓
password_verify()
↓
account state
↓
optional rehash
↓
session regeneration
↓
user_id
```

```text
CURRENT USER

session user_id
↓
UserRepository::findById()
↓
active User?
↓
per-request cache
↓
check() / guest()
```

```text
PASSWORD RESET

new password
↓
password_hash()
↓
BEGIN TRANSACTION
↓
update password
↓
delete reset token
↓
delete remember tokens
↓
COMMIT
```

```text
REMEMBER ME RESTORE

selector:validator
↓
format validation
↓
selector lookup
↓
expiry
↓
SHA-256 validator hash
↓
hash_equals()
↓
User lookup
↓
AuthService::login()
↓
normal authenticated session
```

```text
REGISTRATION

normalize email
↓
application duplicate check
↓
password_hash()
↓
repository create
↓
database UNIQUE(email)
↓
race-safe invariant
```