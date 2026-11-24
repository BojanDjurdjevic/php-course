# Questions

## Composer intervju — Pitanje 1/12

Zamisli da na intervjuu dobiješ pitanje:

Šta je Composer u PHP-u i koje su njegove dve glavne odgovornosti?

Odgovori kao na pravom intervjuu, svojim rečima. Posle ti dajem ocenu, kratku korekciju i idemo na 2/12.

---

Composer je dependency manager za PHP (pandan npm/pnpm za JS).
On rešava dependency graph + učitavanje kako naših klasa tako i third party klasa preko autoloader-a.
Dependencies instalira uglavnom u vendor folder i preko composr.lock fajla odrzava konkretno resolved stanje zavisnosti.

---

Odlično. 9.8/10. Suštinu si pogodio skoro kompletno.
Posebno dobro što nisi ostao na „instalira biblioteke“,
nego si pomenuo dependency graph, vendor/, composer.lock i autoloading.
Jedina sitna terminološka korekcija: umesto „učitavanje naših i third-party klasa“,
preciznije bih rekao generiše/obezbeđuje autoloading sistem za njih.
Intervju-ready verzija tvog odgovora bi bila:

Composer je dependency manager za PHP, pandan npm/pnpm-u u
JavaScript ekosistemu. Njegove dve glavne odgovornosti su dependency
management — rešavanje dependency graph-a, kompatibilnih verzija i instalacija
paketa — i autoloading naših i third-party klasa. Pakete uglavnom instalira u vendor/,
dok composer.lock čuva konkretno resolved stanje dependency-ja.

To je praktično pun odgovor. ✅

---

## Pitanje 2/12

Klonirao si PHP projekat sa GitHub-a. Repo sadrži:

```text
composer.json
composer.lock
```

ali nema vendor/.
Koju Composer komandu pokrećeš i zašto baš nju, a ne composer update?

---

Pokrećem composer install.

Zato sto ona instalira sve prema vec zakljucanim verzijama u lock fajlu, resolved stanje.

Ako bih uradio update, composer bi čitao json fajl pa ponovo pokusavao da resolvuje
i tu postoji mogucnost da verzije zavisnosti koje ja dobijem ne budu iste kao na github repo-u.

---

Odlično — 10/10.
Baš kako treba za intervju. Posebno je dobro što si naveo i zašto je update
problematičan u tom scenariju, a ne samo šta radi install.

Tvoj odgovor praktično već jeste interview-ready:

Pokrećem composer install, jer ako postoji composer.lock,
Composer instalira tačno resolved verzije dependency-ja zapisane u lock fajlu.

```bash
composer update bi ponovo rešavao constraints iz composer.json i mogao bi da
```

izabere novije dozvoljene verzije, pa moj dependency set više ne bi morao biti
isti kao onaj koji je testiran i commitovan u repozitorijumu.

Mala terminološka nijansa: ne bih rekao „iste kao na GitHub repo-u“,
nego iste kao resolved dependency state koji je commitovan u repo.
Ali značenje tvog odgovora je potpuno tačno.

---

## Pitanje 3/12

U composer.json vidiš:

```json
{
    "require": {
        "vendor/package": "^2.4.3"
    }
}
```

---

## Koje verzije ovaj constraint dozvoljava? I šta bi se promenilo da umesto toga piše ~2.4.3?

---

Ovaj constraint, mislim da se zove caret, kaze:

"sve verzije od 2.4.3 do 2.9.9 ali ne i 3.0.0" dakle >=2.4.3 < 3.0.0

Dok bi sa tilde ~ bilo:

Sve verzije od 2.4.3 do 2.5.0 ali ne i 2.5.0, dakle <2.5.0

---

```php
require je ono sto zelimo da ide i u produkciju i u development (automatski dostupno u developmentu),
```

a require-dev tu stavljamo ono sto samo zelimo u razvoju, na primer tests.

Onda u produkciji kazemo --no --dev i instalira se samo sto je u require.

