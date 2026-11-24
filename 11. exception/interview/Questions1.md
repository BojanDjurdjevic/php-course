# Module 11 — PHP Errors, Exceptions & Debugging

## Interview Checkpoint — Questions 1–6

> Status: **privremeno pauziran nakon pitanja 6/12**
>
> Preostalo za kasnije:
>
> - Interview pitanja 7–12
> - Coding zadaci 1–5
> - Završna ocena modula

---

# Question 1

## Objasni razliku između bug-a, PHP `Error`-a i `Exception`-a.

Dodatno: da li svaki `Exception` znači da u kodu postoji bug?

## Moj odgovor

Bug je najčešće greška u samom kodu i čak ne mora biti ni primetna. Recimo da smo hteli da postignemo jedno, a dobili smo drugo. Skripta se izvršava uredno, ali imamo neželjen rezultat.

Pod `Error` spadaju mnogi problemi koje PHP sam detektuje, kao na primer:

```text
TypeError
ValueError
ArgumentCountError
ArithmeticError
DivisionByZeroError
AssertionError
```

`Exception` je često application/library signal.

Mada ovo nije apsolutno pravilo. Exception takođe doživljavam kao razrešavanje problema u izvršavanju koji mogu ili ne moraju biti poznati u kodu.

Na primer `LogicException`: kada je nemoguće obrisati booking zbog biznis pravila.

## Ocena

**9.2/10**

## Korekcije i dopune

Glavna ideja je veoma dobro pogođena.

Posebno je važno:

```text
Bug
→ program može normalno da se izvrši
→ rezultat ipak može biti pogrešan
```

PHP hijerarhija:

```text
Throwable
├── Error
└── Exception
```

Jedina važnija korekcija odnosi se na primer sa `LogicException`.

Za business pravilo poput:

```text
booking više ne sme da se otkaže
```

semantički je prirodnije imati:

```php
BookingCannotBeCancelledException
    extends DomainException
```

nego generički `LogicException`.

## Interview-ready odgovor

Bug je greška u logici ili implementaciji koda i ne mora uopšte da proizvede PHP grešku ili exception. Program može normalno da se izvrši, ali da da pogrešan rezultat.

`Error` je deo `Throwable` hijerarhije i često predstavlja problem koji PHP runtime sam detektuje, kao `TypeError`, `ValueError` ili `ArgumentCountError`.

`Exception` je takođe `Throwable`, ali ga često koristimo kao signal da normalan application flow ne može da se nastavi — bilo da ga baca naš kod ili biblioteka.

Exception ne znači automatski bug.

Na primer:

```text
DuplicateEmailException
BookingCannotBeCancelledException
```

mogu predstavljati potpuno očekivane application situacije.

Sa druge strane, neki exception-i mogu nastati i zbog stvarnog programming problema.

---

# Question 2

## Kako funkcioniše exception propagation?

Šta se dešava kada:

```php
throw new SomeException();
```

trenutna metoda nema odgovarajući `catch`?

Kako to funkcioniše kroz:

```text
Controller
→ Service
→ Repository
```

i gde exception treba catch-ovati?

## Moj odgovor

`throw new SomeException();` bez catch-a se propagira višem sloju, a ako niko nema ko bi ga obradio ide na vrh do globalnog handler-a.

Recimo da se izuzetak dogodi u Repository-ju.

Repository postavlja pitanje:

> Da li ja ovo mogu da uhvatim i obradim na smislen način?

Ako da — catch.

Ako ne — pustim dalje.

Čak može biti delimično uz uslov:

```text
if (isDuplicateEmail())
→ bacim konkretnu grešku
```

Ako nije duplicate email i ne znam šta je, pustim ga naviše.

Repository se u suštini bavi izuzecima vezanim za bazu i PDO.

Onda exception ode u Service, a on treba da se bavi izuzecima tog nivoa. On se ne bavi HTTP problemima.

Ako on ne može, pušta dalje u Controller.

Controller se bavi izuzecima vezanim za HTTP i output.

Opet isto pitanje:

> Mogu li ovo smisleno obraditi? Da li imam nešto da uradim pre nego što ga pustim naviše?

