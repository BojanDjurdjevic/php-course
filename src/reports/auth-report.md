# Code review: mini Auth sistem u PHP-u

**Autor pregleda:** Astra  
**Datum:** 4. novembar 2026.  
**Predmet:** registracija, prijava, odjava, reset lozinke, „remember me“ i potvrda email adrese  
**Vrsta pregleda:** read-only analiza aplikacije; jedini nov fajl je ovaj izveštaj  
**Nivo projekta:** jednostavan OOP PHP sa eksplicitnim `switch` rutiranjem

---

## 1. Zaključak i način čitanja

Ovo je dobra osnova za učenje autentikacije: odgovornosti su uglavnom razdvojene, SQL parametri se vezuju, lozinke se heširaju PHP funkcijama, a oporavak naloga već uzima u obzir transakcije i opoziv starih sesija. Posebno su vredni `auth_version`, jednokratni tokeni i uslovna rotacija „remember me“ tokena.

**Sistem ipak još nije spreman za javnu produkciju.** Postoje konkretni bezbednosni propusti i nedovršeni tokovi: nema ograničavanja pokušaja prijave, konfiguracija sesije se čita prerano, potvrda lozinke završava u poruci o grešci, slanje emaila nije implementirano u pregledanom toku, a paralelna prijava i reset mogu da ponište očekivani efekat promene lozinke.

**Nije potvrđeno bezuslovno kritično preuzimanje proizvoljnog naloga.** Postoji potencijalno kritičan scenario ako web-server izlaže logove sa važećim reset tokenima. To je uslovni nalaz, jer konfiguracija servera nije deo dozvoljenog obima pregleda. Važno je razlikovati „kod dopušta opasan scenario“ od „dokazano je da je taj scenario dostupan preko ovog servera“.

MVC i middleware nisu uslov da se ovi problemi reše. Sve prve popravke moguće su uz postojeće klase i `switch`. Njihovo odsustvo **nije razlog za umanjenje ocene**.

### Sadržaj

1. Zaključak i način čitanja
2. Obim, metod i ograničenja
3. Kako sistem trenutno radi
4. Šta je dobro urađeno i zašto
5. Pregled prioriteta
6. Bezbednosni nalazi
7. Funkcionalne greške i pouzdanost
8. Baza, sesije i dodatna poboljšanja
9. Refaktor bez uvođenja MVC-a
10. Plan provera posle budućih izmena
11. Završna ocena
12. Inventar pregledanih fajlova

Putanje i brojevi redova odnose se na stanje koda u trenutku pregleda. Primeri popravki su obrazovni predlozi: **nisu primenjeni na aplikaciju**. SQL primeri zahtevaju prilagođavanje stvarnoj šemi baze.

---

## 2. Obim, metod i ograničenja

Pregledani su koreni `index.php`, svi PHP fajlovi u `src/` i svi PHP fajlovi u `views/`: ukupno **44 PHP fajla**. U `src/private/` provereni su i postojeći log fajlovi; sadržaj tokena nije prenet u izveštaj. `log.txt` je prazan.

Nisu pregledani zadaci, lekcije, `vendor/`, `includes/`, `.env`, Composer konfiguracija, migracije, drugi direktorijumi, konfiguracija web-servera ni stvarna baza. Zato se ne tvrdi da su provereni TLS, HTTP zaglavlja koja eventualno postavlja server, spoljne biblioteke, DB indeksi ili zajednički HTML header. View fajlovi uključuju `includes/header.php`, ali njegov sadržaj namerno ostaje van pregleda.

### Šta je stvarno provereno

- Statički su praćeni ulazi iz `$_GET`, `$_POST`, `$_COOKIE` i sesije do validacije, SQL-a i HTML izlaza.
- `php -l` je izvršen nad svih 44 PHP fajla: **44 prolaze, 0 sintaksnih grešaka**.
- PHP CLI okruženje za izolovane provere bilo je **PHP 8.3.30**. To ne dokazuje verziju PHP-a koju koristi web-server.
- U memoriji su provereni validator, pogrešna session direktiva, prerano podešavanje `Secure` atributa i kontrolisano preplitanje reseta i rehasha. Skripte su prosleđene PHP-u kroz standardni ulaz i nisu sačuvane u projektu.
- Za proveru sesije funkcije koje bi pokrenule sesiju ili menjale njen ID zamenjene su bezopasnim funkcijama u odgovarajućem namespace-u. Nisu pravljeni session fajlovi.
- Za proveru preplitanja korišćen je memorijski repozitorijum. To potvrđuje ponašanje servisne logike pri zadatom redosledu, ali nije test paralelizma stvarnog MySQL servera.

Nije pokretan `index.php`, aplikacioni HTTP server, registracija stvarnog korisnika, slanje emaila ili test koji piše u bazu. Nije pravljen commit.

**Zašto je ovo važno:** uspešna provera sintakse dokazuje da PHP razume fajl. Ne dokazuje da postoji svaki `require` cilj, da je promenljiva definisana, da je HTTP odgovor ispravan ili da dva istovremena zahteva ne narušavaju pravila sistema.

### Oznake nalaza

| Oznaka | Značenje |
| --- | --- |
| Visok | Moguće ugrožavanje naloga ili važna prepreka bezbednom objavljivanju. |
| Srednji | Konkretno curenje, kvar toka ili rizik uz dodatne uslove. |
| Nizak | Održavanje, upotrebljivost ili dodatno ojačavanje zaštite. |
| Uslovni | Posledica zavisi od šeme, konfiguracije ili preplitanja zahteva. |

Prioritet nije isto što i težina: nedostajući view nije „kritična ranjivost“, ali je brza i neophodna popravka.

---

## 3. Kako sistem trenutno radi

Glavni tok je razumljiv:

```text
HTTP zahtev
    ↓
index.php: sastavljanje objekata + izbor rute
    ↓
Controller: CSRF, unos, rezultat i preusmeravanje
    ↓
Request / Validator: provera podataka i pravljenje DTO objekta
    ↓
Service: pravila autentikacije
    ↓
Repository: SQL i pretvaranje reda iz baze u objekat
    ↓
Session / View: stanje prijave i prikaz korisniku
```

Ovo već predstavlja razdvajanje slojeva. Ne moraš da ga nazoveš punim MVC-om da bi imalo smisla.

| Operacija | Trenutni tok |
| --- | --- |
| Registracija | CSRF → validacija → provera emaila → hash lozinke → korisnik → verifikacioni token. |
| Prijava | CSRF → validacija → aktivan korisnik → lozinka → potvrđen email → rehash po potrebi → nova session identifikacija. |
| Odjava | CSRF → opoziv trenutnog remember tokena → uništavanje sesije → redirect. |
| Remember me | Parsiranje cookie-ja → provera hasha i roka → stanje korisnika → uslovna rotacija → prijava. |
| Reset | Zahtev za link → limiti → token; zatim validacija lozinke → zaključan token → promena lozinke i opoziv autentikacije. |
| Email verify | GET prikazuje potvrdu; POST uz CSRF troši token i označava email potvrđenim. |

U ovom pregledu najviše pažnje posvećeno je prelazima između koraka. Upravo tu nastaje većina pronađenih problema.

---

## 4. Šta je dobro urađeno i zašto

### 4.1. Poslovna pravila nisu razbacana po HTML-u

Servisi u `src/Auth/` donose odluke o prijavi, resetu i tokenima, dok repozitorijumi poznaju SQL. Na primer, `AuthService` zavisi od `UserRepository` interfejsa, a ne od konkretnog PDO upita.

To je korisno i u malom projektu: servis se može proveriti uz memorijski repozitorijum, bez baze. Upravo ta osobina omogućila je izolovanu proveru problema sa rehashom. Ovo nije dekorativni OOP, nego stvarna korist od razdvajanja odgovornosti.

### 4.2. DTO objekti i enum tipovi čine rezultate jasnijim

`UserLoginData`, `UserRegisterData` i `PasswordResetData` su `readonly`. Jednom formirane podatke nije moguće neprimetno promeniti kroz javna svojstva. `LoginResult` razlikuje uspeh, neispravne kredencijale i nepotvrđen email bez proizvoljnih stringova i „magičnih brojeva“.

`UserStatus` i metode `User::isActive()` / `isEmailVerified()` daju čitljiva pravila. DTO ipak nije automatski dokaz validnosti: javni konstruktor može dobiti proizvoljan string. U postojećem HTTP toku validaciju obezbeđuju request klase.

### 4.3. SQL se izvršava sa vezanim parametrima

U svim pregledanim repozitorijumima korisničke vrednosti prolaze kroz `prepare()` i `execute()`. Nema pronađenog spajanja emaila, lozinke ili tokena u SQL tekst. Dinamički dodatak `FOR UPDATE` potiče iz internog boolean argumenta, ne iz korisničkog unosa.

Time je odvojena struktura upita od podataka. Napadačev navodnik u emailu ne postaje SQL operator. Ne tvrdim da je pregledana cela infrastruktura bez SQL injection rizika, već da je ovaj deo koda dosledno dobro napisan.