Ako bismo greskom stavili ono sto ide u produkciju u require-dev,
app bi nam verovatno pukao jer ne bi radio bez potrebnih zavisnosti kako treba
Tačno — 9.7/10.

Suštinu si pogodio: require je za dependency-je koje production runtime zaista treba,
dok require-dev služi za testiranje, statičku analizu, debug alate i sličan development tooling.

Samo dve sitne korekcije. Komanda je:

```bash
composer install --no-dev
```

ne --no --dev.

I posledicu bih formulirao preciznije: ako production dependency greškom staviš u require-dev, lokalno sve može raditi jer običan composer install instalira i dev dependencies. Ali na serveru:

```bash
composer install --no-dev
```

taj package neće biti instaliran, pa production kod može završiti npr. sa:

---

## Class "Some\Pdf\Generator" not found

Dakle klasično:

```text
development ✅
production ❌
```

---

## Pitanje 5/12

Objasni razliku između sledeće tri stvari:

namespace
use
PSR-4
Posebno me zanima: da li namespace App\Services; sam po sebi govori PHP-u da klasu traži u src/Services/ folderu?

Namespace je prostor u okviru kog se nalazi na primer odredjena klasa ALI to navodimo mi
i PHP ne trazi eksplicitno da namespace prati naziv konkretnog foldera, dakle mi mozemo nazvati kako hocemo,
medjutim ovo kasnine resava PSR-4.

use; bi bio neki ekvivalent import-u u
node (npm) i to znaci:

Koristi ovu klasu u iovom fajlu.
Npr u index.php:

use App/Services/BookingService.php
-> index koristi servis. Ako je
Namespace pogresan ili klasa pogresno napisana ili cak pogresno podesen composer.json,
dobili bismo nesto poput: not found class...

PSR-4 je autoload standard ucitavanja fajlova.

---

Dobar odgovor, ali ovde imamo par bitnih nijansi koje vredi zategnuti. 8.8/10.

---

## Najbolji deo ti je prvi: tačno si rekao da namespace nije folder i

da PHP sam ne zahteva da se namespace poklapa sa fizičkom strukturom. Dakle:

```php
namespace App\Services;
```

sam po sebi ne znači:

src/Services/
Tu vezu kasnije pravi PSR-4 mapping.

Kod use samo jedna važna korekcija:

nije baš „učitaj/koristi ovaj fajl“,
nego uvoz imena klase u trenutni namespace/fajl radi kraćeg name resolution-a. Dakle ne:

use App/Services/BookingService.php;

nego:

```php
use App\Services\BookingService;
```

Bez .php, i namespace separator je \, ne /.

Još važnije:

```php
use App\Services\BookingService;
```

ne učitava fajl kao require. Ono samo kaže:

```text
BookingService
→ znači App\Services\BookingService
```

A autoloader kasnije učita odgovarajući fajl kada PHP stvarno zatraži tu klasu.

PSR-4 deo bih malo preciznije formulisao:

PSR-4 je autoloading standard koji definiše kako se namespace/class name mapira na filesystem path.

Na primer:

```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

onda:

```text
App\Services\BookingService
→ src/Services/BookingService.php
```

Dakle tvoj mentalni model je dobar; samo use moraš odvojiti od stvarnog loading-a.

---

## Pitanje 6/12

Imaš ovo u composer.json:

```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

i klasu:

```php
namespace App\Services;
```

final class BookingService {}

Na kom tačno path-u Composer očekuje taj fajl i kako dolazi do tog path-a?

---

Ocekuje ga na:

src/Services/BookingService.php

---

## Dolazi tako sto vidi da je App -> src

Dakle ukloni App a postavi src

Backslash pretvara u slash

Zatim čita ostatak namespace Service + ime klase pa jos doda .php

---

Tačno — 10/10.

Baš si lepo opisao PSR-4 algoritam:

```text
App\Services\BookingService
↓
App\ → src/
↓
Services\BookingService
↓
Services/BookingService
↓
src/Services/BookingService.php
```

Jedina mikronijansa: namespace deo je Services, množina, ali jasno je da je samo typo u odgovoru.

