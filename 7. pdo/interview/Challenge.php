<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$host = $_ENV["DB_HOST"];
$dbName = $_ENV['DB_NAME'];
$dbUser = $_ENV['DB_USER'];
$dbPass = $_ENV['DB_PASS'];

$dsn = "mysql:host=$host;dbname=$dbName;charset=utf8mb4";

$pdo = new PDO(
    $dsn, $dbUser, $dbPass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);


/**

Imamo formu koja šalje:
$_POST['email']

Napiši kompletan PDO kod koji:
- traži user-a po email-u,
- koristi prepared statement,
- email ide kroz named placeholder,
- rezultat vraća kao PDO::FETCH_ASSOC,
- ako user ne postoji, $user treba da bude null, a ne false.
Pretpostavi da već imaš:
$pdo
$email

Dakle napiši samo deo od prepare() do finalnog $user.

*/

$email = "nikola@mail.com";

$sql = "SELECT id, name FROM users WHERE email = :email";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'email' => $email,
]);

if($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $user['id'] . ": " . " " . $user['name'];
} else {
    $user = NULL;
    var_dump($user);
}

echo "</br></br>";

/**
 * Dobro urađeno — 9.4/10.
Tehnički ti radi kako treba: prepare(), named placeholder, execute(), FETCH_ASSOC, i ako nema reda postavljaš $user = null.
Jedina zamerka je što je zadatak tražio da završimo sa vrednošću u $user, a ti si odmah ubacio i output logiku:
echo ...
var_dump(...)

To nije pogrešno, samo meša DB retrieval sa presentation/debug slojem.
Čistije bih napisao:
$sql = 'SELECT id, name FROM users WHERE email = :email';

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'email' => $email,
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user === false) {
    $user = null;
}

Ovde lepo imamo:
user pronađen
→ array

user nije pronađen
→ null

I koristim striktno:
$user === false

jer tačno znamo šta fetch() vraća kada nema reda.

Može i kraće:
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

ali prva verzija je eksplicitnija i bolja dok učimo.

----------------------------------------------------


PDO coding zadatak 2
Sada UPDATE + rowCount().
Pretpostavi da imaš:
$pdo
$roomId
$date

Napiši PDO kod koji izvršava:
UPDATE room_inventory
SET available = available - 1
WHERE room_id = :room_id
  AND date = :date
  AND available > 0

Posle izvršenja želim:
$success

da bude:
true  → tačno 1 red je decrementovan
false → nijedan red nije decrementovan

Koristi prepare(), execute() i rowCount().


$roomId = 18;
$date = '2026-11-11';

$sql = "UPDATE room_inventory
        SET available = available - 1
        WHERE room_id = :room_id
        AND date = :date
        AND available > 0";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'room_id' => $roomId,
    'date' => $date,
]);

$success = $stmt->rowCount() === 1 ? true : false; */

/**
 * Da — tačno si razumeo zadatak. Ovde nisam tražio FOR UPDATE ni eksplicitnu transakciju, jer je poenta bila baš atomic conditional UPDATE.
Za kod: 10/10.
Ovo je ključ:
$stmt->execute([
    'room_id' => $roomId,
    'date' => $date,
]);

$success = $stmt->rowCount() === 1;

Tvoj ternary:
$success = $stmt->rowCount() === 1 ? true : false;

radi potpuno isto, samo je redundantno jer izraz već vraća boolean.

Zašto je bezbedno bez FOR UPDATE? Zato što MySQL atomically izvršava:

UPDATE room_inventory
SET available = available - 1
WHERE room_id = :room_id
  AND date = :date
  AND available > 0

Ako dva request-a gađaju poslednju sobu:
available = 1

Request A
→ UPDATE pogodi red
→ available postaje 0
→ rowCount() = 1

Request B
→ njegov uslov available > 0 više ne prolazi
→ rowCount() = 0

Dakle za jedan red + jednu atomsku promenu eksplicitni SELECT ... FOR UPDATE nije nužan.
I pošto nismo otvorili transaction:
autocommit
→ uspešan UPDATE se odmah commit-uje

Ali kad rezervacija obuhvata, recimo:
3 datuma
+
INSERT booking
+
booking_items

onda bih očekivao eksplicitnu transakciju, jer sve te operacije moraju biti jedna atomic celina.

-------------------------------

PDO coding zadatak 3
Sada baš to.
Pretpostavi da imaš:
$pdo;
$roomId;
$checkIn = '2026-11-10';
$checkOut = '2026-11-13';
$userId;

Rezervacija traje 3 noći.
Napiši PDO flow koji:
1. otvara transakciju,
2. decrementuje availability za sva 3 inventory reda jednim UPDATE-om sa half-open range-om,
3. proverava da je rowCount() === 3,
4. ako nije 3 — baca exception i radi rollback,
5. ako jeste — pravi INSERT INTO bookings,
6. uzima $bookingId preko lastInsertId(),
7. radi commit(),
8. u catch proverava inTransaction() pre rollback-a.
Ne treba email niti FOR UPDATE; ovde želim baš atomic conditional UPDATE + transaction pristup.
 

$roomId = 1;
$checkIn = '2026-11-10';
$checkOut = '2026-11-13';
$userId = 1;

$start = new DateTimeImmutable($checkIn);
$end = new DateTimeImmutable($checkOut);
$period = $start->diff($end, true);

$pdo->beginTransaction();

try {

    $stmt = $pdo->prepare("UPDATE room_inventory
        SET available = available - 1
        WHERE room_id = :room_id
        AND date >= :date1
        AND date <= :date2
        AND available > 0"
    );

    $stmt->execute([
        'room_id' => $roomId,
        'date1' => $checkIn,
        'date2' => $checkOut,
    ]); 

    if($stmt->rowCount() !== $period->days) {
        throw new RuntimeException('There is no availability');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO bookings SET
        user_id = :user_id, room_id = :room_id, 
        check_in = :check_in, check_out = :check_out'
    );

    $stmt->execute([
        'user_id' => $userId,
        'room_id' => $roomId,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
    ]);

    $bookingId = $pdo->lastInsertId();

    $pdo->commit();


} catch (\Throwable $e) {
    
    if($pdo->inTransaction()) $pdo->rollBack();

    throw $e;
}*/