`Database.php:24–33` koristi `utf8mb4` i PDO exceptions. To pomaže korektnom radu sa Unicode podacima i sprečava tiho ignorisanje SQL grešaka.

### 4.4. Za lozinke se koriste odgovarajuće PHP funkcije

`RegistrationService.php:23` koristi `password_hash()`, a `AuthService.php:83` koristi `password_verify()`. Nema ručnog salta, plaintext lozinke u korisničkoj tabeli ili brzog SHA-256 heša lozinke. `password_needs_rehash()` je dobra ideja za postepeno unapređenje postojećih hash vrednosti.

Razlika je važna: korisničke lozinke imaju ograničenu nepredvidivost i traže skup password hash; nasumični tokeni od 32 bajta već imaju veliku nepredvidivost i mogu da se čuvaju kao SHA-256 hash. Problem sa konkurentnim rehashom i ograničenjima bcrypta obrađen je kasnije.

### 4.5. Tokeni su dovoljno nepredvidivi i ne čuvaju se sirovi u token tabelama

Reset i verifikacija koriste `random_bytes(32)` i u bazi čuvaju SHA-256 hash. U servisu se zahteva tačan format od 64 heksadecimalna znaka pre upita. Istek se proverava i pri prikazu forme i ponovo pri promeni stanja.

To znači da validan GET nije trajna dozvola za kasniji POST. Korisnik može da otvori formu, token zatim istekne ili bude potrošen, i POST mora ponovo da ga odbije. Ovde je ta važna provera već prisutna.

### 4.6. Reset lozinke je transakciona operacija

`PasswordResetService.php:76–115` u istoj transakciji zaključava token, proverava ga, menja lozinku, povećava `auth_version`, briše remember tokene i troši reset token. U slučaju greške radi rollback.

To je mnogo bolje od pet nepovezanih upita. Ako bi promena lozinke uspela, a opoziv starih tokena propao, korisnik bi dobio lažan osećaj da je nalog osiguran. Transakcija sprečava takvo delimično izvršavanje, pod uslovom da tabele koriste transakcioni engine.

`findTokenForUpdate()` i odgovarajuća email metoda odbijaju poziv bez aktivne transakcije. To je dobra zaštita od pogrešne upotrebe repozitorijuma.

### 4.7. `auth_version` rešava problem starih aktivnih sesija

`AuthService.php:40–53` učitava trenutno stanje korisnika i poredi njegovu verziju autentikacije sa verzijom u sesiji. Posle reseta, ranije sesije više ne odgovaraju novom broju i pri sledećoj proveri gube prijavu.

Pored verzije proveravaju se aktivnost i potvrđen email. Postojeći session ID zato nije dovoljan da suspendovan korisnik nastavi normalan pristup. Keširanje korisnika važi samo u okviru životnog veka objekta u jednom uobičajenom PHP zahtevu.

Ograničenje: zahtev koji je već prošao proveru može završiti pre nego što primeti reset. Za buduće posebno osetljive promene treba proveriti ovlašćenje i neposredno pri izvršenju operacije.

### 4.8. Remember me rotacija koristi uslovni SQL update

`PdoRememberMeTokenRepository.php:57–77` menja token samo ako se poklapaju stari selector i stari hash i ako rok nije istekao. Uspeh zahteva tačno jedan izmenjen red. To sprečava da dva konkurentna zahteva uspešno potroše istu staru vrednost.

`RememberMeService.php:65–95` zadržava prvobitni istek. Svaka obnova ne produžava cookie za novih 30 dana, pa jedan token ne postaje beskonačan. Ovo je važna pozitivna odluka.

### 4.9. CSRF zaštita pokriva postojeće POST operacije

Token nastaje iz `random_bytes(32)`, unosi se u forme i poredi uz `is_string()` i `hash_equals()`. Prijava i odjava takođe imaju CSRF zaštitu. GET verifikacije samo prikazuje dugme; stvarna promena se dešava preko POST-a, što smanjuje problem automatskog otvaranja linkova od strane email skenera.

Postoje problemi u prikazu nekih CSRF grešaka, ali nije pronađena postojeća POST ruta koja namerno preskače tu proveru.

### 4.10. HTML izlazi su uglavnom pravilno escaped