Dakle, gde catch-ovati?

U sloju gde ima najviše smisla i gde se exception može pravilno obraditi.

## Ocena

**9.7/10**

## Korekcije i dopune

Najvažniji princip je potpuno pogođen:

> Exception se ne catch-uje zato što je tu nastao, već zato što taj sloj zna šta sa njim treba da uradi.

Ako niko ne obradi `Throwable`, završava kod:

```php
set_exception_handler(...)
```

odnosno globalnog exception handler-a.

Ako Repository nema ništa korisno da uradi, nije potrebno ni:

```php
catch (PDOException $e) {
    throw $e;
}
```

Može jednostavno da ga ne catch-uje.

Rethrow ima smisla kada sloj pre toga radi nešto korisno:

```text
rollback
cleanup
mapping
dodavanje context-a
```

## Interview-ready odgovor

Kada se exception baci, normalno izvršavanje trenutnog bloka se prekida.

Ako trenutni nivo nema odgovarajući `catch`, exception propagira uz call stack ka pozivaocu.

Repository ga catch-uje samo ako zna šta sa njim treba da uradi — na primer da prepozna `UNIQUE` violation i mapira `PDOException` u `DuplicateEmailException`.

Service ga catch-uje kada ima use-case odgovornost, kao što su rollback transaction-a, cleanup ili semantic mapping.

Controller može catch-ovati expected application exception kada zna kako da ga pretvori u HTTP/UI ponašanje.

Ako ga niko ne obradi, dolazi do global exception handler-a, koji tipično loguje problem i vraća bezbedan finalni response.

Centralno pravilo:

```text
Exception catch-ujemo u prvom sloju
koji ima konkretnu odgovornost
za taj failure.
```

---

# Question 3

## Objasni razliku između rethrow-a i exception wrapping/chaining-a.

Primer 1:

```php
catch (Throwable $e) {
    throw $e;
}
```

Primer 2:

```php
catch (PDOException $e) {
    throw new UserPersistenceException(
        'Unable to persist user.',
        0,
        $e
    );
}
```

Zašto je `previous` exception važan?

## Moj odgovor

Prvi primer je čist rethrow koji čak, ako ništa drugo nema da se radi u catch-u, nije ni potreban.

`Throwable` je interface koji obuhvata i `Error` i `Exception`.

Drugi primer jeste wrapping i chaining.

Na taj način svaki sloj može dodati svoje značenje, a da se originalni uzrok ne izgubi.

To je kao lanac uzroka:

```text
Registration failed

WHY?
↓
User could not be persisted

WHY?
↓
Database rejected INSERT
```

Wrapping zapravo znači:

```text
pravimo novi exception
+
originalni ostaje kao previous
```

Zato je `$e` kao `previous` važan, jer constructor već prima taj podatak i nećemo izgubiti originalnu grešku.

## Ocena

**9.5/10**

## Korekcije i dopune

Važna terminološka razlika:

```text
throw
→ PHP keyword kojim nešto bacamo

Throwable
→ interface na vrhu Error/Exception hijerarhije
```

Rethrow:

```php
throw $e;
```

baca isti exception objekat dalje.

Wrapping:

```php
throw new UserPersistenceException(
    'Unable to persist user.',
    0,
    $e
);
```

pravi novi exception, dok originalni ostaje dostupan preko:

```php
$exception->getPrevious();
```

## Interview-ready odgovor

Rethrow znači da ponovo bacamo isti exception objekat, na primer:

```php
throw $e;
```

To ima smisla kada trenutni sloj prvo uradi nešto poput rollback-a ili cleanup-a, ali ne želi konačno da obradi problem.

Wrapping znači da napravimo novi exception koji bolje opisuje problem na višem nivou apstrakcije, ali originalni exception prosledimo kao `previous`.

Na primer:

```text
UserPersistenceException
↓ previous
PDOException
```

Tako viši sloj vidi application-level značenje, dok originalni tehnički uzrok ostaje dostupan za logging i debugging preko:

```php
getPrevious()
```

---

# Question 4

## Zašto je redosled `catch` blokova važan?

Šta se dešava ovde:

```php
try {
    $service->execute();
} catch (Throwable $e) {
    // A
} catch (DomainException $e) {
    // B
} catch (DuplicateEmailException $e) {
    // C
}
```

ako `DuplicateEmailException` nasleđuje `DomainException`?

Kada koristiti multi-catch?

```php
catch (FooException | BarException $e)
```

## Moj odgovor

Prvi primer je pogrešan jer ide obrnutim redom.

Uvek idemo od užeg ka širem exception-u.

U ovom slučaju `DuplicateEmailException` bi svakako bio uhvaćen od `Throwable`, ali pre svega semantički nije jasno šta se tačno dogodilo.

Multi-catch ima smisla ako očekujemo dva exception-a sličnog scope-a, mada bi i tu možda moglo da se razmisli o korišćenju zajedničkog parent-a.

## Ocena

**9.3/10**

## Korekcije i dopune

PHP proverava `catch` blokove redom i koristi prvi kompatibilan.

Zato je pravilan redosled:

```php
catch (DuplicateEmailException $e) {
    // najpreciznije
} catch (DomainException $e) {
    // šire
} catch (Throwable $e) {
    // fallback
}
```

Kod multi-catch-a nije presudno samo da exception-i budu sličnog scope-a.

Najvažnije je:

```text
različiti exception tipovi
→ zahtevaju identičan handling
```

Ako postoji smislen zajednički parent, često je još bolje catch-ovati njega.

## Interview-ready odgovor

PHP proverava `catch` blokove redom i izvršava prvi kompatibilan.

Zato specifični exception-i moraju da budu pre svojih parent tipova, dok `Throwable` obično ide poslednji.

Ako `Throwable` stavimo prvi, on će uhvatiti i `DomainException` i `DuplicateEmailException`, pa specifični handling više neće dobiti priliku.

Multi-catch koristim kada različiti exception tipovi zahtevaju potpuno isti način obrade:

```php
catch (FooException | BarException $e)
```

Ako postoji dobar zajednički parent:

```text
CommonException
├── FooException
└── BarException
```

često je čistije:

```php
catch (CommonException $e)
```

---

# Question 5

## Da li svaki `PDOException` treba pretvoriti u custom exception?

Repository može dobiti:

```text
duplicate email
foreign key problem
database down
SQL syntax error
deadlock
```

Šta radiš kada prepoznaš `UNIQUE(email)` violation, a šta kada ne znaš tačno šta greška znači?

## Moj odgovor

Prvo treba razumeti da imamo expected i unexpected DB exceptions.

Možemo očekivati i lepo mapirati `unique email` ili neke poznate constraint probleme.

Ali šta ako imamo:

```text
database server unavailable
SQL syntax error
table missing
programming bug u query-ju
```

To ne želimo da pretvorimo u neki generički output poput `email duplicate`, jer to nije tačno.

Zato imamo global handler koji može da obradi neočekivani `PDOException`.

```text
wrapper
→ koristan kada menja nivo apstrakcije

wrapper
→ nepotreban ako samo menja ime
```

Dakle, treba razumeti kada i kako ćemo obraditi.

Ako prepoznam `unique email violation`, upotrebiću custom wrapper, dok unexpected issues puštam dalje.

## Ocena

**9.8/10**

## Korekcije i dopune

Suština je potpuno pogođena:

> Repository mapira samo ono što stvarno razume.

Na primer:

```php
catch (PDOException $e) {
    if ($this->isDuplicateEmail($e)) {
        throw new DuplicateEmailException(
            previous: $e
        );
    }

    throw $e;
}
```

Važna nijansa:

`foreign key violation` takođe nije automatski expected application problem.

Može biti:

```text
legitimni concurrent scenario
```

ali može biti i:

```text
programming bug
prekršen invariant
```

Dakle isto pravilo važi:

```text
znam precizno šta znači?
→ mapiram

ne znam?
→ propagate
```

## Interview-ready odgovor

Ne bih svaki `PDOException` pretvarao u custom exception.

Repository treba da mapira database failure samo kada pouzdano zna njegovo application značenje.

Ako prepoznam baš `UNIQUE(email)` violation, mogu ga pretvoriti u:

```php
DuplicateEmailException
```