/**
 * rlo dobro složen ceo transaction flow, ali imaš jednu važnu booking grešku koja baš menja rezultat. Dao bih 8.7/10.
Problem je ovde:
AND date <= :date2

Za:
check_in  = 2026-11-10
check_out = 2026-11-13

imamo 3 noćenja:
10.
11.
12.

13. je checkout dan i ne troši inventory. Zato mora:
AND date >= :check_in
AND date < :check_out

Sa tvojim <=, query pokušava da decrementuje čak 4 datuma:
10, 11, 12, 13

što pravi još goru zamku: ako su sva četiri dostupna, rowCount() bude 4, pa rollbackuješ validnu rezervaciju. A ako od četiri datuma slučajno samo tri prođu available > 0, rowCount() === 3 može proći iako možda baš jedno pravo noćenje nije bilo dostupno.
Dakle half-open range ovde nije samo stil — kritičan je za korektnost.
Druga vrlo dobra stvar je što si broj noćenja izračunao, ali ja ne bih koristio:
$start->diff($end, true);

true znači absolute razlika. Ako neko pošalje:
check_in  = 13.
check_out = 10.

dobićeš opet 3 dana i time možeš sakriti nevalidan input. Pre PDO/transakcije treba validirati:
if ($end <= $start) {
    throw new InvalidArgumentException('Invalid booking dates.');
}

$numberOfNights = $start->diff($end)->days;

Ostatak ti je baš dobar:
$pdo->beginTransaction();

try {
    // conditional UPDATE

    if ($stmt->rowCount() !== $numberOfNights) {
        throw new RuntimeException('There is no availability');
    }

    // INSERT booking

    $bookingId = $pdo->lastInsertId();

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}

Ja bih tvoju verziju korigovao ovako:
$start = new DateTimeImmutable($checkIn);
$end = new DateTimeImmutable($checkOut);

if ($end <= $start) {
    throw new InvalidArgumentException('Invalid booking dates.');
}

$numberOfNights = $start->diff($end)->days;

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare(
        'UPDATE room_inventory
         SET available = available - 1
         WHERE room_id = :room_id
           AND date >= :check_in
           AND date < :check_out
           AND available > 0'
    );

    $stmt->execute([
        'room_id' => $roomId,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
    ]);

    if ($stmt->rowCount() !== $numberOfNights) {
        throw new RuntimeException('There is no availability');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO bookings
            (user_id, room_id, check_in, check_out)
         VALUES
            (:user_id, :room_id, :check_in, :check_out)'
    );

    $stmt->execute([
        'user_id' => $userId,
        'room_id' => $roomId,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
    ]);

    $bookingId = (int) $pdo->lastInsertId();

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}

Tvoj INSERT INTO bookings SET ... je validan MySQL stil; promenio sam ga samo u INSERT ... VALUES jer je standardniji SQL.

--------------------------------


PDO coding zadatak 4

Sada jedna praktična kombinacija dinamičkih filtera + PDO.

Imaš opcione vrednosti:

$city;
$minPrice;
$maxPrice;
$status;

Svaka može biti null.
Napiši PDO kod koji dinamički napravi:
SELECT id, name, city, base_price
FROM hotels
WHERE ...

tako da se WHERE uslov dodaje samo ako odgovarajuća vrednost nije null.
Sve stvarne vrednosti moraju ići kroz placeholders + $params, bez direktnog lepljenja user inputa u SQL.

Na kraju:
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

Ti napiši deo za izgradnju $sql i $params.



//$filter = $_GET['filter'] ?? 'city';

$city = 'Novi Sad';
$minPrice = null;
$maxPrice = 100;
$status = null;

$filters = [
    'city' => $city,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'status' => $status,
    'djoko' => 'DELETE from hotels'
];

$allowedList = [
    'city' => 'city = :city',
    'min_price' => 'base_price >= :min_price',
    'max_price' => 'base_price <= :max_price',
    'status' => 'status = :status',
];

$sql = "SELECT id, name, city, base_price
        FROM hotels
        WHERE 1 = 1";

$params = [];

foreach($allowedList as $key => $value) {
    if(isset($filters[$key])) {
        $sql .= " AND $value";

        $params[$key] = $filters[$key];
    }
}

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);  */