Ime na dashboardu, flash poruke i skriveni tokeni prolaze kroz `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. To je dobra zaštita za HTML tekst i quoted atribute koji se ovde koriste.

Nije pronađen konkretan reflected/stored XSS put u pregledanim view fajlovima. To nije ocena sadržaja `includes/header.php`, koji nije pregledan. Takođe, escaping sprečava izvršavanje HTML-a, ali ne sprečava curenje tajne kada aplikacija odluči da je ispiše.

### 4.11. Više malih odluka je dobro

- Redirect nakon uspešnog POST-a uglavnom koristi `303`, čime refresh ne ponavlja upis.
- Reset i resend odgovori nastoje da ne otkriju postoji li nalog.
- Limiti za reset/resend koriste i email i IP, pa jedna nova sesija ne resetuje brojač.
- Linkovi se grade iz `APP_URL`, umesto iz neproverenog HTTP `Host` zaglavlja.
- Logger uklanja CR/LF iz dinamičkih vrednosti i koristi `LOCK_EX`, što smanjuje podmetanje redova i mešanje paralelnih upisa.
- View putanje u `switch` granama su fiksne. Korisnikov `route` se ne koristi kao proizvoljna putanja za `include`.

---

## 5. Pregled prioriteta

| ID | Prioritet / težina | Nalaz |
| --- | --- | --- |
| S01 | Visok | Sesija se konfiguriše pre učitavanja okruženja; `Secure` može ostati isključen. |
| S02 | Visok | Login nema ograničenje pokušaja; registracija i finalni reset dopuštaju skupo heširanje bez limita. |
| S03 | Visok, uslovni | Paralelni login/rehash i reset mogu vratiti staru lozinku ili izdati novi remember token posle opoziva. |
| S04 | Visok za proveru; potencijalno kritičan | Logovi sa auth linkovima su unutar stabla projekta; web dostupnost nije proverena. |
| S05 | Srednji | Potvrda lozinke ulazi u poruku o grešci i flash sesiju. |
| S06 | Srednji | Unicode lozinke prelaze bcrypt granicu; NUL unos izaziva izuzetak. |
| S07 | Srednji | Polja za lozinke su `type="text"`. |
| S08 | Srednji | Registracija otkriva postojeći email; login ima različite vremenske putanje. |
| S09 | Srednji, uslovni | Reset stranica nema eksplicitnu zaštitu od slanja token URL-a kroz referrer. |
| F01 | Visok za funkcionalnost | Token se kreira, ali email nije poslat u pregledanom toku. |
| F02 | Visok pri PSR-4/Linux objavi | `Ratelimiter.php` se ne poklapa sa imenom klase `RateLimiter`. |
| F03 | Srednji | Neispravan email-verification GET zahteva nepostojeći view. |
| F04 | Srednji | `sendAgain()` završava bez prikaza ili redirecta. |
| F05 | Srednji | Forgot-password čita svojstvo pre provere tipa; `$issued` može biti nedefinisan. |
| F06 | Srednji | Prijavljen korisnik na forgot-password ruti dobija dashboard bez `$user`. |
| F07 | Srednji, uslovni | Provera duplikata pre INSERT-a ne rešava paralelnu registraciju. |
| F08 | Nizak do srednji | Flash ključevi, 403 kontekst i putanje formi nisu usaglašeni. |

Pre objavljivanja prvo rešiti S01–S04, F01–F03, a zatim preostale potvrđene greške. Brze popravke korisničkog toka mogu ići uporedo sa ozbiljnijim transakcionim radom.

---

## 6. Bezbednosni nalazi

### S01 — Podešavanje sesije zavisi od okruženja koje još nije učitano

**Mesto:** `index.php:29–30, 46–47`; `src/Session/Session.php:7–23`.

`$session->start()` se poziva pre `$dotenv->load()`. U `Session::start()` se `APP_ENV` već čita, uz podrazumevani `development`. Ako se produkciona vrednost dobija tek iz `.env`, session cookie nastaje bez `Secure` atributa. Kasnije učitavanje promenljive ne menja već primenjena cookie podešavanja.

**Dokaz:** izolovan poziv metode, sa bezopasnom zamenom `session_start()`, dao je `secure=false` pre i posle naknadnog postavljanja `APP_ENV=production`.

**Zašto je opasno:** cookie bez `Secure` može biti poslat i HTTP zahtevom. Ako takav zahtev postoji i mrežni napadač ga presretne, može dobiti session ID. HTTPS/HSTS konfiguracija može smanjiti izloženost, ali nije proverena. Ako je `APP_ENV` već postavljen u `$_ENV` pre ulaza u skriptu, ovaj konkretan scenario ne nastaje.

**Kako popraviti:** učitati i proveriti konfiguraciju odmah posle autoloadera, pa tek onda napraviti i pokrenuti sesiju. Još jasnije je proslediti `secureCookies` boolean u `Session`, umesto da klasa sama čita globalno okruženje.

```php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Ovde proveriti da su APP_ENV i ostala obavezna podešavanja validni.
$session = new Session();
$session->start();
```

U istom fajlu, red 12, postoji i nezavisna greška:

```php
ini_set('session.use.only.cookies', '1'); // postojeći, pogrešan naziv
ini_set('session.use_only_cookies', '1'); // ispravan naziv
```

Pogrešna direktiva ne postoji. U proveravanom CLI okruženju ispravna direktiva već ima vrednost `1`, pa nije dokazano da se session ID zaista prihvata iz URL-a. Dokazano je da kod ne podešava ono što namerava. Naziv i ponašanje direktiva dokumentovani su u [PHP session konfiguraciji](https://www.php.net/manual/en/session.configuration.php).

**Provera posle popravke:** u odvojenom HTTP test okruženju, sa `APP_ENV` koji dolazi isključivo iz `.env`, proveriti `Set-Cookie`: `Secure`, `HttpOnly`, `SameSite=Lax`. Proveriti i stvarni `session.use_only_cookies`.

### S02 — CSRF token nije zaštita od pogađanja lozinki

**Mesto:** `LoginController.php:20–74`; `RegistrationController.php:19–62`; `ResetPasswordController.php:91–123`. Putanje kontrolera su u `src/Http/Controllers/`.

`RateLimiter` se koristi za traženje reset linka i ponovno slanje verifikacije. Nije uključen u prijavu. Napadač može otvoriti sopstvenu sesiju, pročitati njen CSRF token i slati mnogo pokušaja prijave. CSRF dokazuje vezu zahteva sa sesijom, a ne da je zahtev poslao dobronameran čovek.

**Posledice:** pogađanje lozinki, provera kredencijala procurelih sa drugih sajtova i opterećivanje servera skupim password hash proverama. Registracija takođe može stvarati mnogo naloga. Finalni reset računa `password_hash()` pre nego što servis proveri da li string token uopšte ima validan format ili odgovarajući red u bazi.

Kod finalnog reseta to ne omogućava promenu tuđe lozinke bez tokena: `finalReset()` ga ipak proverava. Omogućava nepotreban CPU posao pre odbijanja zahteva.

**Kako popraviti u postojećoj strukturi:** proslediti limiter login i registration kontroleru; postaviti odvojene limite po IP adresi i normalizovanom nalogu; odbiti pre skupog heširanja. Za finalni reset dodati IP limit i ranu proveru formata/važenja tokena, ali zadržati konačnu proveru unutar transakcije. Rana provera služi optimizaciji, a transakciona bezbednosti.

Ne uvoditi trajno zaključavanje naloga posle nekoliko anonimnih zahteva: time bi napadač mogao da zaključa tuđ nalog. Pragove podesiti prema realnom korišćenju, uz kratke periode i postepeno usporavanje. [OWASP Authentication](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#login-throttling) obrađuje ograničavanje pokušaja kao posebnu kontrolu prijave.

**Provera:** više sesija sa iste IP adrese ne sme da zaobiđe IP limit; više IP adresa ne sme neograničeno da napada jedan nalog. Invalidan reset token treba odbiti pre računanja novog password hasha.

### S03 — Reset i prijava ne dele potpuno pravilo konkurentnosti

**Težina:** visok rizik uz preplitanje zahteva. Prvi podscenario zahteva hash kome je potreban rehash. Drugi zahteva login sa uključenim remember me.

#### A. Rehash može da vrati staru lozinku

**Mesto:** `src/Auth/AuthService.php:77–97`; `src/Repositories/PdoUserRepository.php:70–81`; `src/Auth/PasswordResetService.php:91–106`.

Moguć redosled dva zahteva:

1. Login učita korisnika sa starim hashom i uspešno proveri staru lozinku.
2. Reset postavi novu lozinku, poveća `auth_version`, opozove tokene i potvrdi transakciju.
3. Login iz prvog koraka završi rehash **stare** lozinke.
4. `UPDATE users ... WHERE id = :id` bez provere starog hasha prepiše novu lozinku.

`auth_version` će odbiti staru sesiju pri narednoj proveri. Ipak, hash u bazi je već vraćen na staru lozinku, pa je nov login starom lozinkom ponovo moguć.

**Dokaz:** neizmenjeni `AuthService` je pozvan nad memorijskim repozitorijumom koji simulira reset između čitanja i rehash upisa. Rezultat: login `SUCCESS`, stara lozinka se verifikuje, nova se ne verifikuje, verzija repozitorijuma je `2`, a verzija u sesiji `1`. Ovo je deterministička demonstracija servisnog problema, ne izvršen napad na stvarnu bazu.

**Kako popraviti:** rehash izdvojiti u metodu koja radi uslovnu zamenu, na primer `rehashIfUnchanged()`. Konceptualni SQL:

```sql
UPDATE users
SET password_hash = :new_hash
WHERE id = :id
  AND password_hash = :expected_hash
  AND auth_version = :expected_version
