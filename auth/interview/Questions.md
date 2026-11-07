# Module 10 — Authentication bez frameworka

## Interview Checkpoint — 12 pitanja

**Rezultat: 9.7/10**

Ovaj dokument sadrži:

- originalna interview pitanja,
- moje originalne odgovore,
- korekcije i dopune nakon svakog odgovora,
- ključne interview-ready zaključke.

---

# Pitanje 1/12 — Kompletan Login Flow

## Pitanje

Zamisli klasičnu Vanilla PHP aplikaciju sa session authentication-om.

Korisnik pošalje `POST /login` sa emailom i password-om.

**Objasni kompletan login flow od trenutka kada request stigne do trenutka kada korisnik postane authenticated.**

Posebno:

- email i password,
- `UserRepository`,
- password hash,
- session i session ID,
- šta na kraju čini korisnika authenticated.

## Moj odgovor

Predložio bih sledeći tok:

Kontroler prima email i password i validira podatke (još bolje da imamo poseban request): u smislu da li su validan string / email format i sl.

Zatim bi bilo dobro da LoginController ima preko DI AuthService koji bi dalje:

Našao korisnika putem emaila, pozivajuću UserRepository koji bi isključivo radio sa PDO i bazom i vraćao podatke korisnika, prema metodama...

Proverio da li user postoji i da li je aktivan?

uporedio provided password preko funkcije password_verify sa hashovanim zapisom passworda u bazi.

Ako imamo logiku za email verify? Proverio da li je email verifikovan.

Idealno postaviti password_needsrehash - zbog ažuriranja hash lozinki

I sada (ako pass verify prođe):

session_regenarate_id(true); - da bismo sesiju zaštitili od fixation

u sesiju se upisuje user_id.

Zašto samo user_id? On je nepromenjiv indikator user-a i ako bi se kasnije nešto drugo od User objekta menjalo (user modela u bazi...) na osnovu user_id preko UserRepositorijuma uvek možemo povući sveže podatke User-a na osnovu user_id. Najbolji primer u 13:05 imamo aktivnog authenticated usera, a u 13:08 admin ga suspenduje.

Na osnovu user_id u sesiji, možemo sve proveravati što se ulogovanog korisnika tiče.

Kod svih gore pomenutih provera, ukoliko nešto ne prođe, odmah radimo izlaz i error handling.

## Ocena

**9.8/10**

## Korekcija / dopuna

`UserRepository` je najbolje posmatrati kao persistence contract:

```php
interface UserRepository
{
    public function findByEmail(string $email): ?User;
}
```

Tek konkretna implementacija:

```php
final class PdoUserRepository implements UserRepository
```

zna za PDO, SQL i tabelu.

Repository bi idealno vraćao:

```text
User | null
```

a ne proizvoljan DB array.

Standardni flow bih izrazio:

```text
Request validation
↓
LoginController
↓
AuthService
↓
normalize email
↓
UserRepository::findByEmail()
↓
password_verify()
↓
account eligibility
↓
optional password_needs_rehash()
↓
session_regenerate_id(true)
↓
session user_id
```

Email verification zavisi od application policy-ja.

Najvažnija nijansa:

```text
password_verify() === true
```

znači da su credentials validni.

User stvarno postaje authenticated tek kada uspostavimo auth state:

```php
session_regenerate_id(true);

$_SESSION['user_id'] = $user->id();
```

---

# Pitanje 2/12 — Password Hash API

## Pitanje

Objasni razliku između:

```php
password_hash()
password_verify()
password_needs_rehash()
```

i zašto ne možemo pri login-u jednostavno ponovo hashirati uneti password i uraditi `===` poređenje sa hash-em iz baze.

## Moj odgovor

password_hash() - služi nam da hashuje plain text validirane user lozinke, koji kasnije smeštamo u bazu. Ova metoda daje salted hash svaki put, te je gotovo nemoguće da nad istim plain textom dobijemio uvek isti hash sa pozivanjem ove metode, zato bi provera === pala svaki put.

Iz tog razloga baš i koristimo -> password_verify() - metoda specijalno izrađena da proveri prilikom logina korisnički unos sa hashom u bazi.

