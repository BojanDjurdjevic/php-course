<?php
/**
    OOP Coding Task #1 — Booking status
    Napravi sledeće:
    BookingStatus enum
    Booking class

    Zahtevi
    BookingStatus treba da bude backed string enum sa vrednostima:
    Pending   → pending
    Confirmed → confirmed
    Cancelled → cancelled

    Booking treba da ima constructor:
    __construct(
        int $id,
        int $roomId,
        BookingStatus $status
    )

    Properties neka budu private.
    Dodaj metode:
    id(): int
    roomId(): int
    status(): BookingStatus

    i:
    confirm(): void
    cancel(): void

    Pravila su:
    confirm()
    → booking može biti confirmed samo ako je trenutno Pending
    → u suprotnom baci LogicException

    cancel()
    → Pending može da se otkaže
    → Confirmed može da se otkaže
    → Cancelled ne može ponovo da se otkaže
    → ako je već Cancelled, LogicException

    Na kraju želim da ovaj kod radi:
    $booking = new Booking(
        id: 1,
        roomId: 25,
        status: BookingStatus::Pending
    );

    $booking->confirm();

    echo $booking->status()->value;
    // confirmed

    $booking->cancel();

    echo $booking->status()->value;
    // cancelled

    $booking->cancel();
    // LogicException

    Nemoj praviti Service, Repository ni DTO još. 
    Ovde hoću samo da vidim kako ćeš modelovati enkapsulaciju + typed properties + enum + ponašanje objekta.
*/

/*

enum BookingStatus: string {

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function canBeConfirmed(): bool {
        
        return match($this) {
            self::Pending => true,

            self::Confirmed => false,

            self::Cancelled => false
        };

    }

    // AI Rešenje:
    /**
     * public function canBeConfirmed(): bool
     *   {
      *      return $this === self::Pending;
        * }
     */
/*
    public function canBeCancelled(): bool {
        
        return match($this) {
            self::Pending => true,

            self::Confirmed => true,

            self::Cancelled => false
        };

    }

    /**
        public function canBeCancelled(): bool
        {
            return $this !== self::Cancelled;
        }
     */ /*
}

class Booking {

    public function __construct(
        private int $id,
        private int $roomId,
        private BookingStatus $status
    ) {}

    public function id(): int {
        return $this->id;
    }

    public function roomId(): int {
        return $this->roomId;
    }

    public function status(): BookingStatus {
        return $this->status;
    }

    public function confirm(): void {
        if($this->status->canBeConfirmed()) {
            $this->status = BookingStatus::Confirmed;
        } else {
            throw new LogicException('The booking is already confirmed!');
        }
    }

    public function cancel(): void {
        if($this->status->canBeCancelled()) {
            $this->status = BookingStatus::Cancelled;
        } else {
            throw new LogicException('The booking is already cancelled!');
        }
    }

}

$booking = new Booking(1, 25, BookingStatus::Pending);

echo 'Inicijalizovano: ' . $booking->status()->value . "</br></br>"; 

$booking->confirm();

echo 'Potvrđeno: ' . $booking->status()->value . "</br></br>"; 

$booking->cancel();

echo 'Cancelled: ' . $booking->status()->value . "</br></br>"; 

$booking->cancel(); // Greška

/**
 * Ovo je baš dobro urađeno — 9.7/10. I još bolje: nisi samo ispunio zadatak, nego si deo business pravila prebacio u BookingStatus enum, što je sasvim smislen OOP potez.
Najviše mi se sviđa ovo:
$this->status->canBeConfirmed()
$this->status->canBeCancelled()

Umesto da Booking ima:
if (
    $this->status === BookingStatus::Pending
    || ...
)

enum sam zna šta konkretno stanje dozvoljava.
Mentalno:
Booking
→ zna šta želi da uradi

BookingStatus
→ zna da li trenutno stanje to dozvoljava

To je lepo modelovanje ponašanja.
Typed properties i enkapsulacija su ti takođe potpuno dobri:
private int $id,
private int $roomId,
private BookingStatus $status

plus getters:
id()
roomId()
status()

Spolja niko ne može proizvoljno da uradi:
$booking->status = BookingStatus::Cancelled;

nego mora kroz:
$booking->confirm();
$booking->cancel();

što znači da Booking kontroliše sopstvene invariants. To smo upravo hteli da vidimo.
Jedina stvar koju bih zaista ispravio je poruka ovde:
public function confirm(): void
{
    if ($this->status->canBeConfirmed()) {
        $this->status = BookingStatus::Confirmed;
    } else {
        throw new LogicException(
            'The booking is already confirmed!'
        );
    }
}

Ako je booking:
BookingStatus::Cancelled

poruka:
The booking is already confirmed!

nije tačna.
Bolje nešto neutralno:
throw new LogicException(
    'The booking cannot be confirmed from its current status.'
);

ili čak informativnije:
throw new LogicException(
    "Booking with status {$this->status->value} cannot be confirmed."
);

Isto bih mogao napisati malo kraće:
public function canBeConfirmed(): bool
{
    return $this === self::Pending;
}

jer samo jedan status vraća true.
A za cancellation:
public function canBeCancelled(): bool
{
    return $this !== self::Cancelled;
}

Tvoj match je potpuno ispravan — ovo je samo jednostavnija implementacija trenutnih pravila. 
Ako sutra dodaš Completed, Expired, NoShow, eksplicitni match čak može biti 
bolji jer te tera da razmisliš šta novi status znači.

Dakle, tvoj dizajn bih praktično zadržao ovakav:
enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function canBeConfirmed(): bool
    {
        return $this === self::Pending;
    }

    public function canBeCancelled(): bool
    {
        return $this !== self::Cancelled;
    }
}

Sve ostalo u Booking klasi ti je vrlo dobro.
 */