```

Ako nije promenjen tačno jedan red, autentikaciono stanje se promenilo tokom zahteva. Prekinuti taj pokušaj prijave i tražiti novu prijavu; ne nastaviti automatski sa zastarelim `User` objektom. Poređenje hash kolone mora biti egzaktno, sa odgovarajućom binarnom/case-sensitive semantikom baze.

#### B. Remember token može nastati posle brisanja svih prethodnih tokena

**Mesto:** `LoginController.php:48, 68–71`; `src/Auth/RememberMeService.php:18–23, 36–49`; `src/Auth/PasswordResetService.php:97–100`.

Login proveri staru lozinku. Reset zatim poveća verziju i obriše postojeće tokene. Login potom pozove `issue($user)` sa ranije učitanim korisnikom i napravi **novi** remember token. Token tabela, prema pregledanim upitima, ne vezuje token za `auth_version` pri izdavanju. Pri kasnijem restore-u učitava se novi korisnički objekat sa novom verzijom i uspostavlja važeća sesija.

Ovaj podscenario izveden je iz redosleda poziva; nije zasebno reprodukovan na MySQL-u. Zahtevi iz različitih sesija mogu da se preklapaju čak i ako PHP serijalizuje zahteve jedne iste sesije.

**Kako popraviti:** vezati remember token za verziju autentikacije pri izdavanju i odbiti ga pri restore-u kada se verzija razlikuje. Alternativno, izdavanje tokena i konačnu proveru korisnika serijalizovati preko zaključavanja reda korisnika koje koristi i reset. U oba slučaja treba rešiti i rehash uslovnim upisom. Sama dodatna provera verzije pre INSERT-a nije dovoljna ako između provere i upisa ostaje novi vremenski prozor.

**Lekcija:** transakcija samo u reset metodi ne uređuje automatski sve konkurentne operacije u drugim servisima. Pravilo mora obuhvatiti svaki upis koji može da obnovi pristup.

### S04 — Naziv direktorijuma `private` ne znači da ga web-server štiti

**Mesto:** `src/Helpers/Logger.php:13, 57–62`; `src/Auth/PasswordResetService.php:42–51`; `src/Auth/EmailVerificationService.php:37–46`; `src/private/info.log`.

Logger piše u `src/private/`. U postojećem `info.log` pronađena su dva zapisa sa punim verifikacionim linkovima. Tokeni ovde nisu prepisani. Servisi mogu u lokalnom/development režimu, kada je `LOG_RESET_LINKS=true`, zapisati i reset link.

**Potencijalno kritičan scenario:** ako je koren projekta javni document root i server ne blokira taj direktorijum, zahtev za `/src/private/info.log` može otkriti važeći reset token. Ko poseduje taj token može da promeni lozinku pripadajućeg naloga. Verifikacioni token može neovlašćeno potvrditi email, ali sam po sebi nije reset lozinke.

**Šta nije dokazano:** nije testirana HTTP dostupnost loga niti pregledana serverska zabrana. U postojećem logu nisu zatečeni reset linkovi. Uslovno lokalno logovanje je dobra granica, ali development aplikacija izložena mreži i dalje može biti napadnuta.

**Kako popraviti:** čuvati logove izvan javnog document root-a, ili eksplicitno zabraniti web pristup internim direktorijumima dok se raspored ne unapredi. Isključiti logovanje auth linkova van izolovanog razvoja. Za buduće security događaje zapisivati tip događaja i interni identifikator, bez sirovog tokena ili lozinke. Postojeće osetljive logove ukloniti prema pravilima čuvanja podataka i opozvati tokene ako se utvrdi izloženost.

Unix dozvola `0700` ne rešava nužno ovaj problem: web-server koji radi pod vlasnikom direktorijuma može čitati fajl, a Windows dozvole se ne svode na taj argument.

### S05 — Poruka o neusaglašenoj lozinci otkriva njen sadržaj

**Mesto:** `src/Helpers/Validator.php:157–161`; `RegistrationController.php:32–35`; `ResetPasswordController.php:107–109`; odgovarajući view prikazi grešaka.

Postojeća poruka interpolira `$confirm`:

```php
"Password confirmation $confirm must match the password"
```

Ako korisnik pogreši u jednoj kopiji lozinke, druga kopija može biti potpuno ispravna tajna. Sadržaj potvrde se prenosi u errors niz, zatim u flash sesiju i na stranicu kao običan tekst. `htmlspecialchars()` ne sprečava čitanje lozinke; samo sprečava da se njen sadržaj tumači kao HTML.

**Dokaz:** izolovan poziv potvrđuje da rezultat sadrži pun testni confirmation string. Kada confirmation dođe kao niz, interpolacija dodatno izaziva `Array to string conversion` upozorenje.

**Kako popraviti:** poruka treba da bude statična, bez vrednosti bilo kog password polja. Pre poređenja proveriti tip oba argumenta.

```php
if (!is_string($value) || !is_string($confirm) || $value !== $confirm) {
    $this->errors[$field] = 'Password confirmation must match the password.';
    return false;
}
```

**Provera:** pogrešna potvrda, niz i `null` moraju dati kontrolisanu poruku; ni poruka ni flash podaci ne smeju sadržati testnu tajnu.

### S06 — Broj znakova nije isto što i broj bajtova za bcrypt

**Mesto:** `src/Helpers/Validator.php:137–154`; `RegistrationService.php:23`; `ResetPasswordController.php:121`.

Validator prihvata od 8 do 30 Unicode znakova preko `mb_strlen()`. U proveravanom PHP 8.3 okruženju `PASSWORD_DEFAULT` koristi bcrypt, koji obrađuje najviše prvih 72 bajta lozinke. Znak može zauzimati više bajtova. [PHP dokumentacija za password_hash](https://www.php.net/manual/en/function.password-hash.php) eksplicitno navodi tu granicu.

**Dokaz:** dve testne lozinke, svaka sa 18 istih četvorobajtnih emoji znakova i različitim poslednjim ASCII znakom, obe imaju 19 znakova i 73 bajta. Validator prihvata obe. Hash prve uspešno verifikuje i drugu jer se razlikuju tek posle 72. bajta.

To nije mogućnost pogađanja bilo čije lozinke bez znanja, ali jeste narušeno očekivanje da se proverava ceo uneseni string.

Drugi slučaj: validator prihvata string sa NUL bajtom, a `password_hash(..., PASSWORD_DEFAULT)` u ovom okruženju baca `ValueError: Bcrypt password must not contain null character`. Registracija i finalni reset nemaju odgovarajuću obradu tog ulaza.

**Minimalna popravka uz bcrypt:** za nove lozinke proveriti i `strlen($password) <= 72`, odbiti NUL i dati jasnu poruku. Ne odsecati lozinku tiho. Za duže Unicode passphrase lozinke može se planirati Argon2id, uz proveru dostupnosti i troškova na serveru. Algoritam i politiku deliti između registracije, reseta i rehasha.

Ne menjati samo `password_hash()` u registraciji na Argon2id, a ostaviti `password_needs_rehash(..., PASSWORD_DEFAULT)` u loginu: tada bi login mogao ponovo da vrati hash na drugi algoritam.

Login ne treba slepo da primenjuje svaku novu politiku za kreiranje lozinke na postojeće naloge. Treba mu bezbedna granica veličine unosa i verifikacija postojeće lozinke, uz promišljenu migraciju. Gornja granica od 30 znakova je nepotrebno tesna za passphrase pristup.

### S07 — Lozinke se prikazuju kao običan tekst tokom unosa

**Mesto:** `views/auth/loginpage.php:49`; `views/auth/register.php:59, 71`; `views/auth/reset-password.php:35, 47`.

Sva ova polja imaju `type="text"`. Osoba pored korisnika, deljeni ekran ili snimak ekrana mogu direktno otkriti tajnu. To je stvarni problem privatnosti čak i kada su mrežni prenos i hash u bazi dobri.

**Kako popraviti:** koristiti `type="password"`; loginu ostaviti `autocomplete="current-password"`, a registraciji i resetu postaviti `autocomplete="new-password"`. Po želji dodati eksplicitno dugme „Prikaži lozinku“, sa podrazumevano maskiranim prikazom.

Maskiranje nije kriptovanje i ne menja potrebu za HTTPS-om.

### S08 — Postojanje naloga se može zaključiti

**Mesto:** `RegistrationService.php:19–21`; `RegistrationController.php:41–46`; `AuthService.php:77–84`.

Registracija direktno prikazuje da je email već zauzet. Login za nepostojećeg/neaktivnog korisnika vraća rezultat pre `password_verify()`, dok za aktivnog korisnika izvršava skupu proveru. Razlika u vremenu može omogućiti statističko razlikovanje naloga; nije merena na stvarnom HTTP serveru.

**Zašto je važno:** saznanje da neko ima nalog može biti podatak o privatnosti i olakšati ciljani phishing ili napade na lozinku. Ne predstavlja samo po sebi pristup nalogu. Poruka „email nije potvrđen“ dolazi tek posle tačne lozinke, što je mnogo pažljivije od njenog prikaza samo na osnovu adrese.

**Kako unaprediti:** za nepoznat nalog izvršiti proveru protiv unapred pripremljenog dummy hasha istog algoritma/troška; ne generisati dummy hash pri svakom zahtevu. Uvesti limite iz S02. Za registraciju svesno odlučiti da li je eksplicitna poruka o zauzetom emailu prihvatljiv UX kompromis ili treba generički odgovor. Generički tekst nije dovoljan ako status, redirect i vreme i dalje jasno otkrivaju razliku. Ovu razliku opisuje [OWASP Authentication: error messages](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#authentication-and-error-messages).

### S09 — Reset link traži isti oprez kao verifikacioni link

**Mesto:** `index.php:253–266`, nasuprot `index.php:290–291`.

Za uspešan GET email verifikacije postoje `Referrer-Policy: no-referrer` i `Cache-Control: no-store`. Reset GET ih eksplicitno ne postavlja. Token je u URL-u i može biti prenet kroz referrer u zavisnosti od odredišta, politike browsera i servera. Savremena podrazumevana politika često uklanja putanju pri cross-origin zahtevu, pa nije tačno da svaki spoljni resurs automatski dobija ceo token. Same-origin zahtevi i druga podešavanja ipak ostaju relevantni.

**Kako popraviti:** pre prikaza svih stranica koje nose auth token postaviti `Referrer-Policy: no-referrer` i eksplicitni `Cache-Control: no-store`. Proveriti obradu URL-ova u access logovima, proxy-jima i analitici. Samo zaglavlje ne uklanja URL iz svih već postojećih logova.

Podrazumevani PHP session cache limiter možda već emituje cache zaštitu, a server može postavljati dodatna zaglavlja; ovo nije provereno. Nalaz se odnosi na nedoslednu eksplicitnu zaštitu u pregledanom kodu. [OWASP Forgot Password](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html#url-tokens) preporučuje kontrolu referrera na reset stranici.

---

## 7. Funkcionalne greške i pouzdanost

### F01 — Kreiranje tokena nije isto što i slanje emaila

**Mesto:** `src/Auth/EmailVerificationService.php:19–49`; `src/Auth/PasswordResetService.php:20–54`.

Obe metode naprave token, upišu njegov hash i sastave link. Zatim ga eventualno zapišu u lokalni log i vrate `true`. U pregledanom putu izvršavanja nema poziva mail transporta, mail servisa ili reda za slanje poruka.

**Posledica:** u produkciji, gde je lokalno logovanje isključeno, sirovi token se ne isporučuje korisniku. Novi korisnik ne može da završi potvrdu emaila i prijavi se; korisnik koji je zaboravio lozinku ne dobija sredstvo za reset. Hash iz baze nije zamena za originalni token.

Ako je logovanje namerno privremena zamena za email tokom vežbanja, to je razumljiva razvojna faza. Funkcionalnost ipak treba nazvati „generisanje linka“, a ne dovršenim slanjem emaila.

**Kako implementirati bez frameworka:** uvesti mali `AuthMailer` interfejs, sa metodama poput `sendVerificationLink()` i `sendPasswordResetLink()`. Servis dobija taj objekat kroz konstruktor. Lokalna implementacija može koristiti izolovani testni inbox, dok produkciona koristi izabrani pouzdan mail transport.

Potrebno je razlikovati najmanje: token sačuvan, poruka prihvaćena za slanje i korisniku prikazan generički odgovor. Uspeh slanja transportu ne garantuje da je poruka stigla u inbox. Za sada je dovoljno kontrolisano hvatati grešku transporta, bez otkrivanja postojanja naloga, i omogućiti resend. Kod registracije račun već može postojati kada izdavanje/slanje zakaže; to treba jasno podržati umesto pokušaja nove registracije.

Kada se uvede email, dodati i obaveštenje o uspešnoj promeni lozinke, bez same lozinke. [OWASP Forgot Password](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html#user-resets-password) preporučuje takvo obaveštenje.

### F02 — Ime fajla za limiter nije PSR-4 usaglašeno

**Mesto:** `src/Security/Ratelimiter.php:7`; `index.php:19, 58`.

Klasa se zove `RateLimiter`, a fajl `Ratelimiter.php`. Na Windows filesystem-u razlika velikog i malog slova često ostane neprimećena. Na case-sensitive sistemu, standardno PSR-4 mapiranje traži `RateLimiter.php`, koji pod tim tačnim imenom ne postoji.

**Posledica:** pri takvom autoload podešavanju aplikacija može stati na `new RateLimiter($pdo)`, pre rutiranja, pa problem zahvata sve stranice. Composer konfiguracija i generisana classmap nisu pregledani: posebna classmap može promeniti ponašanje, zato stvarni Linux pad nije proglašen izvršenim testom.

**Kako popraviti:** preimenovati fajl tako da tačno odgovara klasi. Na Windows-u za promenu samo veličine slova može biti potrebno privremeno ime, pa konačno ime. To je buduća izmena; tokom pregleda ništa nije preimenovano. [PSR-4 specifikacija](https://www.php-fig.org/psr/psr-4/) zahteva podudaranje velikih i malih slova između klase i imena fajla.

**Provera:** autoload test u case-sensitive okruženju, bez ručnog `require` učitavanja klase kao zaobilaznice.

### F03 — Neispravan verifikacioni token vodi na nepostojeći fajl

**Mesto:** `index.php:281–287`; direktorijum `views/errors/`.

Grana za nevažeći ili izostavljen email token radi:

```php
require __DIR__ . '/views/errors/invalid-verification-token.php';
```

Takav fajl ne postoji. Postoje samo `403.php` i `invalid-reset-token.php`.

**Posledica:** umesto planirane stranice sa statusom 400 izvršavanje se prekida na `require`. Korisnik sa isteklim, već upotrebljenim ili pogrešnim linkom ne dobija urednu grešku. Tačan konačni HTTP status zavisi od PHP/server obrade fatalne greške; nedostajući fajl je potvrđen.

**Kako popraviti:** napraviti namenjen view ili koristiti postojeći generički token-error view sa odgovarajućim tekstom i linkom ka ponovnom slanju. POST kontroler trenutno koristi reset-error view i za email tok; to nije zaobilaženje zaštite, ali nazivi i navigacija treba da budu dosledni.

### F04 — Ponovno slanje verifikacije vraća prazan odgovor

**Mesto:** `src/Http/Controllers/EmailVerificationController.php:65–98`; `index.php:306–308`; `views/auth/resend-verification.php:11`.

Za neispravan email `sendAgain()` samo radi `return`. Komentar predlaže flash i redirect, ali ih kod ne izvršava. Za ispravan email metoda eventualno izda token i postavi success flash, ali opet nema redirect niti prikaz view-a. Router potom završi granu.

**Posledica:** po normalnom toku aplikacionog koda, korisnik dobija prazan odgovor umesto završne stranice. Success poruka može ostati za neki kasniji, nevezan GET. Neispravan email nema razumljivu povratnu informaciju. Forma dodatno ima pogrešan naslov „Forgot password“.

**Kako popraviti:** koristiti isti `Validator::validateEmail()` kao u ostalim tokovima, kako bi se trim izvršio pre provere formata. Na grešci upisati errors flash i poslati `303` nazad na resend stranicu; na uspešnom/generičkom ishodu upisati success flash i poslati isti redirect. Posle redirecta `exit`.

```php
$this->session->flashSet(
    'success',
    'If this account is eligible, verification instructions will be provided.'
);
header('Location: index.php?route=resend-verification', true, 303);
exit;
```

Postojeće ograničenje učestalosti i generičku poruku treba sačuvati. Popravka HTTP odgovora ne zahteva promenu SQL logike.

### F05 — Rezultat validacije koristi se pre nego što se utvrdi njegov tip

**Mesto:** `src/Http/Controllers/ResetPasswordController.php:35–44, 60–66`.

`ForgotPasswordRequest::validated()` vraća DTO ili niz grešaka. Kontroler prvo radi `$validated->email`, pa tek zatim `is_array($validated)`.

**Dokaz:** za neispravan email u izolovanom PHP 8.3 testu nastaju `Attempt to read property "email" on array` i deprecation za `trim(null)`. U ovom režimu izvršavanje može nastaviti do redirecta; uz handler koji upozorenja pretvara u izuzetke tok puca. Zato ovo nije opisano kao bezuslovan fatalni pad na svakom serveru.

**Kako popraviti:** najpre razdvojiti grane, pa tek onda koristiti DTO.

```php
$validated = $request->validated($_POST['email'] ?? null);

