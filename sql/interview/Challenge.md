# Challenge

Prvi zadatak neka bude baš JOIN + GROUP BY + HAVING, bez previše trikova.
Imamo tabele:

```text
hotels
------
id
name
city
is_active
```

```text
bookings
--------
id
hotel_id
total_price
status
created_at
```

Napiši SQL upit koji vraća samo aktivne hotele iz Novog Sada koji imaju najmanje 5 potvrđenih rezervacija (status = 'confirmed').
Za svaki hotel želimo:

```text
hotel id
hotel name
broj potvrđenih rezervacija
ukupan prihod iz tih rezervacija
prosečnu vrednost rezervacije
```

Rezultat sortiraj prvo po ukupnom prihodu opadajuće, a ako dva hotela imaju isti prihod, onda po broju rezervacija opadajuće.
Dakle očekujem da upotrebiš nešto u fazonu:

```text
JOIN
WHERE
GROUP BY
HAVING
COUNT()
SUM()
AVG()
ORDER BY
```

Napiši ceo SQL kako bi ga ti napisao, pa ga pregledamo red po red.

---

```sql
SELECT h.id, h.name, COUNT(b.status) AS confirmed_bookings, SUM(b.total) AS total_revenue, AVG(b.total) AS average_income
FROM hotels h
JOIN bookings b ON h.id = b.hotel_id
WHERE b.status = 'confirmed' AND h.name = 'Novi Sad'
GROUP BY h.id
HAVING(confirmed_bookings >= 5)
ORDER BY total_revenue, confirmed_bookings DESC;
```

---

Dobar kostur — vidi se da ti je redosled SQL logike već prirodan. Ovde bih dao 7.8/10, jer je struktura dobra, ali ima nekoliko konkretnih grešaka koje menjaju rezultat.
Tvoj upit:

```sql
SELECT h.id, h.name,
       COUNT(b.status) AS confirmed_bookings,
       SUM(b.total) AS total_revenue,
       AVG(b.total) AS average_income
FROM hotels h
JOIN bookings b ON h.id = b.hotel_id
WHERE b.status = 'confirmed'
  AND h.name = 'Novi Sad'
GROUP BY h.id
HAVING confirmed_bookings >= 5
ORDER BY total_revenue, confirmed_bookings DESC;
```

Najpre, ovde:

h.name = 'Novi Sad'

tražimo grad, pa treba:

h.city = 'Novi Sad'

I zaboravili smo uslov da hotel mora biti aktivan:

h.is_active = TRUE

Drugo, po zadatoj tabeli kolona se zove:

total_price

a ne total, pa:

```php
SUM(b.total_price)
AVG(b.total_price)
```

Treće, ovde:

```sql
ORDER BY total_revenue, confirmed_bookings DESC
```

samo drugi kriterijum ima DESC. Prvi je podrazumevano ASC.
Nama treba:

```sql
ORDER BY total_revenue DESC,
         confirmed_bookings DESC;
```

`COUNT(b.status)` će ovde raditi ako status nije NULL, ali pošto smo već u WHERE ostavili samo confirmed booking redove, meni je jasnije:

`COUNT(*)` AS confirmed_bookings

I konačna verzija:

```sql
SELECT
    h.id,
    h.name,
    COUNT(*) AS confirmed_bookings,
    SUM(b.total_price) AS total_revenue,
    AVG(b.total_price) AS average_income
FROM hotels h
JOIN bookings b
    ON b.hotel_id = h.id
WHERE h.city = 'Novi Sad'
  AND h.is_active = TRUE
  AND b.status = 'confirmed'
GROUP BY h.id, h.name
HAVING COUNT(*) >= 5
ORDER BY total_revenue DESC,
         confirmed_bookings DESC;
```

Tvoj najvažniji deo je bio potpuno dobar:

```text
WHERE
→ filtriraj pojedinačne booking redove pre grupisanja
```

```text
GROUP BY
→ napravi grupu po hotelu
```

```text
HAVING
→ nakon grupisanja ostavi samo hotele sa >= 5 booking-a
```

Dakle nisu problem JOIN/GROUP/HAVING koncepti — greške su bile više pažnja prema zahtevu i imenima kolona.

---

## SQL coding zadatak 2 — NOT EXISTS

Imamo:

```text
hotels
------
id
name
city
is_active
```

```text
bookings
--------
id
hotel_id
status
check_in
check_out
```

Napiši query koji vraća:

sve aktivne hotele koji nemaju nijednu potvrđenu rezervaciju čiji check_in pada u novembru 2026.

Želim da koristiš:

NOT EXISTS

a za novembar koristi half-open date range, dakle ideju:

```text
>= početak novembra
< početak decembra
```

Napiši ceo SQL pa pregledamo.

---

```sql
SELECT h.id, h.name, h.city
FROM hotels h
WHERE h.is_active = TRUE
  AND NOT EXISTS (
      SELECT 1
      FROM bookings b
      WHERE b.hotel_id = h.id
        AND b.status = 'confirmed'
        AND b.check_in >= '2026-11-01'
        AND b.check_in <  '2026-12-01'
  );
```