// CODING CHALLENGE 2:

/**
// -------------------------------------------------------- OOP Coding Task #2 — Interface + DI + Polymorphism ---------------------------------------------------//
Sada malo podižemo nivo.
Napravi:
PaymentGateway interface

StripeGateway
FakePaymentGateway

BookingPaymentService

PaymentGateway treba da ima:
public function charge(int $amount): bool;

StripeGateway neka za sada samo simulira payment i vrati true.
FakePaymentGateway neka pamti iznose koji su naplaćeni:
private array $charges = [];

i neka ima:
public function charges(): array;

Kada se pozove:
$fake->charge(5000);
$fake->charge(2500);

očekujem:
$fake->charges();
// [5000, 2500]

A BookingPaymentService treba da primi dependency kroz constructor:
public function __construct(
    private PaymentGateway $gateway
) {}

i da ima:
public function pay(int $amount): bool

koji delegira payment gateway-u.
Na kraju želim da oba rade bez promene Service klase:
$service = new BookingPaymentService(
    new StripeGateway()
);

$service->pay(5000);

i:
$fake = new FakePaymentGateway();

$service = new BookingPaymentService($fake);

$service->pay(5000);
$service->pay(2500);

var_dump($fake->charges());
// [5000, 2500]

Ovde gledamo zajedno interface + DI + composition + polymorphism + testability.
 */