if (is_array($validated)) {
    $this->session->flashSet('errors', $validated);
    header('Location: index.php?route=forgot-password', true, 303);
    exit;
}

$email = $validated->email;
```

Drugi problem je `$issued`: nastaje samo ako su oba limita dozvolila zahtev, a čita se i kada nisu. Inicijalizovati ga ili njegovu obradu zadržati unutar iste grane. Pošto je trenutno telo `if (!$issued)` samo komentar, može se i ukloniti nepotrebno čitanje dok se ne uvede stvarna obrada rezultata.

**Lekcija:** `array | DTO` je prihvatljiv jednostavan model, ali svaka upotreba mora prvo da proveri koja je varijanta vraćena. Ako se ovo često ponavlja, zaseban `ValidationResult` objekat može smanjiti mogućnost greške.

### F06 — Dashboard se uključuje bez pripremljenog korisnika

**Mesto:** `index.php:243–245`; `views/dashboard.php:18`.

Na `GET:forgot-password`, kada korisnik već ima važeću sesiju, router direktno uključuje dashboard. U toj grani nije postavljeno `$user`. Poziv `$authService->guest()` interno može učitati korisnika, ali ne stvara lokalnu promenljivu `$user` u `index.php`.

Dashboard očekuje `$user->name()`, pa prema pregledanom toku nema potrebne podatke. Zajednički header van obima pregleda ne treba koristiti kao skriven izvor autentikacionog konteksta.

**Kako popraviti:** za prijavljenog korisnika uraditi redirect na `index.php?route=dashboard`. Već postojeća dashboard grana tada centralno pripremi korisnika i izvrši proveru. To je jednostavnije od ponavljanja pripreme podataka na dve lokacije.

### F07 — Provera da email ne postoji ne garantuje jedinstvenost

**Mesto:** `src/Auth/RegistrationService.php:17–25`; `src/Repositories/PdoUserRepository.php:51–66`; `RegistrationController.php:39–47`.

Dva zahteva mogu gotovo istovremeno dobiti `findByEmail() === null`, pa oba pokušati INSERT. Ishod zavisi od baze:

- Ako postoji `UNIQUE(email)`, jedan zahtev uspeva, a drugi dobija PDO exception. Kontroler hvata samo `DomainException`, pa konkurentni duplikat nema istu obradu kao običan duplikat.
- Ako jedinstveno ograničenje ne postoji, mogu nastati dva naloga sa istim emailom, što razbija očekivanje lookup-a i reset toka.

Šema nije pregledana, pa odsustvo indeksa nije proglašeno činjenicom.

**Kako popraviti:** jedinstvenost mora garantovati baza. U PDO repozitorijumu prepoznati konkretan duplicate-key slučaj za email ograničenje i prevesti ga u namenski domenski izuzetak. Ne prevoditi svaku SQL grešku u „email zauzet“: prekid veze, pogrešna kolona i drugi integritetski problemi imaju drugo značenje.

Prethodni SELECT može ostati radi korisničkog toka, ali ne sme biti jedina zaštita. Normalizaciju emaila i collation usaglasiti sa pravilom šta se smatra istom adresom.

### F08 — Nedoslednosti između kontrolera i view-a

#### Poruka posle registracije se ne prikazuje

`RegistrationController.php:59` postavlja flash ključ `verify`, a `views/auth/loginpage.php:5–6` čita `errors` i `success`. Korisnik posle uspešne registracije ne vidi namenjeno objašnjenje. Koristiti isti ključ ili eksplicitno prikazati `verify` poruku. `Session::flashGet()` briše samo pročitane ključeve, pa nepročitana poruka ostaje u sesiji.

#### 403 view meša lokalnu promenljivu i direktan pristup sesiji

`index.php:163` postavlja lokalni `$page = 'register'`. `views/errors/403.php:5` ga zatim prepisuje iz `$_SESSION['page']`, bez provere postojanja. Reset POST CSRF grana takođe ne postavlja session `page`.

Posledica su upozorenje za nedostajući ključ i pogrešan link nazad. Sama CSRF provera i dalje odbija zahtev. Jednostavno rešenje je da svaki pozivalac prosledi lokalni `$page`, a view primeni allowlist i podrazumevanu vrednost, bez direktnog čitanja i brisanja `$_SESSION`.

#### Putanje formi su vezane za naziv lokalnog projekta

`views/auth/register.php:24`, `loginpage.php:26`, `forgot-password.php:20` i `reset-password.php:19` koriste `/../php-course/index.php?...`. Browser normalizuje putanju na korenu domena; ona praktično pretpostavlja `/php-course/` deployment. Na sopstvenom domenu sa aplikacijom u `/`, ili pod drugim direktorijumom, POST može otići na pogrešnu adresu.

U postojećem front-controller modelu dosledno `index.php?route=...` rešava većinu ovih formi. Ako postoji više deployment prefiksa, uvesti mali URL helper sa proverenim base path podešavanjem. To nije potreba za složenijim routerom.

#### Upotrebljivost i pristupačnost

Mnoga polja imaju prazan `id` i oslanjaju se samo na placeholder. Dodati vidljiv `label`, jedinstven `id`, prikladan autocomplete i povezivanje greške preko `aria-describedby`. `required` i razumna HTML ograničenja mogu pomoći korisniku, ali ne zamenjuju server validaciju.

Za neuspešnu registraciju korisno je sačuvati dozvoljena „old input“ polja, poput imena i emaila, uz escaping pri prikazu. Password i confirmation se nikada ne vraćaju u HTML ili flash old-input podatke.

---

## 8. Baza, sesije i dodatna poboljšanja

Ovo poglavlje odvaja stvari koje tek treba proveriti od već dokazanih grešaka. Nije zahtev da odmah uvedeš svaku moguću kontrolu.

### 8.1. Ugovor koda sa bazom mora biti zapisan

Upiti pretpostavljaju sledeće osobine šeme:

| Deo baze | Potrebno pravilo |
| --- | --- |
| `users.email` | Jedinstvenost usaglašena sa normalizacijom emaila. |
| `users.auth_version` | Nenull celobrojna vrednost sa smislenom početnom vrednošću. |
| `users.status` | Vrednost dozvoljena enumom i definisan početni status. |
| `users.email_verified_at` | `NULL` za novi nepotvrđeni nalog. |
| `users.password_hash` | Dovoljan kapacitet za sadašnje i buduće algoritme, tipično 255 znakova. |
| Reset/verify `user_id` | `UNIQUE` ako pravilo glasi „jedan važeći token po korisniku“. |
| Reset/verify `token_hash` | Indeks; za jasnu jednoznačnost poželjan `UNIQUE`. |
| Remember `selector` | Jedinstveno i indeksirano pretraživanje. |
| Limiter | Složeni `UNIQUE(scope, key_hash)`. |
| Veze ka korisniku | Strani ključevi i definisano ponašanje pri brisanju korisnika. |
| Transakcione tabele | Engine koji podržava transakcije i zaključavanje redova. |

`ON DUPLICATE KEY UPDATE` reaguje na postojeći unique/primary key konflikt. Sama SQL fraza ne znači da je `user_id` jedinstven. Bez odgovarajućeg ključa resend može dodavati redove umesto zamene, a limiter može stalno praviti nove brojače. To su pretpostavke koje treba potvrditi u zasebnom pregledu šeme.

`incrementAuthVersion()` nema proveru broja izmenjenih redova. Kod ispravnog zaključavanja i postojećeg korisnika to bi trebalo da bude jedan red. Provera bi učinila neusaglašenost vidljivom, naročito ako se menjaju tokovi brisanja korisnika. Takođe, `NULL + 1` ostaje `NULL`, što dodatno objašnjava zahtev za nenull kolonom.

### 8.2. Limiter ima dobru osnovu, ali treba definisati operativne granice

`RateLimiter::allowPerHour()` radi atomsko povećavanje brojača u SQL-u, ali zatim zasebno čita vrednost. Pod konkurencijom zahtev može videti i pokušaje koji su stigli nakon njegovog upisa, pa odbiti više zahteva nego što bi savršeno precizno raspoređivanje odbilo. To nije dokaz proizvoljnog zaobilaženja limita; pre je moguća preterana restrikcija na granici.

Prozor traje sat vremena od početka zapisa/prozora, a ne predstavlja precizan klizni zbir svih zahteva u poslednjih 60 minuta. Na granici prozora dozvoljeni pokušaji mogu biti grupisani. To je prihvatljivo ako je namerno i dokumentovano.

U reset/resend kontrolerima email brojač se menja pre nego što se sazna ishod IP limita. Čak i već blokirana IP adresa može slati nove adrese i praviti dodatne email brojače, ili trošiti tuđu email kvotu. Prvo primeniti jeftiniji opšti IP limit, pa tek zatim uži limit; ne tvrditi da time nestaje mogućnost distribuirane zloupotrebe.

Uvesti periodično čišćenje starih limit zapisa i isteklih tokena. U pregledanom obimu nema tog posla, ali on može postojati drugde i nije pregledan. Ne povećavati brojač beskonačno ako se može bezbedno zadržati na vrednosti „limit prekoračen“; time se izbegava nepotreban rast i eventualni overflow tipa kolone.

`REMOTE_ADDR` je bolji početak od slepog poverenja proizvoljnom `X-Forwarded-For`. Ako se uvede reverse proxy, klijentsku IP adresu izvlačiti samo uz eksplicitnu listu pouzdanih proxy-ja. Inače svi korisnici mogu deliti proxy IP, ili napadač može sam birati navodnu IP adresu.

SHA-256 emaila/IP adrese u limiteru izbegava čuvanje očiglednog plaintexta, ali nije potpuna anonimizacija: poznate adrese i mali IP prostor mogu se proveravati pogađanjem. Ako to zahteva model privatnosti, koristiti HMAC sa tajnim ključem i ograničen retention.

### 8.3. Vreme treba da ima jedno značenje

Tokeni i limiter uglavnom koriste UTC, što je dobro. `markEmailVerified()` koristi `CURRENT_TIMESTAMP`, a `hydrateUser()` pravi `DateTimeImmutable` bez eksplicitne timezone vrednosti. DB session timezone i PHP timezone nisu provereni.

Preporuka je da aplikacija, DB konekcija i serijalizovani datumi imaju dokumentovanu UTC konvenciju, a da se lokalno vreme primenjuje tek pri prikazu. Posebno proveriti da li su kolone `DATETIME` ili `TIMESTAMP`, jer način konverzije nije isti. Nije dokazano da su rokovi sada pogrešni; prepoznata je konfiguraciona pretpostavka.

### 8.4. Session cookie bez roka nije aplikacioni timeout

`Session.php` postavlja cookie lifetime na `0`, ali ne čuva vreme prijave i poslednje aktivnosti. To ne daje precizno pravilo „odjava posle X minuta neaktivnosti“ ili „sesija najduže Y sati“. Browser može obnoviti sesiju pri ponovnom otvaranju, a PHP čišćenje session podataka nije poslovno pravilo autentikacije.

Za sledeću fazu dodati `authenticated_at` i `last_activity_at`, proveravati ih serverski i jasno odrediti kako se timeout odnosi prema remember-me obnavljanju. Ako timeout treba da zahteva ponovnu lozinku, automatski remember restore ne sme odmah poništiti tu nameru. Razliku između idle i apsolutnog isteka obrađuje [OWASP Session Management](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html#session-expiration).

Ne treba rotirati CSRF token posle svakog zahteva po automatizmu. Za ovu aplikaciju session token može biti razuman izbor. Eventualnu rotaciju pri prijavi uskladiti sa otvorenim tabovima, bez stvaranja nepotrebnih lažnih 403 grešaka.

### 8.5. Remember-me greške i opoziv zahtevaju eksplicitna pravila

Kod ne briše cookie kada selector ne postoji ili validator ne odgovara, a restore vraća `false`. To bezbedno odbija prijavu, ali može ponavljati beskorisne upite. Ipak, slepo brisanje cookie-ja iz svakog neuspešnog paralelnog restore odgovora može obrisati novu vrednost koju je drugi zahtev upravo postavio. Zato promena mora uzeti u obzir redosled HTTP odgovora, a ne samo „odkomentarisati clearCookie“.

Trenutna odjava opoziva token prepoznat iz trenutnog cookie-ja. Ne predstavlja odjavu svih uređaja; to je prihvatljivo ako je namera „odjava ovog browsera“. Za buduće „odjavi sve uređaje“ treba posebna operacija: povećanje verzije i brisanje svih remember tokena, uz rešenu trku iz S03.

Pri logout-u greška repozitorijuma u `revoke()` sprečava naredni `AuthService::logout()`. Slično, neuspešno izdavanje remember tokena može nastupiti kada je sesija već prijavljena. Kontrolisano razdvojiti ishod osnovne sesije od opcionalnog remember-me koraka. Za logout pokušati opoziv, ali lokalno očistiti cookie i uništiti sesiju i pri grešci, uz bezbedno evidentiranje da opoziv u bazi nije potvrđen. To ne daje garanciju opoziva ukradenog tokena ako baza nije dostupna.

### 8.6. Neočekivane greške treba prikazati kontrolisano

Postojeći servisi pravilno vraćaju rollback pa ponovo bacaju izuzetak. Na nivou `index.php`, međutim, nema zajedničke granice za neočekivane greške. DB kvar, nemogućnost pisanja loga ili neispravan domenski podatak mogu prekinuti zahtev.

U produkciji treba kontrolisani 500 odgovor, isključen prikaz internih detalja korisniku i bezbedan error log. Logovanje same greške ne sme zavisiti isključivo od loggera koji je upravo zakazao. Poruke i trace ne smeju nenamerno zadržati password argumente i tokene. Serverska error konfiguracija nije pregledana, pa nije potvrđeno da sada korisniku cure stack trace ili kredencijali.

`Logger` se konstruiše za svaki zahtev i proverava/kreira direktorijum pre rutiranja. Zbog toga neupisiv log direktorijum može srušiti i običan GET login stranice. Predvideti tu zavisnost pri postavljanju aplikacije i razlikovati obavezno audit evidentiranje od opcionalnog development loga.

### 8.7. Dodatne male dorade

- Razmotriti eksplicitno `PDO::ATTR_EMULATE_PREPARES => false` za MySQL, uz proveru kompatibilnosti. Trenutni vezani parametri nisu proglašeni ranjivim samo zato što ova opcija nije postavljena.
- `PasswordResetService::issue()` proverava postojanje korisnika, ali ne i aktivnost, dok se finalni reset neaktivnog korisnika odbija. Uskladiti izdavanje sa pravilima upotrebe, da se ne šalju beskorisni linkovi.
- `AuthService::login(User)` i `RememberMeService::issue(User)` oslanjaju se na to da je pozivalac već proverio korisnika. Sadašnji glavni pozivi uglavnom to rade; dokumentovati ugovor ili dodati minimalne zaštite da budući pozivalac ne napravi zaobilaznicu.
- Eksplicitno odlučiti šta rade POST login/register zahtevi kada je korisnik već prijavljen. GET zabrana ne pokriva direktno poslat POST. To nije trenutni dokaz preuzimanja naloga, nego nedosledno pravilo toka.
- `Session::unsetCookie()` nema isti skup cookie opcija kao ostale metode i trenutno nije korišćen u pregledanom kodu. Ukloniti suvišnu metodu ili je usaglasiti pre upotrebe.
- `AuthService` ima nekorišćen `DomainException` import, `LoginController` nekorišćen `Throwable`, `ResetPasswordController` nekorišćen `UserRegisterRequest`, a `markEmailVerified()` nekorišćen `$now`. To otežava čitanje, ali nije bezbednosni propust.
- `User.php` ima zatvarajući `?>`; u fajlovima koji sadrže samo PHP bolje ga je izostaviti radi sprečavanja slučajnog outputa pri budućim izmenama. Nije potvrđeno da sada izaziva „headers already sent“.

---

## 9. Refaktor bez uvođenja MVC-a

Cilj nije više klasa po svaku cenu. Cilj je da pravilo ima jedno jasno mesto, a da naziv metode govori šta garantuje.

### Korak 1 — Popraviti konkretne greške u postojećim fajlovima

Prvo ispraviti redosled konfiguracije, naziv session direktive, ime limiter fajla, nedostajući view, redirecte, `$user`, flash ključeve i prikaz lozinki. To su male izmene sa velikim efektom, koje ne zahtevaju promenu arhitekture.

### Korak 2 — Izdvojiti konfiguraciju i zajedničke HTTP operacije

`index.php` može ostati mesto sastavljanja objekata. Po potrebi izdvojiti samo bootstrap u jedan fajl. Učitavanje okruženja mora prethoditi svim objektima koji od njega zavise.

Za ponovljene CSRF provere dovoljan je mali objekat ili funkcija sa jasnim ulazima, bez middleware sistema:

```php
final class Csrf
{
    public function __construct(private Session $session) {}