---

## Pitanje 7/12

Šta tačno radi:

```bash
composer dump-autoload
```

i kada ti zaista treba?

---

Posebno: ako samo dodaš novu klasu u već postojeći PSR-4 mapping, da li moraš da pokreneš dump-autoload?

Ne moram, zato sto prema PSR-4 mappingu svaku novu klasu koju dodamo vec se automatski vidi u mapi.

dump-autoload radimo samo kada treba regenerisati autoload metadata.

Na primer kada promenimo autoload konfiguraciju u json fajlu.

---

Tačno — 9.8/10.

---

## Jedina sitna terminološka korekcija: kod običnog PSR-4 autoloadinga nova klasa se

ne „upisuje automatski u mapu“, nego Composer već zna pravilo:

```text
App\ → src/
```

pa može dinamički da izračuna path za novu klasu kada zatreba.

Dakle:

```text
nova klasa u postojećem PSR-4 mapping-u
→ obično NE treba dump-autoload
```

```text
promena autoload konfiguracije
→ treba composer dump-autoload
```

I tačno: composer dump-autoload regeneriše Composer autoload metadata.

---

## Pitanje 8/12

Objasni razliku između ova tri pojma:

```text
composer.json
composer.lock
vendor/
```

I posebno: zašto se composer.lock commit-uje, a vendor/ uglavnom ne?

---

composer.json je konfiguracioni fajl i u
njemu stji ono sto smo trazili sa require.

composer.lock je fajl u kome su
zakljucane verzije instaliranih paketa,
odatle composer cita za komandu install.

Vendor/ je folder u koji se instaliraju
zavisnosti i on se ne komituje, ne zato
sto je opasno, nego nema potrebe zbog
njegove tezine.

Svakako ce kompozer citati iz lock-a i
zato je preporucen komit i json i lock
fajla.

---

Vrlo dobro — 9.6/10.
Tačno si razdvojio sva tri sloja:

```text
composer.json
→ šta projekat zahteva / dozvoljava
```

```text
composer.lock
→ koje konkretne verzije su resolved
```

```text
vendor/
→ stvarno instalirani dependency fajlovi
```

Jedina važnija dopuna: composer.lock ne čuva samo verzije tvojih direktnih paketa,
nego konkretan resolved state celog dependency graph-a, uključujući transitive dependencies.
I za vendor/ si u suštini u pravu, samo bih razlog
formulisao malo šire od same težine foldera. Ne commitujemo ga jer je
generisani/install output koji možemo reproducirati iz composer.json + composer.lock preko:

```bash
composer install
```

Veličina je dodatni razlog, ali reproducibilnost je glavni.
Dakle interview-ready verzija bi bila:

composer.json opisuje dependency constraints i Composer konfiguraciju, composer.lock čuva
konkretno resolved stanje celog dependency graph-a i commitujemo ga da svi dobiju isti dependency set,
dok je vendor/ instalirani output koji možemo ponovo generisati sa composer install, pa se uglavnom ne commituje.

---

## Pitanje 9/12

Šta je razlika između:

```text
autoload
autoload-dev
```

u composer.json?
I šta se dešava sa autoload-dev pravilima kada na produkciji radiš:

```bash
composer install --no-dev
```

---

autoload sadrzi sve klase koje zelimo i u produkciji dok
autoload-dev sadrzi klase i fajlove koje samo zelimo u razvoju.

Tako da autoload-dev pravila se ne izvrsavaju u produkciji.

autoload sadrzi sve klase koje zelimo i u produkciji dok
autoload-dev sadrzi klase i fajlove koje samo zelimo u razvoju.

Tako da autoload-dev pravila se ne izvrsavaju u produkciji.

---

Tačno — 9.7/10.

Samo mala terminološka korekcija: autoload
i autoload-dev ne „sadrže klase“, nego autoload pravila/mappinge za njih.

Dakle preciznije:

```text
autoload
→ pravila za application/runtime code
```

```text
autoload-dev
→ pravila za test/dev code
```