/*
interface PaymentGateway {
    public function charge(int $amount): bool;
}

class StripeGateway implements PaymentGateway {
    #[Override]
    public function charge(int $amount): bool
    {
        return true;
    }
}

class FakePaymentGateway implements PaymentGateway {
    private array $charges = [];

    #[Override]
    public function charge(int $amount): bool
    {
        $this->charges[] = $amount;

        return true;
    }

    public function charges(): array {
        return $this->charges;
    }
}

class BookingPaymentService {

    public function __construct(
        private PaymentGateway $gateway
    ) {}

    public function pay(int $amount): bool {
        return $this->gateway->charge($amount);
    }

}

$service = new BookingPaymentService(new StripeGateway());

$service->pay(3000);

//---------------------------------

$fake = new FakePaymentGateway();

$fService = new BookingPaymentService($fake);

$fService->pay(5000);
$fService->pay(2500);

var_dump($fake->charges());

Odlično urađeno — 10/10.
Ovo je baš čist primer svega što smo hteli da proverimo:
PaymentGateway
→ contract

StripeGateway
FakePaymentGateway
→ različite implementacije

BookingPaymentService
→ zavisi od abstraction-a

constructor
→ DI

private PaymentGateway $gateway
→ composition

isti pay()
→ polymorphism

Posebno dobro što si stavio:
#[Override]

To je lep moderan PHP dodatak jer jasno pokazuje da metoda zaista implementira/override-uje deklarisani contract.
FakePaymentGateway ti je takođe odličan:
private array $charges = [];

i:
$this->charges[] = $amount;

Time test ne mora da veruje da se payment dogodio — može konkretno da proveri šta je servis poslao gateway-u.
Na primer:
$fake = new FakePaymentGateway();

$service = new BookingPaymentService($fake);

$service->pay(5000);

var_dump($fake->charges());
// [5000]

To vrlo lepo pokazuje zašto su:
interface + DI

toliko korisni za testiranje.
Jedina sitna stilska stvar: u ozbiljnijem kodu bih klase verovatno označio kao final ako ih ne planiraš za inheritance:
final class StripeGateway implements PaymentGateway

ali to nije nedostatak tvog rešenja.

// ---------------------------------------------------------------------------------


// ------------------------------------------  OOP Coding Task #3 — Abstract class + inheritance + polymorphism --------------------------------------------//


Sada proveravamo abstract klase.
Napravi:
abstract Notification
EmailNotification
SmsNotification

Notification treba da ima:
public function __construct(
    protected string $message
) {}

i običnu metodu:
public function message(): string

plus abstract metodu:
abstract public function send(): string;

EmailNotification::send() neka vrati:
EMAIL: Booking confirmed

a SmsNotification::send():
SMS: Booking confirmed

Zatim napravi funkciju:
function sendNotification(Notification $notification): string

koja ne proverava konkretan tip objekta, nego samo pozove:
$notification->send();

Na kraju treba da radi:
$email = new EmailNotification('Booking confirmed');
$sms = new SmsNotification('Booking confirmed');

echo sendNotification($email);
// EMAIL: Booking confirmed

echo sendNotification($sms);
// SMS: Booking confirmed

Ovde gledamo: abstract class + protected state + abstract method + overriding + polymorphism.
*/
// ----------------------------------
/*
abstract class Notification {

    public function __construct(
        protected string $message
    ) {}

    public function message(): string {
        return $this->message;
    }

    abstract public function send(): string;

}

class EmailNotification extends Notification {

    #[Override]
    public function send(): string
    {
        //$this->message = "EMAIL: " . $this->message; // - will duplicate

        return "Email: " . $this->message();
    }
}

class SMSNotification extends Notification {


    #[Override]
    public function send(): string
    {
        //$this->message = "SMS: " . $this->message; // Wrong
        return "SMS: " . $this->message();
    }
}

function sendNotification(Notification $notification): string {
    return $notification->send();
}

$email = new EmailNotification('Booking confirmed');
$sms = new SmsNotification('Booking confirmed');

echo sendNotification($email);
// EMAIL: Booking confirmed

echo sendNotification($sms);
// SMS: Booking confirmed

Vrlo dobro — 9.3/10. Strukturu si pogodio potpuno: abstract parent, protected state, abstract method, overriding i polymorphism preko Notification type-hinta.
Najvažnija stvar koju bih ispravio je ovo:
$this->message = "EMAIL: " . $this->message;
return $this->message();

Problem je što send() menja stanje objekta. Ako pozoveš:
$email->send();
$email->send();

dobićeš:
EMAIL: Booking confirmed
EMAIL: EMAIL: Booking confirmed

Dakle send() ovde ima neočekivan side effect.
Bolje je samo vratiti rezultat:
class EmailNotification extends Notification
{
    #[Override]
    public function send(): string
    {
        return 'EMAIL: ' . $this->message;
    }
}

i:
class SmsNotification extends Notification
{
    #[Override]
    public function send(): string
    {
        return 'SMS: ' . $this->message;
    }
}

Tada originalno stanje ostaje:
$this->message === 'Booking confirmed'

bez obzira koliko puta pozoveš send().
Tvoja funkcija je, međutim, baš ono što sam tražio:
function sendNotification(Notification $notification): string
{
    return $notification->send();
}

Ona nema:
if ($notification instanceof EmailNotification)

niti switch, nego se oslanja na polymorphism. To je odlično.
Mala stilska stvar: preferirao bih SmsNotification umesto SMSNotification zbog standardnog PascalCase imenovanja, ali funkcionalno nije problem.


// -------------------------------------------------------------------  OOP Coding Task #4 — DTO + Service + Repository contract ---------------------------------------------------//
Sada spajamo nekoliko važnijih koncepata.
Napravi:
CreateBookingData
BookingRepository interface
InMemoryBookingRepository
BookingService

DTO neka bude:
final readonly class CreateBookingData
{
    public function __construct(
        public int $userId,
        public int $roomId,
    ) {}
}

BookingRepository contract:
public function save(Booking $booking): void;

public function all(): array;

InMemoryBookingRepository neka čuva booking-e u običnom array-u.
BookingService treba da dobije repository kroz DI:
public function __construct(
    private BookingRepository $bookings
) {}

i da ima:
public function create(CreateBookingData $data): Booking

koji napravi novi Booking sa:
id = 1
roomId = $data->roomId
status = BookingStatus::Pending

sačuva ga kroz repository i vrati ga.
Za sada slobodno hardcode id = 1; ID generation nije tema zadatka.
Na kraju želim:
$repository = new InMemoryBookingRepository();

$service = new BookingService($repository);

$data = new CreateBookingData(
    userId: 5,
    roomId: 25
);

$booking = $service->create($data);

var_dump($booking->status());
// BookingStatus::Pending

var_dump($repository->all());
// sadrži kreirani Booking

Ovde proveravamo readonly DTO + interface + DI + composition + Service/Repository separation.

*/