password_needs_rehash() - metoda koja nam omogućava postepeni upgrade hash-eva u bazi bez forsiranja svih korisnika odjednom.

Ovo je dobro i poželjno zbog sledećih scenarija:

korisnik se registrovao pre tri godine  
password hash je napravljen sa starijim parametrima  
danas želimo jači hashing setup

## Ocena

**9.9/10**

## Korekcija / dopuna

`password_hash()`, `password_verify()` i `password_needs_rehash()` su PHP funkcije.

`password_hash()`:

```text
plain password
↓
salted password hash
↓
database
```

Isti password pri dva poziva može dati različite hash stringove zbog random salt-a.

Zato je pogrešno:

```php
password_hash(
    $providedPassword,
    PASSWORD_DEFAULT
) === $storedHash;
```

Ispravno:

```php
password_verify(
    $providedPassword,
    $storedHash
);
```

Stored hash već sadrži informacije potrebne za proveru, uključujući algoritam, parametre i salt.

Rehash ide nakon uspešnog verify-a:

```text
password_verify() ✅
↓
password_needs_rehash()
↓
password_hash()
↓
update stored hash
```

---

# Pitanje 3/12 — Session ID, Fixation i Hijacking

## Pitanje

Objasni šta se dešava kada nakon uspešnog login-a uradimo:

```php
session_regenerate_id(true);

$_SESSION['user_id'] = $user->id();
```

Objasni:

- šta je session ID,
- gde se nalazi session ID, a gde `user_id`,
- zašto regenerišemo session ID,
- razliku između session fixation i session hijacking.

## Moj odgovor

session_regenerate_id(true) nam ugrubo receno sluzi da promenimo session ID nakon uspesnog login attempta a pre stavljanja user_id u session. true znači da PHP treba da obriše staru server-side session data povezanu sa starim ID-em.

session ID je credential like secret i tako ga i tretiramo jer je on indikator odredjene sesije po kome server nju prepoznaje. Session ID se cuva na serveru a salje se i u session cookie, kako bi ga browser stalno vracao na server radi prepoznavanja. Sa druge strane user_id je User persistance u bazi na osnovu koga trazimo usera i fakticki jedini nepromenjivi podatak modela (bar sam user ne moze da ga menja).

Regenerate ID operaciju radimo kao zastitu od session fixation-a gde napadac zna session id i uspeva da natera zrtvu da ga koristi. Hi-jacking je drugaciji tip napada, gde napadac ukrade session id zrtve i lazno se predstavi.

Dakle kod fixation je situacija:

```text
Attacker zna session ID AAA
↓
victim koristi session AAA
↓
victim se loginuje
↓
user_id = 15
↓
attacker i dalje zna AAA
```

Dok:

```text
SESSION HIJACKING
→ napadač ukrade već postojeći validan authenticated session ID
```

## Ocena

**9.7/10**

## Korekcija / dopuna

Precizniji model:

```text
BROWSER
→ session cookie
→ PHPSESSID=AAA

SERVER
→ session data povezana sa AAA
→ user_id = 15
```

Browser čuva session ID u cookie-ju.

Server čuva session podatke povezane sa tim ID-em.

`user_id` nije persistence sam po sebi, nego stabilna identity reference / primary key korisnika.

Fixation:

```text
attacker zna ID PRE authentication-a
↓
victim koristi taj ID
↓
victim se loginuje
↓
isti ID postaje privileged
```

Hijacking:

```text
user je već authenticated
↓
attacker ukrade authenticated session ID
↓
predstavlja se kao victim
```

`session_regenerate_id()` prvenstveno štiti od fixation-a.

---

# Pitanje 4/12 — Current User Resolution

## Pitanje

Pretpostavimo da posle login-a u session čuvamo samo:

```php
$_SESSION['user_id'] = 17;
```

Kako bi implementirao:

```php
$auth->id();
$auth->user();
$auth->check();
$auth->guest();
```

Šta ako user više ne postoji ili je suspendovan?

Kako izbeći više istih SQL query-ja tokom jednog request-a?

## Moj odgovor

Napravio bih AuthService sa ovim metodama.