Na primer:

```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
},
```

```json
"autoload-dev": {
    "psr-4": {
        "Tests\\": "tests/"
    }
}
```

A sa:

```bash
composer install --no-dev
```

Composer ne instalira require-dev dependencies
i ne uključuje autoload-dev pravila u generisani autoloader.

---

## Pitanje 10/12

Objasni razliku između ova tri Composer autoload mehanizma:

PSR-4
classmap
files
Ne treba enciklopedijski — reci mi kako svaki pronalazi/učitava
kod i kada bi ga tipično koristio.

```text
PSR-4
→ namespace → predvidiv filepath
→ default za moderan OOP PHP
```

```text
classmap
→ skeniraj klase i napravi eksplicitnu mapu
→ koristan za legacy/non-standard code
```

```text
files
→ učitaj fajl pri bootstrapu autoloadera
→ helpers/functions/constants
Odlično — 10/10. To je baš nivo odgovora koji bih želeo na intervjuu: kratak,
```

precizan i bez suvišnog objašnjavanja.

Posebno dobro što si odmah vezao mehanizam za tipičan use case:

```text
PSR-4
→ predvidiv namespace → path mapping
→ moderan OOP PHP
```

```text
classmap
→ eksplicitna mapa klasa nakon skeniranja
→ legacy / non-standard struktura
```

```text
files
→ fajl se učitava pri bootstrapu Composer autoloadera
→ helpers, funkcije, konstante
Nema šta da popravljam. ✅
```

---

## Pitanje 11/12

U projektu vidiš da je instaliran package:

psr/log
ali ga nema u tvom composer.json.

---

## Kako je moguće da je instaliran i kojom Composer komandom možeš proveriti zašto je tu?

Prvo: kada trazimo da se instalira neki
paket, composer trazi i njegove
zavisnosti bez koje on ne moze raditi pa
odmah instalira i njih, iako ih nismo direktno trazili.

Ovo proverimo komandom:

composer why
I on nam da objasnjenje.
Tačno — 10/10.

Upravo to: psr/log može biti transitive dependency — neki paket
koji si ti direktno instalirao zavisi od njega, pa ga Composer povuče automatski.

Za konkretan slučaj najpreciznije bi bilo:

composer why psr/log

i Composer će ti pokazati koji package ga zahteva i kojim constraint-om.

Dobar intervju odgovor bi bio:

Paket može biti instaliran iako nije direktno naveden u mom composer.json,
zato što je transitive dependency nekog drugog paketa. To proveravam sa composer why psr/log.

---

## Pitanje 12/12

Poslednje pitanje, malo praktičnije:

Na produkcionom serveru želiš da instaliraš dependency-je iz
postojećeg composer.lock, bez development paketa, sa optimizovanim autoloadom,
a zatim da proveriš da li stvarna PHP verzija i potrebne extensions zadovoljavaju zahteve projekta.

---

## Koje Composer komande bi koristio i šta svaka radi?

Deployment može konceptualno imati:

```bash
composer install --no-dev --optimize-autoloader
```

composer check-platform-reqs

Ako platform requirements nisu zadovoljeni:

abort deploy

Inace, --no-dev da bismo instalirali samo za prodiukciju a
--optimize-autoloader
Da bismo optimizovali prema
production friendly okruzenju.

Takodje korisno:

composer diagnose

---

## Može proveriti Composer environment/config i prijaviti probleme

Odlično — 10/10.

Glavni deo je potpuno tačan:

```bash
composer install --no-dev --optimize-autoloader
```

composer check-platform-reqs

Tačno značenje:

```text
composer install
→ koristi postojeći composer.lock
```

```text
--no-dev
→ ne instalira require-dev dependencies
```

```text
--optimize-autoloader
→ generiše production-friendly optimizovan autoload
```

```text
composer check-platform-reqs
→ proverava stvarni PHP + extensions na serveru
I da — ako platform requirements nisu zadovoljeni,
```

deploy treba zaustaviti, a ne rešavati problem sa --ignore-platform-reqs.