enum BookingStatus: string {

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';


    public function canBeConfirmed(): bool
    {
        return $this === self::Pending;
    }

    public function canBeCancelled(): bool
    {
        return $this !== self::Cancelled;
    }

}

class Booking {

    public function __construct(
        private int $id,
        private int $roomId,
        private BookingStatus $status
    ) {}

    public function id(): int {
        return $this->id;
    }

    public function roomId(): int {
        return $this->roomId;
    }

    public function status(): BookingStatus {
        return $this->status;
    }

    public function confirm(): void {
        if($this->status->canBeConfirmed()) {
            $this->status = BookingStatus::Confirmed;
        } else {
            throw new LogicException('The booking is already confirmed!');
        }
    }

    public function cancel(): void {
        if($this->status->canBeCancelled()) {
            $this->status = BookingStatus::Cancelled;
        } else {
            throw new LogicException('The booking is already cancelled!');
        }
    }

}

final readonly class CreateBookingData 
{
    
    public function __construct(
        public int $userId,
        public int $roomId,
    ) {}

}

interface BookingRepository {

    public function save(Booking $booking): void;

    public function all(): array;

}

final class InMemoryBookingRepository implements BookingRepository {

    private array $bookings = [];

    #[Override]
    public function save(Booking $booking): void
    {
        $this->bookings[] = $booking;
    }

    #[Override]
    public function all(): array
    {
        return $this->bookings;
    }
}

final class BookingService {

    public function __construct(
        private BookingRepository $booking
    ) {}

    public function create(CreateBookingData $data): Booking {

        $booking = new Booking($data->userId, $data->roomId, BookingStatus::Pending);
        
        $this->booking->save($booking);

        return $booking;

    }

}

$repository = new InMemoryBookingRepository();

$service = new BookingService($repository);

$data = new CreateBookingData(5, 25);

$booking = $service->create($data);
/*
echo "Status: ";
var_dump($booking->status());

echo "</br></br>";

echo "Svi bookings: ";

var_dump($repository->all()); */