id() proverava i vraca ako ima 'user_id' iz sesije.  
Naravno, pozeljno je imati dobar UserRepository (idealno interface, kog implementira PdoUsrrRepository), koji ce da radi sa bazom, koji ima metode poput findById i findByEmail.

U ->user() metodi bih, da bih izbegao vise upita po jednom requestu, prvo uradio kesiranje, pomocu 2 private promenjive (koje stoje naravno na svom mestu, na vrhu klase) na primer $currentUser: ?User i $resolvedUser: bool, currentUser cuva podatke o korisniku a resolvedUser nam kaze da li je vec izvrsen upit i pokusano da se dobije user? Tako na primer mozemo imati: "pokusao sam da nadjem korisnika ali ga nema i on je null".

Zatim dalje se proverava $this->id(), pa se poziva UserRepositori i trazi i proverava user pomocu ->findById, ako je null return null; ako imamo user-a, proveravamo da li je aktivan? Ako nije - opet null + brisanje userId iz sesije obavezno, a ako jeste vracamo User. Ps. Idealno bi bilo i cuvati auth_version kako u bazi tako i u sesiji pa i to proveriti radi invalidacije svih sesija nakon promene lozinke. Na kraju azuriramo resolved i current.

check() bi bila jednostavna provera $this->user !== null i vraca da li imamo authenticated usera, ovo je dobro za middleware recimo.

-guest() suprotno od check !$this->check() koja nam potvrdjuje da je korisnik gost a ne auth user...

## Ocena

**9.8/10**

## Korekcija / dopuna

Dobar cache model:

```php
private ?User $currentUser = null;
private bool $userResolved = false;
```

Time razlikujemo:

```text
userResolved = false
→ još nismo pokušali

userResolved = true + currentUser = null
→ pokušali smo, nema validnog user-a

userResolved = true + currentUser = User
→ user resolved
```

Kod stale session state-a:

```text
session user_id
↓
findById()
↓
User missing / suspended
↓
forget user_id
↓
guest
```

`check()` treba sintaksno:

```php
public function check(): bool
{
    return $this->user() !== null;
}
```

a:

```php
public function guest(): bool
{
    return !$this->check();
}
```

`auth_version` je odličan napredni dodatak za globalnu invalidaciju session-a.

---

# Pitanje 5/12 — Session Auth vs Remember Me

## Pitanje

Objasni razliku između session authentication-a i Remember Me authentication-a.

Posebno:

- šta je u običnoj session,
- šta je u Remember Me cookie-ju,
- zašto cookie ne sme sadržati password ili samo `user_id`,
- selector i validator,
- šta se čuva u bazi,
- kako se ponovo uspostavlja normalna session.

## Moj odgovor

U obicnoj sesiji se nalazi najcesce samo user_id kada je rec o autentifikaciji, tj najbolja je praksa da smestamo samo user_id a ne ceo User objekat koji moze biti promenjiv. Tada, server samo salje session cookie koji sadrzi name=id i cookie zivi u browseru tokom jedne browser sesije mada na serveru session life cycle moze biti drugaciji.

Buduci da je HTTP stateless, Remember Me cookie nam pomaze za trajniju sesiju.

Medjutim, jako je losa praksa da se u njega smesti user_id jer je cookie user controlled i moze lako da se promeni i da se pristupi rudjem nalogu.

Zato je najbolje imati posebnu remember_me tabelu gde bismo cuvali user_id, selector-token pomocu kog trazimo remember u bazi (unique constraint), validator-token - hashovani token po kom validiramo zahtev, expires_at -> vreme trajanja (u zavisnoti od politike - najcesce do 30 dana) i kada je kreiran - created_at.

Poenta je da remember cookie sadrzi kao vrednost 'selector:validator' te kada server primi zahtev, nadje po selector (parsiramo vrednost) stored remember token, validira validator deo koji je u cookie plan text a u bazi njegov hash sa hash_equals(), ne sa ===, a onda proveri do kada traje pomocu expires_at jer vrednost iz cookie je ipak non trusted source (server - source of truth), nakon svih provera i poklapanja (naravno proveravamo i da li token ima validan format pre sql), upisujemo user_id u sesiju i user je na taj nacin authenticated.