/**
 * Odlično — 10/10. Ovo je baš čist i bezbedan način da napraviš dinamičke filtere.
Posebno je dobro što si ubacio:
'djoko' => 'DELETE from hotels'

u $filters, a pošto prolaziš samo kroz:
$allowedList

taj podatak se potpuno ignoriše. To lepo pokazuje da user input ne određuje SQL strukturu.
Tvoj flow je:
user/filter data
↓
allowedList određuje koje filtere aplikacija dozvoljava
↓
SQL dobija samo unapred definisane fragmente
↓
stvarne vrednosti idu u $params
↓
prepare + execute

I ovo:
if (isset($filters[$key]))

je ovde baš zgodno jer si rekao da se filter dodaje samo ako vrednost nije null. 
isset() vraća false kada je vrednost null.
Sa tvojim konkretnim podacima finalni query bi praktično postao:
SELECT id, name, city, base_price
FROM hotels
WHERE 1 = 1
  AND city = :city
  AND base_price <= :max_price

a:
$params = [
    'city' => 'Novi Sad',
    'max_price' => 100,
];

min_price, status i zlonamerni djoko ne ulaze u SQL.
Mala napomena za ubuduće: ako bi null bio legitimna vrednost koju želiš posebno obraditi, 
onda bi isset() bio pogrešan izbor i koristio bi array_key_exists(). Ali za ovaj zahtev — tačno kako treba.

----------------------------------------------------

PDO coding zadatak 5

Sad spojimo whitelist + ORDER BY + LIMIT/OFFSET + type binding.
Pretpostavi da korisnik šalje:
$sort = 'price';
$direction = 'desc';
$page = 3;
$perPage = 20;

Dozvoljeni sortovi su:
name       → name
price      → base_price
created_at → created_at

A direction sme biti samo:
ASC
DESC

Napiši PDO kod koji bezbedno napravi:
SELECT id, name, base_price, created_at
FROM hotels
ORDER BY ...
LIMIT :limit
OFFSET :offset

Uslovi:
- ORDER BY kolona ide preko whitelist-a
- ASC/DESC takođe mora biti kontrolisano
- $offset = ($page - 1) * $perPage
- LIMIT i OFFSET binduj kao PDO::PARAM_INT
- ne lepi direktan user input u SQL.
 */

$sort = 'price';
$direction = 'desc';
$page = 3;
$perPage = 20;

$allowedSort = [
    'name' => 'name',
    'price' => 'base_price',
    'created_at' => 'created_at',
];

$directions = ['DESC', 'ASC'];

$direction = mb_strtoupper($direction);
$direction = in_array($direction, $directions, true) ? $direction : 'ASC';

$sortColumn = $allowedSort[$sort] ?? 'name';

$page    = max(1, $page);
$perPage = min(max(1, $perPage), 100);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT id, name, base_price, created_at
    FROM hotels
    ORDER BY $sortColumn $direction, id ASC
    LIMIT :limit
    OFFSET :offset
");

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$hotels = $stmt->fetchAll();

/**
 * tvoj poslednji zadatak je 9.8/10. 👌
Kod ti je vrlo dobar. Posebno mi se sviđaju ove stvari:
$direction = in_array($direction, $directions, true)
    ? $direction
    : 'ASC';

$sortColumn = $allowedSort[$sort] ?? 'name';

Dakle user može da traži šta hoće, ali u SQL završava samo nešto što je aplikacija unapred dozvolila.
Odlično je i:
ORDER BY $sortColumn $direction, id ASC

jer si dodao id kao deterministic tie-breaker. Ako dva hotela imaju istu cenu, rezultat i dalje ima stabilan redosled.
Pagination deo je takođe lep:
$page    = max(1, $page);
$perPage = min(max(1, $perPage), 100);
$offset  = ($page - 1) * $perPage;

i onda eksplicitno:
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

Jedino bih u realnom $_GET scenariju pre max()/min() prvo uradio validation/casting, jer će ti input originalno stići kao string. I ako nemamo globalni:
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC

onda bih poslednje napisao:
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

Ali sam zadatak si pogodio praktično potpuno.
Time je Module 7 — PDO završen: teorija ✅, interview checkpoint ✅, coding challenge ✅.
A da, sećam se plana. Sledeći je Module 8 — OOP. Posle njega imamo 9 — Composer, pa 10 — finalni mini kompleksni Vanilla PHP projekat. 
Kod OOP-a bih išao istim principom kao do sada, ali pošto ti OOP već praktično koristiš u Laravelu, ne bih ga razvlačio na 
„šta je klasa, šta je objekat“ deset lekcija. Krenućemo od mentalnog modela, pa vrlo brzo na ono što ti stvarno treba: 
properties/methods → visibility → constructor/DI → inheritance 
→ abstract/interface → traits → static → composition → SOLID osnove 
→ praktična arhitektura, uz stalno povezivanje sa Laravelom.
 */



?>