/**
 * Ovo je 10/10 za zadatak. Struktura ti je čista i tačno prati ono što smo hteli da proverimo.
Posebno dobro:
final readonly class CreateBookingData

DTO je tipizovan i nepromenljiv; zatim:
interface BookingRepository

definiše contract, dok:
final class InMemoryBookingRepository implements BookingRepository

daje konkretnu implementaciju. BookingService pritom ne zna niti ga zanima da li podaci idu u array, PDO ili nešto treće:
private BookingRepository $booking

To je zajedno DI + DIP + polymorphism + composition u vrlo malom primeru.
Jedina stvar koju bih promenio je naziv property-ja:
private BookingRepository $booking

u:
private BookingRepository $bookings

ili:
private BookingRepository $repository

jer taj objekat nije jedan Booking, nego repository za booking-e:
final class BookingService
{
    public function __construct(
        private BookingRepository $bookings
    ) {}

    public function create(CreateBookingData $data): Booking
    {
        $booking = new Booking(
            1,
            $data->roomId,
            BookingStatus::Pending
        );

        $this->bookings->save($booking);

        return $booking;
    }
}

Ali to je samo naming, ne greška.
Još jedna zanimljiva stvar: trenutno:
$data->userId

nije iskorišćen zato što naš postojeći Booking iz Task #1 nema userId. 
To je sasvim u redu za ovaj zadatak — neću ti zbog toga menjati model usred vežbe.

// ------------------------------------------------------------ OOP Coding Task #5 — Request + FormRequest + Controller ---------------------------------------------------//

Za poslednji OOP coding zadatak hajde baš ono što si ranije pitao: da napravimo mali Laravel-like flow bez frameworka.
Napravi običan Request:
final class Request

koji constructorom prima:
array $body

i ima:
public function input(
    string $key,
    mixed $default = null
): mixed

Dakle:
$request = new Request([
    'user_id' => '5',
    'room_id' => '25',
]);

pa:
$request->input('room_id');
// '25'

Zatim napravi:
final class CreateBookingRequest

koji kroz DI prima:
Request $request

i ima:
public function validated(): CreateBookingData

Ovde želim da proveriš da su:
user_id
room_id

validni integer-i.
Ako nisu:
throw new InvalidArgumentException(...);

Ako jesu, validated() neka odmah vrati naš DTO:
new CreateBookingData(
    userId: ...,
    roomId: ...
);

Na kraju napravi:
final class BookingController

koji prima:
BookingService $service

i ima:
public function store(
    CreateBookingRequest $request
): Booking

Controller ne validira ništa sam, nego:
CreateBookingRequest
→ validated DTO

Controller
→ DTO prosledi Service-u

Service
→ napravi Booking

Na kraju treba da možeš otprilike:
$request = new Request([
    'user_id' => '5',
    'room_id' => '25',
]);

$formRequest = new CreateBookingRequest($request);

$repository = new InMemoryBookingRepository();

$service = new BookingService($repository);

$controller = new BookingController($service);

$booking = $controller->store($formRequest);

var_dump($booking->roomId());
// 25

Ovde gledamo poslednji veliki spoj:
raw input
→ Request
→ validation
→ DTO
→ Controller
→ Service
→ Repository

Posle ovoga bih rekao da je OOP coding checkpoint završen i možemo preći na sledeći modul — Composer + autoloading + namespaces.
 */

final class Request {
    public function __construct(private array $body) {}

    public function input(string $key, mixed $default = null): mixed {
        /*
        $default = $this->body[$key] ?? $default; // Opet komplikujem

        return $default; */

        return $this->body[$key] ?? $default;
    }

    public function all(): array {
        return $this->body;
    }
}

final class CreateBookingRequest {
    private array $errors = [];

    public function __construct(
        private Request $request
    ) {}

    private function validateInteger(mixed $value, string $field, array &$errors, int $min, int $max): ? int { // NE treba errors kad već imam this-errors

        if(!is_string($value)) {
            $errors[$field] = "The $field must be a string!"; // OVDE prestrogo... Može biti JSON poslat
            return null;
        }

        $value = trim($value);

        if($value === '') {

            $errors[$field] = "The field $field is required!";
            return null;

        }

        $validatedInt = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [

                'min_range' => $min,

                'max_range' => $max,

            ]
        ]);

        if($validatedInt === false) {

            $errors[$field] = "Field $field must be a number between $min and $max";
            return null;

        }

        return $validatedInt;
    }


    public function validated(): CreateBookingData 
    {
        $roomId =  $this->validateInteger($this->request->input('room_id'), 'room_id', $this->errors, 1, PHP_INT_MAX);

        $userId = $this->validateInteger($this->request->input('user_id'), 'user_id', $this->errors, 1, PHP_INT_MAX);

        if($userId === null) {
            throw new InvalidArgumentException($this->errors['user_id'] ?? 'Nevalidan user_id!');
        }

        if($roomId === null) {
            throw new InvalidArgumentException($this->errors['room_id'] ?? 'Nevalidan room_id!');
        }

        return new CreateBookingData($userId, $roomId);
    }
}

final class BookingController {
    public function __construct(
        private BookingService $service
    ) {}