Ps. Arhitektonska pozeljna odluka je i da se razmisli o brisanju remember tokena ukoliko dodje do promene password-a, a takodje razmisliti sta u slucaju da klijent salje nevalidan token.

## Ocena

**9.8/10**

## Korekcija / dopuna

Session cookie tipično izgleda:

```text
PHPSESSID=abc123
```

`PHPSESSID` je cookie name, a `abc123` session ID.

Remember Me nije trajna session.

Preciznije:

```text
Remember Me
→ persistent credential
→ koji može ponovo uspostaviti normalnu session
```

Selector:

```text
→ lookup key
```

Validator:

```text
→ secret proof
```

Cookie:

```text
selector:validator
```

DB:

```text
user_id
selector
validator_hash
expires_at
created_at
```

Nakon uspešne validacije bolje je pozvati:

```php
$auth->login($user);
```

umesto ručnog upisivanja `user_id`, jer `login()` centralizuje session regeneration, state i current-user cache.

---

# Pitanje 6/12 — Password Reset

## Pitanje

Objasni kompletan password reset flow.

Posebno:

- account enumeration,
- generisanje i čuvanje tokena,
- expiry,
- single-use,
- zašto SHA-256 za token, a ne za password,
- šta invalidirati nakon reset-a.

## Moj odgovor

Proverimo da li email tj user sa tim emailom postoji. Ako ne -> redirect sa generic porukom tipa 'if this account exists the email with link was sent'. Nikako ne otkrivamo da li email postoji ili ne jer ne zelimo potencijalnom napadacu da olaksamo posao.

Za generisanje tokena, nema potrebe za password_hash, jer je to spora operacija i utice na koriscenje memorije, a drugo i vaznije jeste da nam ne treba salted hash pa opet _verify. Mozemo hash('sha256', $token) a token generisati pomocu bin2hex(random_bytes(32)), cime je veoma mala verovatnoca da dodje do kolizije.

Token se cuva u bazi, idealno u posebnoj reset_password_tokens tabeli, zajedno sa foreign user_id i expires_at da bismo ga povezali sa korisnikom i dali mu vremensko ogranicenje.

Postoje ovo veoma osetljiva operacija zbog potencijalnog napada, moramo imati kratko vremensko ogranicenje tokena i da obezbedimo da se moze iskoristiti samo jednom. Dakle, brisanje odmah nakon promene passworda ili update ukoliko korisnik posalje novi zahtev (na primer unique user_id pa on duplicate update - upsert).

Dakle:

Normalizovati email - trazimo usera  
Nema - generic poruka return.  
Zatim generisemo token i smestamo u bazu sa ogranicenjem na primer na 30min.  
Bolje sa upsert da bismo odmah invalidirali prethodni neiskorisceni.  
Ista generic poruka da je link poslat.

Korisniku se salje nehashiran token tj link sa baseUrl + token nehashiran, on unese novi password i confirmation.

Sada, proveravamo token i validiramo password i password confirmation.  
Token neispravan? Return error page.  
Ako je ispravan i nije istekao radimo reset passworda i brisemo token iz baze.

Ovde je vazno razmisliti o tome sta sa sesijama i remember me user-a?

Idealno resenje je invalidacija svih sesija i remember-a jer osim zaborava, razlog promene lozinke moze biti i sumnja da je nalog korumpiran.

## Ocena

**9.8/10**

## Korekcija / dopuna

Glavni razlog za `random_bytes(32)` nije samo mala verovatnoća kolizije već:

```text
visoka entropija
+
praktično nemoguće pogađanje
```

U DB ne čuvamo plaintext token nego:

```php
$tokenHash = hash('sha256', $token);
```

Dakle:

```text
email/browser
→ plaintext token

database
→ token_hash
```

SHA-256 je ovde dovoljan jer je token već cryptographically random high-entropy secret.

Password je human-generated i predvidljiviji, zato zahteva spor `password_hash()`.

Uspešan reset:

```text
BEGIN
↓
password_hash(new password)
↓
update password hash
↓
invalidate reset token
↓
optional remember-token invalidation
↓
COMMIT
```

`auth_version` može pomoći za invalidaciju svih postojećih sessions.