---

Tačno — 10/10.
Ovo je baš čist NOT EXISTS upit:

```sql
SELECT h.id, h.name, h.city
FROM hotels h
WHERE h.is_active = TRUE
  AND NOT EXISTS (
      SELECT 1
      FROM bookings b
      WHERE b.hotel_id = h.id
        AND b.status = 'confirmed'
        AND b.check_in >= '2026-11-01'
        AND b.check_in <  '2026-12-01'
  );
```

Posebno dobro:

b.hotel_id = h.id

To je korelacija između spoljnog i unutrašnjeg upita.
I half-open range je tačno kako treba:

```text
>= 2026-11-01
<  2026-12-01
```

bolje nego da radiš nešto tipa:

`MONTH(check_in)` = 11

jer je ovaj tvoj pristup SARGable i može lepo da koristi indeks.

---

SQL coding zadatak 3 — indeksiranje

Imamo često korišćen query:

```sql
SELECT id, hotel_id, check_in, check_out, status
FROM bookings
WHERE hotel_id = 15
  AND status = 'confirmed'
  AND check_in >= '2026-11-01'
  AND check_in < '2026-12-01'
ORDER BY check_in ASC;
```

Predloži jedan composite index za ovaj query.
Zatim objasni zašto si izabrao baš taj redosled kolona.
Ovde me zanima da primeniš ono:

```text
equality
→ equality
→ range
```

i da razmisliš i o ORDER BY check_in.

---

```sql
CREATE INDEX idx_bookings_hotel_status_checkin
    ON bookings (hotel_id, status, check_in);
```

Ovo bi bilo najprikladnije jer najbolje sužava pretragu,
po id-u odmah skače na izabrani id zatim pregleda statuse
kojih je malo a onda ide na range i lepo poreda, što nam baš i treba zbog Order BY.

---

Tačno — 10/10.
Ovaj indeks je baš prirodan izbor:

```sql
CREATE INDEX idx_bookings_hotel_status_checkin
    ON bookings (hotel_id, status, check_in);
```

Razlog je upravo redosled uslova:

```text
hotel_id = 15
→ equality
```

```text
status = 'confirmed'
→ equality
```

check_in >= ... AND check_in < ...

```text
→ range
```

Dakle:

```text
equality
→ equality
→ range
```

I dodatni plus je što je:

```sql
ORDER BY check_in ASC
```

u skladu sa poslednjom kolonom indeksa. Pošto su hotel_id i status već fiksirani jednakostima,
MySQL može da čita odgovarajući check_in opseg već u traženom redosledu, bez posebnog sortiranja.
Jedina terminološka sitnica: kada kažeš:

„po id-u odmah skače na izabrani id“

preciznije je reći:

po hotel_id brzo pronalazi odgovarajući indeksni opseg

jer hotel_id ovde nije primary key same bookings tabele, nego obična indexed kolona unutar composite index-a.

---

## SQL coding zadatak 4 — prepoznaj loš indeks

Za isti query:

```sql
SELECT id, hotel_id, check_in, check_out, status
FROM bookings
WHERE hotel_id = 15
  AND status = 'confirmed'
  AND check_in >= '2026-11-01'
  AND check_in < '2026-12-01'
ORDER BY check_in ASC;
```

neko predloži:

```php
INDEX(check_in, hotel_id, status)
```

Objasni zašto je ovaj redosled slabiji od:

```php
INDEX(hotel_id, status, check_in)
```

Posebno me zanima šta se dešava kada range kolona dođe prva u composite index-u.

---

`INDEX(check_in, hotel_id, status)` - vrlo problematičan jer stavlja range na početak.

hotel_id i status bi bili filtrirani unutar opsega (čita se sve u novembru, pa se odbacuju nepotvrđeni).
Radi, ali čita više unosa nego što treba. Kad je opseg pre jednakosti, jednakost ne može da suzi pretragu.

---

Tačno — 9.8/10.
Najvažnije si pogodio: kada indeks krene sa:

```php
INDEX(check_in, hotel_id, status)
```

MySQL prvo ulazi u:

check_in range za novembar

i time odmah radi nad širim delom indeksa. hotel_id i status dolaze tek posle range kolone, pa ne mogu na isti način da dodatno suze indeksni seek kao kada su equality uslovi prvi.
Zato je:

```php
INDEX(hotel_id, status, check_in)
```

mnogo prirodniji za:

hotel_id = ...
status = ...
check_in BETWEEN ...

jer prvo suzimo na veoma mali podskup:

```text
hotel_id = 15
status = confirmed
```

pa tek onda čitamo samo odgovarajući check_in range unutar tog podskupa.
Tvoja rečenica:

„čita se sve u novembru, pa se odbacuju nepotvrđeni“

je dobar mentalni model. Samo bih je na intervjuu formulirao malo opreznije
kao „može morati da pregleda mnogo veći deo novembarskog range-a“, jer optimizer
i storage engine imaju nijanse u načinu korišćenja kasnijih kolona.