composer diagnose si dobro pomenuo kao dodatnu troubleshooting komandu,
samo nije obavezan deo ovog deployment flow-a.

---

## Composer intervju završen — 12/12 ✅

Ukupno si bio veoma jak. Moja zbirna ocena: 9.7/10.

---

## CODING

Coding zadatak 1/5

Napravi minimalni composer.json za Vanilla PHP aplikaciju sa ovim zahtevima:

- projekat zahteva PHP 8.3+ unutar 8.x serije
- zahteva ext-pdo
- application namespace App\ treba da mapira na src/
- test namespace Tests\ treba da mapira na tests/
- PHPUnit treba da bude samo development dependency
Napiši ceo composer.json.

---

```json
{
    "name": "bojan/ministay",
    "description": "Vanilla PHP booking application",
    "type": "project",
    "require": {
        "php": "^8.3",
        "ext-pdo": "*"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

---

Vrlo dobro — struktura ti je tačna,
ali fali jedna tražena stavka: PHPUnit u require-dev.

Zato 9.2/10.
Tvoj fajl treba samo da dobije:

```json
"require-dev": {
    "phpunit/phpunit": "^12.0"
}
```

Kompletno:

```json
{
    "name": "bojan/ministay",
    "description": "Vanilla PHP booking application",
    "type": "project",
    "require": {
        "php": "^8.3",
        "ext-pdo": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^12.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

Sve ostalo si pogodio:

```text
- ^8.3 → >=8.3 <9.0
- ext-pdo u runtime require
- App\ → src/
- Tests\ → tests/
```

- validan JSON i dobra osnovna metadata.

---

## Coding zadatak 2/5

Imaš ovaj composer.json:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

Napiši tačan path i kompletan PHP fajl za klasu:

App\Repositories\PdoBookingRepository

Klasa treba da bude final,
da u konstruktor primi PDO i sačuva ga kao private property.

---

Path:

src/Repositories/PdoBookingRepository.php

File:

```php
require _DIR_ './../vendor/autoloader.php

namespace Repositories;
use PDO;

final class PdoBookingRepository {
  public function __construct(
    private PDO $pdo
  )

  // Ostatak logike.. sql itd
}
```

PS. Ako si bas mislio da osmislim i napisem ceo repositorijum vrati mi zadatak.
Mislio sam da je bitan samo ovaj deo zbog composer-a

---

Da, dobro si procenio — nisam tražio SQL logiku repozitorijuma.
Bitan je bio Composer/PSR-4 deo i minimalna klasa.
Ovde bih dao 7.8/10, jer je path pogođen savršeno, ali ima nekoliko bitnih detalja:

Path:

src/Repositories/PdoBookingRepository.php

✅ potpuno tačno.
Ali namespace mora da odgovara celom FQCN-u:

```php
namespace App\Repositories;
```

ne:

```php
namespace Repositories;
```

Drugo, u samom class fajlu ne treba da radiš:

```php
require ...
```

vendor/autoload.php se tipično učita jednom u entry point-u, npr. public/index.php.
Posle toga Composer učitava ovu klasu kada zatreba.

I još dve sintaksne sitnice: fajl je autoload.php, ne autoloader.php, a konstruktor mora imati telo.

Minimalno ispravno:

```php
<?php

namespace App\Repositories;

use PDO;

final class PdoBookingRepository
{
    public function __construct(
        private PDO $pdo
    ) {}
}
```

Dakle ključna veza je:

```text
App\Repositories\PdoBookingRepository
↓ App\ → src/
Repositories\PdoBookingRepository
↓
src/Repositories/PdoBookingRepository.php
```

---

## Coding zadatak 3/5

Imaš ovaj setup:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

Struktura projekta je:

```text
src/
└── Services/
    └── PaymentService.php
```

A fajl trenutno sadrži:

```php
<?php

namespace App\Service;

final class PaymentService
{
}
```

U drugom fajlu imaš:

```php
use App\Services\PaymentService;

$service = new PaymentService();
```

Dobijaš Class not found.
Ispravi problem i ukratko objasni zašto Composer nije mogao da pronađe klasu.

---

Trebalo bi da je jednostavno ovo:

Ispraviti namespace:

```text
Umesto App\Service
->
App\Services;
```

Pogresan namespacing i autoload
jednostavno ne moze da nadje
klasu pod tim namespace-om.

---

Tačno — 10/10.
Problem je upravo u neslaganju između očekivanog FQCN-a i onoga što fajl stvarno definiše.
Ispravno:

```php
<?php

namespace App\Services;

final class PaymentService
{
}
```

Zašto? Composer po mapping-u:

```text
App\ → src/
```

za:

App\Services\PaymentService

očekuje:

src/Services/PaymentService.php

Taj fajl postoji, ali je unutra definisana druga klasa:

App\Service\PaymentService

što nije isto kao:

App\Services\PaymentService

Dakle fajl fizički postoji, ali ne definiše FQCN koji autoloader traži.

---

## Coding zadatak 4/5

Imaš projekat sa ovim composer.json:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

Dodaš novi fajl:

src/Services/ReportService.php

sa:

```php
<?php

namespace App\Services;

final class ReportService
{
}
```

Zatim u controller-u napišeš:

```php
use App\Services\ReportService;

$report = new ReportService();
```

Da li treba da pokreneš:

```bash
composer dump-autoload
```

da bi nova klasa proradila?
Ako ne, navedi jedan slučaj kada bi dump-autoload ipak bio potreban.

---

U ovom slucaje ne treba

```bash
composer dump-autoload
```

Jer

Autoload automatski po svojim mapping pravilima vec vidi ovu klasu.

Medjutim, ako bih rucno izmenio composer.json, i dodao na primer jos neke mapping spaces, onda dda azurirao, morao bih da pokrenem komandu.

---

Tačno — 9.8/10.
Jedina mikro-korekcija je formulacija „autoload već vidi ovu klasu“. Preciznije:

Composer već zna PSR-4 pravilo App\ → src/, pa kada PHP zatraži App\Services\ReportService, može da izračuna path src/Services/ReportService.php.

Zato nova pravilno smeštena PSR-4 klasa ne traži novi dump.
A drugi deo si pogodio potpuno: ako promeniš samu autoload konfiguraciju u composer.json, recimo dodaš:

```json
"Domain\\": "domain/"
```

onda:

```bash
composer dump-autoload
```

regeneriše autoload metadata sa novim mapping-om.

---

## Coding zadatak 5/5

Poslednji zadatak spaja praktično ceo modul.
Imaš novi Vanilla PHP projekat sa strukturom:

```text
MiniStay/
├── public/
│   └── index.php
├── src/
│   ├── Contracts/
│   │   └── BookingRepository.php
│   ├── Repositories/
│   │   └── InMemoryBookingRepository.php
│   └── Services/
│       └── BookingService.php
└── tests/
```

Treba da napišeš:

1. minimalni composer.json gde je App\ → src/, Tests\ → tests/, PHP je ^8.3, a PHPUnit dev dependency;
2. kompletan public/index.php koji učitava Composer autoloader i kreira InMemoryBookingRepository, pa ga dependency injection-om prosleđuje u BookingService;
3. namespace i minimalnu definiciju za BookingRepository, InMemoryBookingRepository i BookingService.

Ne treba booking business logika — fokus je na Composer + namespaces + PSR-4 + DI povezivanju svega zajedno.

---

```json
{
    "name": "bojan/ministay",
    "description": "Vanilla PHP booking application",
    "type": "project",
    "require": {
        "php": "^8.3",
        "ext-pdo": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^12.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

index:

```php
<?php

require _DIR_ './../vendor/autoload.php;

use InMemoryBookingRepository;
use BookingStatus;
use BookingService;

$repository = new InMemoryBookingRepository(1, 5, BookingStatus::pending);

$service = new BookingService($repository);
?>

namespace App\Contracts;

interface BookingRepository {
  public function create(): void;
}

namespace App\Repositories;

use BookingRepository;

final class InMemoryBookingRepository
```

```text
implements BookingRepository
 {
  public functiin create() {
     // logika
  }
}
```

```php
namespace App\Services;

use InMemoryBookingRepository;
```

```text
final class BookingService() {
  public function __construct(
    private  InMemoryBookingRepository $repository
   ) {}
}
```

---

Da — ideja ti je dobra, ali ovde ima nekoliko važnih grešaka baš u delovima koje zadatak proverava. Dao bih 7.6/10.
composer.json ti je praktično odličan. ext-pdo nisam tražio, ali nije problem — samo je nepotreban za ovaj konkretan zadatak.
Najveće korekcije su u PHP delu.
1. index.php
Ovo:

```php
require _DIR_ './../vendor/autoload.php;
```

treba:

```php
require __DIR__ . '/../vendor/autoload.php';
```

A use mora sadržati FQCN, ne samo ime klase:

```php
use App\Repositories\InMemoryBookingRepository;
use App\Services\BookingService;
```

BookingStatus nam u ovom zadatku uopšte nije potreban.
Takođe repository nismo definisali da prima 1, 5, `BookingStatus::Pending`, pa je dovoljno:

```php
$repository = new InMemoryBookingRepository();
$service = new BookingService($repository);
```

Dakle:

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\InMemoryBookingRepository;
use App\Services\BookingService;

$repository = new InMemoryBookingRepository();

$service = new BookingService($repository);
```

---

## 2. BookingRepository.php

Path:

src/Contracts/BookingRepository.php

Tvoj namespace je dobar:

```php
<?php

namespace App\Contracts;

interface BookingRepository
{
    public function create(): void;
}
```

✅

---

## 3. InMemoryBookingRepository.php

Path:

src/Repositories/InMemoryBookingRepository.php

Ovde si napisao:

```php
use BookingRepository;
```

ali treba:

```php
use App\Contracts\BookingRepository;
```

jer je pravi FQCN:

App\Contracts\BookingRepository

Kompletno:

```php
<?php

namespace App\Repositories;

use App\Contracts\BookingRepository;

final class InMemoryBookingRepository implements BookingRepository
{
    public function create(): void
    {
        // logika
    }
}
```

Imao si i typo:

functiin

ali to ti ne bih ozbiljno računao jer očigledno kucaš sa telefona.

---

## 4. BookingService.php

Ovde je najvažnija konceptualna greška.
Napisao si:

```php
private InMemoryBookingRepository $repository
```

Ali upravo smo u OOP modulu vežbali DIP:

Service treba da zavisi od abstrakcije, ne od konkretne implementacije.

Dakle ne:

```text
BookingService
↓
InMemoryBookingRepository
```

nego:

```text
BookingService
↓
BookingRepository
↑
InMemoryBookingRepository
```

Ispravno:

```php
<?php

namespace App\Services;

use App\Contracts\BookingRepository;

final class BookingService
{
    public function __construct(
        private BookingRepository $repository
    ) {}
}
```

To nam omogućava da sutra ubacimo:

```php
new PdoBookingRepository($pdo)
```

bez menjanja BookingService.
I još sintaksno:

final class `BookingService()`

nije validno — klasa nema ():

```php
final class BookingService
```

---

## Cela slika

Tvoj index.php pravi konkretnu implementaciju:

```php
$repository = new InMemoryBookingRepository();
```

ali service prima contract:

```php
new BookingService($repository);
```

jer:

```text
InMemoryBookingRepository
implements
BookingRepository
```

To je lepo spajanje dva modula:

```text
Composer / PSR-4
+
Namespaces
+
Interfaces
+
Dependency Injection
+
DIP
```

A Composer tok je:

```text
public/index.php
↓
require vendor/autoload.php
↓
use App\Repositories\InMemoryBookingRepository
↓
PSR-4 App\ → src/
↓
src/Repositories/InMemoryBookingRepository.php
```

---

## Coding checkpoint završen — 5/5 ✅