---

# Pitanje 7/12 — Authenticated, Verified, Active, Authorized

## Pitanje

Objasni razliku između:

```text
authenticated
email verified
account active
authorized
```

Daj primer korisnika koji je authenticated i verified, ali ipak dobija `403 Forbidden`.

Gde se uklapaju:

```text
AuthMiddleware
VerifiedMiddleware
authorization / Policy
```

## Moj odgovor

authenticated -> ko si ti? Da li je user ulogovan ili ne.

email verified -> da li je user i vlasnik tj da li kontrolise email koji stoji u bazi kao njegov?

account active -> da li je user aktivni user tj sa li mu je nalog aktivan a ne disabled ili suspended u zavisnosti od politike app.

authorized -> da li user moze izvrsiti odredjenu radnju?

User je ulogovan, ima email verified i aktivan nalog Ali, dobijq forbiden 403 jer na primer pokusava da obrise booking drugog usera a pri tom nije admin.

Request:

1. AuthMiddleware -> prvo gledamo da li je authenticated.
2. Email verified?
3. Admin/supo/guest middleware -> autorizacija u zavisnosti od app

## Ocena

**9.7/10**

## Korekcija / dopuna

Odličan 403 primer:

```text
User #17
authenticated ✅
verified ✅
active ✅

DELETE Booking #500
Booking pripada User #42
User #17 nije admin
↓
403 Forbidden
```

Pipeline:

```text
Request
↓
AuthMiddleware
↓
VerifiedMiddleware
↓
Authorization / Policy
↓
Controller
```

`guest` nije authorization middleware.

```text
AuthMiddleware
GuestMiddleware
→ authentication state
```

dok:

```text
AdminMiddleware
SupplierMiddleware
Policy
→ authorization
```

Account active proveru možemo centralizovati u current-user resolution-u.

---

# Pitanje 8/12 — Zašto više servisa?

## Pitanje

Zašto je bolje imati:

```text
AuthService
RegistrationService
PasswordResetService
EmailVerificationService
RememberMeService
```

nego jednu ogromnu `Auth` klasu?

Objasni kroz:

- SRP,
- testabilnost,
- DI,
- transaction boundaries,
- održavanje.

Gde pripada transaction boundary kod password reset-a?

## Moj odgovor

Zato sto ne zelimo da nam AuthService postane God object. To nije dobro za odrzavanje, testiranje i citljivost koda. Uvek je bolje razdvojiti po slojevima odgovornosti. Najjaci primer je eventualna implementacija nove funkcionalnosti, pa onda kopaj po kodu i trazi pa prepravljaj.

Dakle, Single responsible services.

Za password reset transakciju bih pozvao u PasswordResetService pa u boundary stavio

Update pass hash  
Invalidate token  
Invalidate rememberme

## Ocena

**9.3/10**

## Korekcija / dopuna

SRP:

```text
PasswordResetService
→ menja se zbog reset flow-a

RememberMeService
→ menja se zbog persistent auth-a
```

Testabilnost:

```text
PasswordResetService
+ FakeUserRepository
+ FakeResetTokenRepository
```

može se testirati izolovano.

DI je čistiji jer svaki service dobija samo dependencies koje zaista koristi.

Transaction boundary pripada:

```text
PasswordResetService
```

jer on poznaje ceo use case:

```text
BEGIN
↓
update password hash
↓
invalidate reset token
↓
optional invalidate remember tokens
↓
COMMIT
```

Repositories ne treba sami da određuju transaction boundary.

Oni treba da učestvuju koristeći isti PDO connection.

---

# Pitanje 9/12 — Session Cookie, Session i Remember Me Cookie

## Pitanje

Objasni razliku između:

```text
session cookie
session
Remember Me cookie
```

Gde se svaki nalazi?

Objasni i razliku između:

```text
Secure
HttpOnly
SameSite
```

## Moj odgovor

session cookie je cookie sesije, koji server salje browser-u i sadrzi nesto poput PHPSESID=a123vc45...

session je sesija koja se cuva na serveru (gde tacno, zavisi od konfiguracije)

Remember Me cookie je cookie koji mi pravimo i u njega smestamo remember token koji validiramo kasnije.