    public function valid(mixed $received): bool
    {
        $expected = $this->session->get('csrf_token');

        return is_string($expected)
            && is_string($received)
            && hash_equals($expected, $received);
    }
}
```

Kontroler ili router i dalje određuje 403 odgovor i view. Tako zaštita ne dobija skrivene `exit` pozive, a logika poređenja nije kopirana na više mesta.

Mali redirect helper može obezbediti da se posle `header()` ne zaboravi `exit`. Zaštićenu dashboard stranicu prikazivati kroz jednu granu sa pripremljenim korisnikom.

### Korak 3 — Ujednačiti request rezultat i pravila za lozinku

Dogovoriti `validated()` kao ime metode svuda, umesto kombinacije `validate()` i `validated()`. Interfejs `Request` trenutno vraća baš `UserRegisterData`, pa naziv zvuči opštije nego što ugovor jeste. Za ovaj projekat može se preimenovati u registracioni interfejs ili potpuno izostaviti ako nema korisnu zamenu implementacije.

`PasswordRequest` i `PasswordResetData` trenutno mešaju zahtev za reset link i opcionalnu lozinku, iako se finalni reset obrađuje posebno. Jasniji sledeći korak je odvojen podatak „email za link“ i odvojen „token + nova lozinka“.

Važnije od broja interfejsa jeste da kontroler prvo prepozna validacionu grešku, pa tek onda koristi DTO. Politiku za **novu** lozinku deliti između registracije i reseta; login tretirati kao proveru već postojeće tajne.

### Korak 4 — Definisati operacije koje menjaju bezbednosno stanje

Razdvojiti „korisnik menja lozinku“ od „osvežavam hash iste lozinke“. One trenutno koriste isti bezuslovni `updatePasswordHash()`, a zahtevaju različita pravila konkurentnosti.

Za reset, rehash i izdavanje remember tokena dokumentovati: koji red se zaključava, koja očekivana verzija se proverava, kojim redosledom se upisuju promene i šta znači neuspešan uslovni upis. Pri uvođenju dodatnih zaključavanja držati dosledan redosled da se ne poveća rizik od deadlock-a.

Transakcija u servisima nije greška zato što servis prima `PDO`. Za mali projekat to je razumljivo rešenje. Apstrakcija tipa `TransactionManager` može doći kasnije, kada donosi stvarnu korist.

### Korak 5 — Uvesti isporuku poruka i odvojiti je od lokalnog loggera

Mail servis i logger imaju različite odgovornosti. Prvi dostavlja dokaz korisniku; drugi beleži događaj za održavanje. Auth link ne treba da bude uspešno „poslat“ zato što je dodat red u log fajl.

Kasnije, ako pouzdanost to zahteva, može se dodati outbox obrazac: zapis događaja za slanje u istoj transakciji, pa odvojeni proces dostave. Za sada je dovoljan mali interfejs i jasna obrada neuspeha, bez obaveznog queue sistema.

### Korak 6 — Ukloniti istorijske ostatke

`index.php` posle reda 321 sadrži veliku staru komentarisanu implementaciju. Ona nije aktivna ranjivost, ali otežava pregled i povećava šansu da čitalac analizira pogrešnu verziju toka. Verzije treba čuvati u Git istoriji; buduća izmena može ukloniti mrtvi blok.

`views/auth/login.php` samo ispisuje `login` i nijedna aktivna ruta ga ne koristi. Ukloniti ili jasno odrediti njegovu ulogu. Isto važi za stari komentarisani validator telefona i ostale razvojne ostatke.

Stilsko usaglašavanje, poput razmaka, jedne forme vitičastih zagrada i doslednih return tipova, dolazi posle funkcionalnih i bezbednosnih popravki. `declare(strict_types=1)` može pomoći otkrivanju pogrešnih tipova, ali ne zamenjuje validaciju HTTP unosa niti automatski popravlja postojeće greške.

---

## 10. Plan provera posle budućih izmena

Sledeće provere **nisu izvršene nad stvarnom aplikacijom u ovom read-only pregledu**. Ovo je konkretan plan za zasebnu test bazu i test server kada se odobre izmene.

### 10.1. Funkcionalni scenariji

| Scenario | Očekivan rezultat |
| --- | --- |
| Ispravna registracija | Jedan korisnik, hash lozinke, nepotvrđen email, token i stvarna testna email poruka. |
| Neispravni podaci | Vidljive greške i 303 povratak; nema upisa korisnika ni lozinke u flash. |
| Duplikat emaila | Kontrolisan odgovor; isto važi za dva paralelna zahteva. |
| Login pre verifikacije | Odbijen posle tačne provere lozinke, uz jasno uputstvo. |
| Ispravna verifikacija | POST potvrdi email i potroši token; GET ga samo prikazuje. |
| Prazan/istekao/potrošen verify token | Uredna 400 stranica, bez nedostajućeg include-a. |
| Resend | Validaciona greška ili generička poruka uz 303; nikada prazna stranica. |
| Prijavljen korisnik otvara forgot-password | Ispravan redirect na dashboard i definisan korisnik. |
| Reset lozinke | Nova lozinka radi; stara ne radi; prethodne sesije i remember tokeni se odbijaju. |
| Logout | Trenutna sesija završena i odgovarajući remember token opozvan. |

### 10.2. Bezbednosne i granične provere

1. Svaki POST poslati bez CSRF tokena, sa pogrešnim tokenom i sa `_token[]`; svi moraju kontrolisano da budu odbijeni.
2. Poslati `email[]`, `password[]`, `password_confirmation[]`, `token[]` i `route[]`; ne sme biti neobrađenih upozorenja ili izuzetaka u odgovoru.
3. Proveriti Unicode lozinke oko 72 bajta, NUL, veoma veliki unos i duže passphrase lozinke u okviru izabrane politike.
4. Uvesti prepoznatljivu testnu tajnu; proveriti da se nigde ne pojavljuje u HTML greškama, flash podacima i logovima.
5. Višestrukim login pokušajima, uz različite sesije, potvrditi email/IP limite. Za nepoznat nalog proveriti i ponašanje dummy hash putanje.
6. Posle resetovanja pokušati ponovnu upotrebu istog tokena i već otvorene stare forme. Oba pokušaja moraju biti odbijena.
7. Za remember me proveriti neispravan format, pogrešan validator, istek, suspendovanog korisnika, stari token posle rotacije i očuvanje prvobitnog roka.
8. U produkcionoj test konfiguraciji proveriti `Set-Cookie`, reset/verify referrer i cache zaglavlja i zabranu pristupa privatnim logovima. Ne slati stvarne tajne tokom ovih provera.
9. Posle budućeg refaktora proveriti deployment i u root-u domena i u poddirektorijumu.

### 10.3. Konkurentnost i kvarovi

Ove provere imaju veću vrednost od velikog broja testova koji samo ponavljaju getter metode:

- Dva zahteva istovremeno troše isti reset token: najviše jedan sme da promeni lozinku.
- Dva zahteva koriste isti remember token: najviše jedna rotacija uspeva; konačni cookie treba da ostane upotrebljiv prema dokumentovanoj politici.
- Zaustaviti login nakon provere stare lozinke, izvršiti reset, pa nastaviti rehash: stara lozinka ne sme biti vraćena.
- Zaustaviti login pre remember izdavanja, izvršiti reset, pa nastaviti: stari kontekst ne sme proizvesti važeći token za novu auth verziju.
- Simulirati grešku usred reseta: ni lozinka ni auth verzija ni tokeni ne smeju ostati delimično izmenjeni.
- Simulirati pad mail transporta, upisa loga i opoziva tokena pri logout-u: ishod mora biti kontrolisan i bez lažnog obećanja o uspehu.

Za prve četiri provere potrebne su nezavisne konekcije/zahtevi i kontrolisane tačke zaustavljanja, ne samo brzi uzastopni pozivi u jednoj sesiji.

### 10.4. Evidencija već izvršenih izolovanih provera

| Provera | Zabeležen rezultat |
| --- | --- |
| Sintaksa svih PHP fajlova | 44/44 prolazi. |
| Confirmation u errors poruci | Pun testni string je prisutan. |
| Confirmation kao niz | `Array to string conversion`. |
| Neispravan forgot email | Čitanje svojstva niza + `trim(null)` deprecation. |
| NUL lozinka | Validator prihvata; bcrypt hash baca `ValueError`. |
| Unicode 19 znakova / 73 bajta | Obe različite lozinke prihvaćene; hash prve verifikuje drugu. |
| `session.use.only.cookies` | Direktiva ne postoji. |
| Kasno postavljen production env | Session secure parametar ostaje `false`. |
| Reset između čitanja i rehash upisa | Stara lozinka radi, nova ne; DB/session verzije su 2/1 u memorijskoj simulaciji. |

Izolovani primeri koristili su izmišljene podatke. Nije promenjen korisnik, stvarna lozinka, token, baza ili aplikacioni log.

---

## 11. Završna ocena

**Završna pedagoška ocena: 7/10 za ovu fazu učenja.** To je ocena kvaliteta razmišljanja i izvedbe u mini OOP projektu, a ne potvrda da je sistem bezbedan za produkciju.

| Oblast | Ocena | Obrazloženje |
| --- | --- | --- |
| Razdvajanje odgovornosti i OOP | 8/10 | Servisi, ugovori, repozitorijumi, DTO i enum imaju konkretnu svrhu. |
| Osnovni bezbednosni mehanizmi | 7/10 | Dobre osnove za hash, CSRF, tokene i opoziv sesija; važni propusti u povezivanju tih delova. |
| Potpunost korisničkih tokova | 5/10 | Nema email isporuke; resend, error view, dashboard grana i flash poruke imaju greške. |
| Pouzdanost pod greškama i konkurencijom | 5/10 | Reset transakcija je dobra, ali rehash/token issuance i rubni ulazi nisu dovoljno povezani. |
| Čitljivost i održavanje | 7/10 | Glavni tok je razumljiv; duplikacija, mrtvi kod i nedosledna imena stvaraju šum. |

Ocena nije aritmetički prosek tabele, već pedagoški sud za zadati nivo projekta. Najviše vrednujem što su prisutne naprednije ideje koje imaju smisla — jednokratni tokeni, zaključavanje i verzionisanje sesije — a ne samo osnovno poređenje emaila i lozinke.

Za produkciju je trenutni zaključak: **potrebne su popravke i integraciona provera pre objavljivanja**. Glavni razlog nisu `switch`, odsustvo MVC-a ili manjak frameworka. Razlog su konkretne garancije koje još ne važe u svim tokovima.

Najkorisnija sledeća lekcija je da bezbednost nije zbir dobrih funkcija. Potrebno je da njihove veze budu ispravne: konfiguracija mora prethoditi upotrebi, validacija prethoditi pristupu podacima, reset nadjačati stari login, a poruka korisniku odgovarati onome što je stvarno izvršeno.

---

## 12. Inventar pregledanih fajlova

Inventar omogućava da se obim proveri i kada se dokument odštampa. Broj PHP fajlova je 44; izveštaj nije uračunat u taj broj.

### Ulazna tačka — 1 fajl

- `index.php`

### Auth servisi — 5 fajlova

- `src/Auth/AuthService.php`
- `src/Auth/EmailVerificationService.php`
- `src/Auth/PasswordResetService.php`
- `src/Auth/RegistrationService.php`
- `src/Auth/RememberMeService.php`

### Ugovori — 5 fajlova

- `src/Contracts/EmailVerificationRepository.php`
- `src/Contracts/PasswordRequest.php`
- `src/Contracts/PasswordResetTokenRepository.php`
- `src/Contracts/RememberTokenRepository.php`
- `src/Contracts/UserRepository.php`

### Baza, DTO, enum i model — 7 fajlova

- `src/Database/Database.php`
- `src/Dto/PasswordResetData.php`
- `src/Dto/UserLoginData.php`
- `src/Dto/UserRegisterData.php`
- `src/Enums/LoginResult.php`
- `src/Enums/UserStatus.php`
- `src/Models/User.php`

### Kontroleri — 4 fajla

- `src/Http/Controllers/EmailVerificationController.php`
- `src/Http/Controllers/LoginController.php`
- `src/Http/Controllers/RegistrationController.php`
- `src/Http/Controllers/ResetPasswordController.php`

### Request sloj — 4 fajla

- `src/Http/Requests/ForgotPasswordRequest.php`
- `src/Http/Requests/Request.php`
- `src/Http/Requests/UserLoginRequest.php`
- `src/Http/Requests/UserRegisterRequest.php`

### Repozitorijumi — 4 fajla

- `src/Repositories/PdoEmailVerificationRepository.php`
- `src/Repositories/PdoPasswordResetTokenRepository.php`
- `src/Repositories/PdoRememberMeTokenRepository.php`
- `src/Repositories/PdoUserRepository.php`

### Helper, bezbednost i sesija — 4 fajla

- `src/Helpers/Logger.php`
- `src/Helpers/Validator.php`
- `src/Security/Ratelimiter.php`
- `src/Session/Session.php`

### View sloj — 10 fajlova

- `views/dashboard.php`
- `views/auth/email-verify.php`
- `views/auth/forgot-password.php`
- `views/auth/login.php`
- `views/auth/loginpage.php`
- `views/auth/register.php`
- `views/auth/resend-verification.php`
- `views/auth/reset-password.php`
- `views/errors/403.php`
- `views/errors/invalid-reset-token.php`

### Dodatni fajlovi unutar dozvoljenog obima

- `src/private/info.log` — proverena vrsta zapisa; auth tokeni nisu objavljeni u izveštaju.
- `src/private/log.txt` — prazan fajl.

**Potvrda obima izmene:** ovaj pregled ne sadrži popravke aplikacionog koda. Jedini dodatak je `src/reports/auth-report.md`; commit nije napravljen.