    public function store(CreateBookingRequest $request): Booking {
        return $this->service->create($request->validated());
    }
}

$request = new Request([
    'user_id' => '9',
    'room_id' => '18',
]);

$formRequest = new CreateBookingRequest($request);

$repository = new InMemoryBookingRepository();

$service = new BookingService($repository);

$controller = new BookingController($service);

$booking = $controller->store($formRequest);

var_dump($booking);


/**
 * Za ovaj zadatak bih dao 9.6/10.
Glavni flow ti je odličan:
raw data
↓
Request
↓
CreateBookingRequest
↓ validation
CreateBookingData
↓
BookingController
↓
BookingService
↓
Repository

Posebno mi se sviđa ovo:
public function store(CreateBookingRequest $request): Booking
{
    return $this->service->create(
        $request->validated()
    );
}

Controller je praktično savršeno tanak. On ne zna kako se validira, niti kako se booking kreira.
I all() bih slobodno ostavio:
public function all(): array
{
    return $this->body;
}

Nije problem što ga ovaj use-case trenutno ne koristi. 
Request je generalnija infrastrukturalna klasa 
i potpuno ima smisla da pruža all(), baš kao Laravel Request.

Imaju samo tri stvari koje bih doterao.
1. input() može jednostavnije
Tvoje radi:
public function input(string $key, mixed $default = null): mixed
{
    $default = $this->body[$key] ?? $default;

    return $default;
}

ali nema potrebe da prepisujemo $default.
Čistije:
public function input(
    string $key,
    mixed $default = null
): mixed {
    return $this->body[$key] ?? $default;
}

Funkcionalno nema razlike.
2. validateInteger() je malo previše strog
Ovde:
if (!is_string($value)) {
    $errors[$field] = "The $field must be a string!";
    return null;
}

ima smisla ako eksplicitno kažeš:
Moj Request predstavlja klasične application/x-www-form-urlencoded podatke, pa očekujem string.

Jer:
room_id=25

u $_POST zaista dolazi kao:
'25'

Ali generalni HTTP Request može doći i iz JSON-a:
{
  "room_id": 25
}

nakon json_decode() to može biti pravi PHP int.
A semantički tebi nije važno da room_id bude string. Važno je da predstavlja validan integer.
Zato bih dozvolio i:
'25'
25

Na primer:
private function validateInteger(
    mixed $value,
    string $field,
    int $min,
    int $max
): ?int {
    if (is_string($value)) {
        $value = trim($value);

        if ($value === '') {
            $this->errors[$field] = "The field $field is required!";
            return null;
        }
    }

    $validated = filter_var(
        $value,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => $min,
                'max_range' => $max,
            ],
        ]
    );

    if ($validated === false) {
        $this->errors[$field]
            = "Field $field must be an integer between $min and $max.";

        return null;
    }

    return $validated;
}

Ovo je više robustnost nego OOP problem.
3. $errors ne moraš prosleđivati po referenci
Već imaš:
private array $errors = [];

pa je malo redundantno:
array &$errors

Može sama metoda da radi:
$this->errors[$field] = ...

To čak bolje odgovara OOP-u: errors je stanje konkretnog CreateBookingRequest objekta.
Dakle:
private function validateInteger(
    mixed $value,
    string $field,
    int $min,
    int $max
): ?int

je dovoljno.
Inače ovo:
return new CreateBookingData($userId, $roomId);

mi se baš sviđa.
Mogli smo da napravimo:
validated(): array

pa da Controller pravi DTO, ali tvoja varijanta kaže:
Kada ovaj FormRequest uspešno prođe validation, rezultat je već application-ready CreateBookingData.

Za ovakav projekat je to sasvim čist dizajn.
Završna ocena OOP coding checkpointa: 9.7/10
Kroz zadatke si praktično pokazao da umeš da koristiš, a ne samo definišeš:
Enum + domain behavior
Interface
DI
DIP
Composition
Polymorphism
Abstract class
Inheritance
DTO
Repository contract
Fake/InMemory implementation
Service
Request
FormRequest-style validation
Controller
Encapsulation
Typed properties
readonly

Najvažnije: kod tebe se vidi da si počeo da razmišljaš u terminima odgovornosti objekata, 
a ne samo „gde da stavim ovu funkciju“. To je zapravo glavni cilj ovog OOP modula.
 */