Cookies se cuvaju u browseru i njih on salje automatski serveru svakim requestom.

Kada session cookie stigne na server, server uzima id sesije iz njega i tako poveze koji su podaci te k9nkretne sesije.

Secure -> slanje cookies samo preko https sigurne veze.  
HttpOnly -> odredjuje da li ce cookie biti vidljiv i citljiv java script-u ili samo kroz HTTP protokol  
Samesite resava problem cross site

## Ocena

**9.6/10**

## Korekcija / dopuna

Precizno:

```text
Browser
→ PHPSESSID=abc123

Server
→ session data povezana sa abc123
```

Remember Me cookie:

```text
persistent credential
→ može obnoviti novu normalnu session
```

Browser ne šalje bukvalno svaki cookie uz svaki request.

Šalje odgovarajuće cookies u skladu sa:

```text
domain
path
Secure
SameSite
```

`Secure`:

```text
→ cookie samo preko HTTPS
```

`HttpOnly`:

```text
→ JavaScript ne može direktno čitati cookie kroz document.cookie
```

`SameSite`:

```text
→ kontroliše cross-site slanje cookie-ja
→ pomaže protiv CSRF napada
```

Nijedan od ova tri atributa nije zamena za druga dva.

---

# Pitanje 10/12 — Logout

## Pitanje

Objasni razliku između:

```php
unset($_SESSION['user_id']);
```

i:

```php
$_SESSION = [];
session_destroy();
```

Kada bi koristio koji pristup?

Šta sa:

- session ID-em,
- session cookie-jem,
- Remember Me tokenom,
- `POST /logout` vs `GET /logout`?

## Moj odgovor

Prvi pristup bih izabrao ako u sesiji ostaju neki podaci nevezani za autentifikaciju kao npr prefferences, a drugi ako nema nista drugo osim auth-a.

Ipak, samo session_destroy bih prosirio, tj pre njega bih:

Brisanje tj podesavanje expires cookir-a u proslost za session i remember cookie, kao i brisanje remember tokena iz baze (da ne bi bio efekat: user ne moze da se izloguje).

Ako bismo bas praznili celu sesiju onda $_SESSION = [];  
Jer sam _destroy() ne mora odmah izbrisati podatke.

GET  
→ trebalo bi da bude safe/idempotent retrieval semantics

logout  
→ menja authenticated state

I zayo za logout prirodnije je POST.

Na kraju: Ne mora, ali dobra praksa -> regenerate_id.

## Ocena

**9.7/10**

## Korekcija / dopuna

Partial logout:

```text
remove auth state
→ zadrži cart/preferences/locale itd.
```

Full destruction:

```php
$_SESSION = [];
session_destroy();
```

`session_destroy()` uklanja persisted server-side session, ali ne prazni nužno trenutni `$_SESSION` array u memoriji.

Ako potpuno gasimo session:

```text
destroy server-side state
+
expire session cookie
```

Ako postoji Remember Me:

```text
revoke DB token
+
delete remember cookie
+
remove session auth
```

inače user može odmah biti automatski ponovo loginovan.

Kod partial logout-a ima smisla:

```php
session_regenerate_id(true);
```

nakon uklanjanja auth state-a.

`GET` treba da bude **safe**.

Logout menja state, zato je:

```text
POST /logout
```

prirodniji i omogućava normalnu CSRF zaštitu.

Idempotent i safe nisu isto.

---

# Pitanje 11/12 — Registration Race Condition

## Pitanje

Dva request-a istovremeno pokušavaju da registruju isti email.

Imamo:

```php
if ($users->findByEmail($email) !== null) {
    // email already taken
}
```

Zašto to nije dovoljno?

Objasni:

- race condition,
- `UNIQUE(email)`,
- oba request-a prvo vide da email ne postoji,
- obradu drugog neuspešnog INSERT-a.

## Moj odgovor

Ova provera nije dovoljna zbog race condition-a, tj kada dva razlicita requesta pokusavaju da u isto vreme upisu u bazu (u ovom slucaju isti email na primer).

Bez constraint-a UNIQUE na email, moglo bi se desiti 2 ista upisa. Tako je baza "poslednja linija odbrane".