---

SQL coding zadatak 5 — poslednji, kombinovani

Imamo:

```text
users
-----
id
name
```

```text
hotels
------
id
owner_id
name
```

```text
bookings
--------
id
hotel_id
status
total_price
```

Napiši query koji vraća vlasnike koji imaju:

- najmanje 2 hotela
- najmanje 10 confirmed booking-a ukupno preko svih svojih hotela
- ukupan confirmed prihod veći od 5000
Vrati:

```text
owner id
owner name
broj hotela
broj confirmed booking-a
ukupan confirmed prihod
```

Sortiraj po prihodu opadajuće.
Ovde pazi na jednu zamku: JOIN hotels -> bookings može da multiplikuje redove hotela, pa razmisli kako ćeš brojati hotele.

---

```sql
WITH confirmed_bookings AS (
    SELECT hotel_id, COUNT(*) AS reservations, SUM(total_price) AS total_revenue FROM bookings
    WHERE status = 'confirmed'
    GROUP BY hotel_id
)
SELECT h.owner_id, u.name, COUNT(*) as total_hotels, SUM(b.reservations) as confirmed_bookings, SUM(b.total_revenue) as total_income
FROM hotels h
LEFT JOIN confirmed_bookings b ON h.id = b.hotel_id
JOIN users u ON u.id = h.owner_id
GROUP BY h.owner_id, u.name
HAVING (
    COUNT(*) > 1 AND SUM(b.reservations) > 9 AND SUM(b.total_revenue) > 5000
)
ORDER BY total_income DESC;
```

---

Odlično — 10/10. Ovo je čak elegantnije nego da si rešavao problem sa `COUNT(DISTINCT h.id)` nakon direktnog JOIN bookings.
Ključna stvar koju si uradio je da si prvo agregirao booking-e po hotelu:

```sql
WITH confirmed_bookings AS (
    SELECT
        hotel_id,
        COUNT(*) AS reservations,
        SUM(total_price) AS total_revenue
    FROM bookings
    WHERE status = 'confirmed'
    GROUP BY hotel_id
)
```

Time dobijaš najviše jedan red po hotelu:

```text
hotel 1 → 7 booking-a → 2400
hotel 2 → 5 booking-a → 3100
hotel 3 → ...
```

Zato kada posle uradiš:

```sql
LEFT JOIN confirmed_bookings b
    ON h.id = b.hotel_id
```

više nema problema da svaki booking multiplikuje hotel.
Tada ovo:

`COUNT(*)` AS total_hotels

zaista broji hotele, jer svaki hotel postoji samo jednom u rezultatu pre GROUP BY owner.
I ovo je potpuno dobro:

```php
SUM(b.reservations)
SUM(b.total_revenue)
```

jer sada sabiraš već izračunate totals po hotelima da bi dobio totals po owner-u.
Tvoj:

```text
HAVING (
    COUNT(*) > 1
    AND SUM(b.reservations) > 9
    AND SUM(b.total_revenue) > 5000
)
```

je logički tačan. Ja bih ga samo stilistički napisao direktno prema business zahtevu:

```sql
HAVING COUNT(*) >= 2
   AND SUM(b.reservations) >= 10
   AND SUM(b.total_revenue) > 5000
```

Isto značenje, ali čitalac odmah vidi pravilo „najmanje 2 / najmanje 10“.
Jedna dobra nijansa: pošto koristiš LEFT JOIN, hotel bez confirmed booking-a dobija:

```text
b.reservations = NULL
b.total_revenue = NULL
```

Ali to ti ovde ne pravi problem jer owner svakako mora imati:

`SUM(b.reservations)` >= 10

Ako bismo samo prikazivali sve ownere, tada bi `COALESCE()` bio koristan.
Tvoj finalni pristup bih vrlo rado video na intervjuu:

```sql
WITH confirmed_bookings AS (
    SELECT
        hotel_id,
        COUNT(*) AS reservations,
        SUM(total_price) AS total_revenue
    FROM bookings
    WHERE status = 'confirmed'
    GROUP BY hotel_id
)
SELECT
    h.owner_id,
    u.name,
    COUNT(*) AS total_hotels,
    SUM(b.reservations) AS confirmed_bookings,
    SUM(b.total_revenue) AS total_income
FROM hotels h
LEFT JOIN confirmed_bookings b
    ON h.id = b.hotel_id
JOIN users u
    ON u.id = h.owner_id
GROUP BY h.owner_id, u.name
HAVING COUNT(*) >= 2
   AND SUM(b.reservations) >= 10
   AND SUM(b.total_revenue) > 5000
ORDER BY total_income DESC;
```

Ovim završavamo Module 6 — MySQL/SQL. Teorija + interview checkpoint + coding zadaci su kompletirani.

---

## Rekao bih da si ovde na veoma dobrom nivou; posebno su ti jaki JOIN/agregacije,

EXISTS, indeksi, transakcije i concurrency, što je za backend važnije od pamćenja egzotičnih SQL funkcija.