i originalni `PDOException` sačuvati kao `previous`.

Ako je problem database outage, SQL syntax error, missing table ili neki nepoznati database failure, ne treba nagađati.

Takav exception treba pustiti dalje ka global exception handler-u, gde može biti logovan i pretvoren u bezbedan `500` response.

Centralno pravilo:

```text
poznat infrastructure failure
→ semantic mapping

nepoznat failure
→ propagation
```

---

# Question 6

## Koja je razlika između:

```php
set_error_handler(...)
```

i:

```php
set_exception_handler(...)
```

Šta svaki hvata?

Zašto možemo traditional PHP error pretvoriti u:

```php
new ErrorException(...)
```

i šta time dobijamo?

## Moj odgovor

`set_exception_handler(...)` podešava globalni exception handler, zapravo za `Exception` i `Error` koji implementiraju `Throwable`.

Ali PHP ima svoj tradicionalni error sistem, na primer:

```text
warning
notice
deprecated
user warning
user notice
```

Za njih možemo koristiti:

```php
set_error_handler(...)
```

Međutim, on ne može pouzdano preuzeti sve moguće PHP failure kategorije.

Zašto možemo u njega `new ErrorException`?

Jer je to standardna PHP klasa, a PHP već lepo prepoznaje `Throwable`, pa ako očekujemo warning možemo i njemu dati isti pipeline.

## Ocena

**9.4/10**

## Korekcije i dopune

Glavna ideja je potpuno tačna.

`set_exception_handler()` je finalna application granica za neobrađeni:

```text
Throwable
├── Error
└── Exception
```

`set_error_handler()` je namenjen tradicionalnom PHP error sistemu:

```text
E_WARNING
E_NOTICE
E_DEPRECATED
E_USER_WARNING
...
```

Poenta `ErrorException` nije samo što je to standardna PHP klasa.

Njime **normalizujemo tradicionalni PHP error u isti Throwable pipeline**.

Mentalno:

```text
E_WARNING
↓
set_error_handler()
↓
throw ErrorException
↓
Throwable pipeline
↓
local catch?
↓
global exception handler
```

Važna nijansa:

```text
ErrorException
```

uprkos imenu pripada:

```text
Exception branch
```

ne `Error` branch-u.

## Interview-ready odgovor

`set_exception_handler()` obrađuje neuhvaćeni `Throwable`, što uključuje i `Exception` i `Error`.

`set_error_handler()` služi za tradicionalni PHP error sistem, kao što su warnings, notices i deprecated upozorenja, ali ne može da preuzme baš sve fatalne PHP failure kategorije.

U error handler-u možemo tradicionalni PHP error pretvoriti u:

```php
ErrorException
```

Time ga uvodimo u isti `Throwable` pipeline kao ostale exception-e.

Tada možemo koristiti isti sistem:

```text
propagation
try/catch
global exception handler
logging
stack trace
debugging
```

---

# Trenutna ocena checkpoint-a

| Pitanje | Ocena |
|---|---:|
| 1. Bug vs Error vs Exception | 9.2/10 |
| 2. Exception propagation | 9.7/10 |
| 3. Rethrow vs wrapping/chaining | 9.5/10 |
| 4. Catch order & multi-catch | 9.3/10 |
| 5. Repository & PDO exceptions | 9.8/10 |
| 6. Error handler vs exception handler | 9.4/10 |

**Prosek dosadašnjih 6 pitanja: 9.48/10**

Najveća snaga dosadašnjeg checkpoint-a nije memorisanje PHP API-ja, već arhitektonski mentalni model:

```text
"Da li ovaj sloj zna šta da uradi?"
```

To se ponavlja kroz gotovo ceo modul:

```text
Repository
→ infrastructure meaning

Service
→ use-case / transaction responsibility

Controller
→ HTTP/UI meaning

Global Handler
→ final fallback
```

## Deferred checkpoint

Kada se vratimo na Module 11, nastavljamo direktno od:

```text
Interview Question 7/12
```

zatim:

```text
Questions 7–12
↓
5 Coding Tasks
↓
Final Module Score
```

Nema potrebe da se pitanja 1–6 ponavljaju.