Bez unique: korisnik A procita da nema a u isto vreme korisnik B procita nema i upis oba a sa unique, B request dobija gresku.

Sql cak vraca greske po odredjenim kodovima, tako da bih pored ove provere koristio UNIQUE na email, a ako dodje bas do ove greske, obradio bih je i poslao nazad poruku da je email already taken, a ako bi se desila neka druga greska pri upisu -> generic message.

## Ocena

**9.8/10**

## Korekcija / dopuna

Tačan race:

```text
A → SELECT → nema user-a
B → SELECT → nema user-a

A → INSERT → success
B → INSERT → UNIQUE violation
```

Application check:

```text
→ bolji UX
```

Database constraint:

```text
→ garantuje invariant
```

Potrebna su oba.

Arhitektonski je lepo da persistence sloj mapira konkretan DB duplicate error:

```text
PDOException / SQLSTATE
↓
PdoUserRepository
↓
DuplicateEmailException
↓
RegistrationService / Controller
↓
"Email is already taken."
```

Ostale DB greške ne treba predstavljati korisniku kao duplicate email.

---

# Pitanje 12/12 — Kompletna Auth Arhitektura

## Pitanje

Objasni odnos i odgovornosti:

```text
Request
Controller
AuthService
RegistrationService
UserRepository
Session
Middleware
RememberMeService
PasswordResetService
EmailVerificationService
```

I zašto Laravel auth više nije „magija“ kada razumeš sistem bez frameworka?

## Moj odgovor

Request

Controller -> prima http request i poziva dalje servis a nakon toga vraca output. Ne mora da zna sta tacno servis radi, samo prosledi data i dobije sta treba vratiti.

AuthService -> Servis zaduzen za autentifikaciju korisnika. Recimo da sadrzi metode attempt() login i logout. On orkestrira ove radnje. On ne razmislja o sql upitima, to prepusta Repository-u, on brine o glavnoj logici zadatka.

RegistrationService -> Servis zaduzen za registraciju - slicno se ponasa kao i svaki servis ali ovde konkretno da auth ne bi bio God object, logiku registracije izmestamo.

UserRepository - idealno, ovo je interface koji nalaze metode koje se imaju implementirati u repozitorijumima koji ga implementiraju. Repository direktno radi sa bazom sql upiti + pdo.

Session -> bi bila jedna lepa klasa koja radi sa sesijom da ne moeam svuda kroz app da ispisujemo $_SESSION['user_id'], lepo koristimo metode ove klase koju kroz konstruktor (DI) primamo u ostale klase.

Middleware -> klasa zaduzena za proveru pre nego request stigne u kontroler. Na primer, ako user nije ulogovan, ne moze da uredjuje svoj profil a ako je ulogovan, ne bi trebalo da ide na login page, tu middlewares sprecavaju da Controller uopste bude pozvan.

RememberMeService -> orkestrira cuvanje remember tokena i cookie pa kasnije uspostavljanja sesije kada se vrati cookie sa remember.

PasswordResetService -> orkestrira password reset logiku.

EmailVerificationService -> orkestrira email verification.

## Ocena

**9.4/10**

## Korekcija / dopuna

### Request

Request sloj treba da:

```text
primi HTTP input
↓
validira / normalizuje ono što pripada request validation-u
↓
napravi DTO / validated data
```

Na primer:

```text
LoginRequest
RegisterRequest
ResetPasswordRequest
```

### Controller

Tačno:

```text
HTTP boundary
↓
poziva odgovarajući service
↓
vraća Response / redirect
```

Controller ne treba da zna SQL, hashing ili detalje session storage-a.

### AuthService

```text
attempt()
login()
logout()
check()
guest()
id()
user()
```

Orkestrira current authentication lifecycle.

Ne radi SQL direktno.

### RegistrationService

```text
registration use case
↓
email normalization
↓
uniqueness policy
↓
password hashing
↓
UserRepository
```

Izdvojen je da AuthService ne postane God Object.

### UserRepository

Važna korekcija:

```text
UserRepository interface
→ persistence contract
```

Ne radi sam interface direktno sa PDO.

Tek:

```text
PdoUserRepository
→ PDO + SQL + mapping
```

implementira contract.

### Session

Wrapper oko PHP session mehanizma:

```text
get
put
forget
regenerate
```

sprečava rasipanje:

```php
$_SESSION[...]
```

po celoj aplikaciji.

### Middleware

Stoји pre Controller-a:

```text
Request
↓
Middleware
↓
Controller
```

Može prekinuti pipeline.

Na primer:

```text
AuthMiddleware
GuestMiddleware
VerifiedMiddleware
```

### RememberMeService

Orkestrira:

```text
issue persistent token
restore authentication
rotate token
revoke token
```

Nakon validnog tokena koristi:

```php
$auth->login($user);
```

da ponovo uspostavi normalnu session.

### PasswordResetService

Orkestrira:

```text
reset token lifecycle
password validation
new password hash
token invalidation
security invalidation
```

I prirodno poseduje transaction boundary za:

```text
update password
+
invalidate reset token
```

### EmailVerificationService

Orkestrira:

```text
issue verification token
verify token
mark email verified
resend / rotate token
```

---

# Zašto Laravel Auth više nije „magija“?

Kada napišemo:

```php
Auth::attempt($credentials);
```

sada znamo da ispod konceptualno postoji:

```text
credential input
↓
User provider / repository lookup
↓
password verification
↓
account checks
↓
session authentication state
```

Kada napišemo:

```php
auth()->user();
```

mentalno znamo:

```text
session identity
↓
user provider
↓
current User
```

Kada koristimo:

```text
auth middleware
guest middleware
verified middleware
```

znamo da je to request pipeline koji odlučuje da li Controller sme biti pozvan.

Laravelov:

```text
session guard
user provider
Hash facade
password broker
verification notifications
middleware
```

nisu magija.

To su framework implementacije istih odgovornosti koje smo ručno napravili u Vanilla PHP-u.

Mentalna mapa:

```text
Request
↓
Middleware
↓
Controller
↓
Service
↓
Repository / Session / Token infrastructure
↓
Database
↓
Response
```

Framework uklanja boilerplate.

Ne menja osnovne principe.

---

# Konačni Interview Checkpoint

## Rezultat

**9.7/10**

## Najjače oblasti

- session authentication mental model,
- password hashing lifecycle,
- fixation vs hijacking,
- current-user resolution,
- Remember Me selector/validator model,
- password reset token security,
- authentication vs authorization,
- race conditions i database invariants,
- service/repository podela odgovornosti.

## Najvažnije sitnice za dodatno poliranje

### 1. Interface vs implementation

```text
UserRepository
→ contract

PdoUserRepository
→ PDO implementation
```

### 2. Session ID vs user ID

```text
session ID
→ identifikuje session

user_id
→ identifikuje User-a
```

### 3. Remember Me

```text
nije trajna session

nego:
persistent credential
→ restore normal session
```

### 4. Safe vs idempotent HTTP metoda

```text
GET
→ treba da bude safe

logout
→ menja state
→ POST
```

### 5. Authentication state

```text
password_verify()
→ credentials valid

session user_id
→ authenticated state established
```

---

# Završni mentalni model

```text
REGISTER
↓
validate
↓
password_hash()
↓
UserRepository
↓
User
```

```text
LOGIN
↓
find user
↓
password_verify()
↓
account eligibility
↓
session_regenerate_id()
↓
session user_id
```

```text
NEXT REQUEST
↓
session cookie
↓
session user_id
↓
UserRepository::findById()
↓
current User
```

```text
PROTECTED ROUTE
↓
AuthMiddleware
↓
VerifiedMiddleware
↓
Policy / Authorization
↓
Controller
```

```text
REMEMBER ME
↓
persistent token
↓
validate
↓
AuthService::login(User)
↓
new normal session
```

```text
PASSWORD RESET
↓
random token
↓
hash in DB
↓
short expiry
↓
single-use
↓
new password_hash
↓
invalidate auth credentials as policy requires
```

```text
LOGOUT
↓
revoke persistent auth
↓
remove session auth
↓
rotate/destroy session
↓
guest
```

**Module 10 Interview Questions: 12/12 completed.**