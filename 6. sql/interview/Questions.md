# Questions

Krećemo sa MySQL interview checkpoint-om — kao i prethodne module: jedno pitanje → tvoj odgovor → moja analiza, korekcija i ocena → sledeće pitanje.

Ići ćemo od core stvari ka JOIN-ovima, indeksima, transakcijama, concurrency-ju i optimizaciji.

---

## Interview pitanje 1

Objasni mi razliku između:

```text
PRIMARY KEY
UNIQUE
FOREIGN KEY
INDEX
```

Posebno me zanima:

- čemu svaki služi,
- da li garantuje integritet ili prvenstveno služi performansama,
- i koji od njih već podrazumevaju indeks.
Odgovori kao da si na tehničkom intervjuu — svojim rečima, ne mora definicija iz knjige.

---

Primary key, najčešće id jeste ključ koji bukvalno identifikuje jedan red u oređenoj tabeli, kada ga postavljamo rucno (ne kroz upit) mysql okruzenje mu automatski postavlja i increment. Samo po sebi je vec indeksirano i nema potrebe postavljati dodatno indez

UNIQUE nam obezbedjuje da odredjena kolona, polje bude unikatne vrednosti i da ne mogu biti 2 iste. Primer: email. Takodje, ne treba dodavati index.

FOREIGN KEY jeste strani kljuc povezan sa drugom tabelom i obezbedjuje relaciju sa tim drugim entitetom. Primer tabela hotels ima u sebi owner_id koji predstavlja id usera tj user_id. Moze biti postavljeno tako da ne dozvoljava brisanje usera ukoliko ima hotel. Garantuje integritet i to da user mora postojati u bazi.

Nijedno od ova 3 do sada navedena, ne moze biti null.

INDEX je nesto sto mi postavljamo na odredjeno polje a najčešće radi performansa, ukoliko je to polje cesto pretrazivano.

---

Dobar odgovor, ali imaš dve bitne greške koje bi interviewer verovatno uhvatio. Dao bih ti 8.5/10.
PRIMARY KEY deo ti je dobar osim ovoga:

„MySQL mu automatski postavlja increment.“

Ne — PRIMARY KEY ne znači automatski AUTO_INCREMENT. Moraš ga eksplicitno definisati:

id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

Možeš sasvim normalno imati:

code `VARCHAR(20)` PRIMARY KEY

ili:

id BIGINT PRIMARY KEY

bez auto-incrementa.
Tačno je da PRIMARY KEY identifikuje red, mora biti jedinstven i NOT NULL, i već je indeksiran.
UNIQUE si objasnio dobro:

```text
UNIQUE
→ integrity constraint
→ sprečava duplikate
→ MySQL ga sprovodi unique index-om
```

Samo jedna nijansa: u MySQL-u UNIQUE kolona može dozvoliti više NULL vrednosti ako nije definisana kao NOT NULL.
Zato često imamo:

email `VARCHAR(255)` NOT NULL UNIQUE

FOREIGN KEY deo ti je vrlo dobar: čuva referential integrity i može imati ON DELETE RESTRICT, CASCADE, SET NULL itd.
Ali druga greška je:

„Nijedno od ova 3 ne može biti null.“

To važi za PRIMARY KEY, ali ne automatski za druga dva.
Na primer sasvim validno:

manager_id BIGINT UNSIGNED NULL,
FOREIGN KEY (manager_id) REFERENCES `users(id)`

Znači employee može trenutno da nema managera.
I:

nickname `VARCHAR(100)` UNIQUE NULL

takođe može biti nullable.
Dakle:

```text
PRIMARY KEY
→ nikad NULL
```

```text
UNIQUE
→ može biti NULL ako nije NOT NULL
```

```text
FOREIGN KEY
→ može biti NULL ako kolona to dozvoljava
```

Još jedna korisna nijansa oko FK-a: FOREIGN KEY nije prvenstveno performance alat,
ali InnoDB zahteva odgovarajuće indekse za FK proveru i može kreirati indeks na child
FK koloni ako odgovarajući već ne postoji. Zato nemoj ga mentalno izjednačiti sa običnim INDEX,
ali indeksiranje jeste deo implementacije FK-a.

Tvoj INDEX deo je dobar:

```text
INDEX
→ prvenstveno performance struktura
→ ubrzava određene WHERE / JOIN / ORDER BY / GROUP BY operacije
→ sam po sebi ne uvodi business integrity pravilo
```

Najbolja interview verzija bi ti bila:

```text
PRIMARY KEY
→ identitet reda
→ unique + not null
→ indeksiran
```

```text
UNIQUE
→ sprečava dupliranje vrednosti/kombinacije
→ integrity constraint
→ unique index
```

```text
FOREIGN KEY
→ garantuje validnu relaciju između tabela
→ referential integrity
→ kolona može biti nullable ako schema to dozvoljava
```

```text
INDEX
→ prvenstveno performance
→ ne garantuje business integritet sam po sebi
```

---

## Interview pitanje 2

Objasni razliku između:

```text
WHERE
HAVING
```

i reci zašto ovo nije isto:

```sql
WHERE COUNT(*) >= 3
```

i:

```sql
HAVING COUNT(*) >= 3
```

Daj mi i jedan konkretan primer sa GROUP BY.

---

Osnovna razlika:

---

## WHERE filtrira pre grupisanja a HAVING posle tako da zapravo ako imamo GROUP BY,

ne mozemo koristiti WHERE jer ono "ne vidi" count funkciju. Primer:

```sql
SELECT city, COUNT(*) AS total_hotels
FROM hotels
GROUP BY city
HAVING COUNT(*) >= 2;
```

I dobijemo samo gradove koji imaju najmanje 2 hotela

---

Dobar odgovor — 9/10. Suštinu si pogodio, ali jednu formulaciju treba ispraviti.
Ovo:

„ako imamo GROUP BY, ne možemo koristiti WHERE“

nije tačno. Možemo koristiti oba zajedno. Razlika je u tome šta filtriraju i kada:

```text
WHERE
→ filtrira pojedinačne redove PRE GROUP BY
```

```text
HAVING
→ filtrira već formirane grupe POSLE GROUP BY
```

Tvoj primer je ispravan:

```sql
SELECT city, COUNT(*) AS total_hotels
FROM hotels
GROUP BY city
HAVING COUNT(*) >= 2;
```

To znači: grupiši hotele po gradu, pa zadrži samo grupe koje imaju najmanje 2 hotela.
A evo primera gde koriste oba:

```sql
SELECT city, COUNT(*) AS total_hotels
FROM hotels
WHERE is_active = TRUE
GROUP BY city
HAVING COUNT(*) >= 2;
```

Mentalno:

```text
1. WHERE is_active = TRUE
   → izbaci neaktivne hotele
```

```text
2. GROUP BY city
   → grupiši preostale po gradu
```

```text
3. HAVING COUNT(*) >= 2
   → zadrži gradove sa najmanje 2 aktivna hotela
```

I da, razlog zašto ovo ne radi:

```sql
WHERE COUNT(*) >= 3
```

jeste što WHERE radi pre nego što je agregat `COUNT(*)` izračunat.
U MySQL-u možeš i:

```sql
HAVING total_hotels >= 2
```

jer dozvoljava korišćenje SELECT alias-a u HAVING.

---

## Interview pitanje 3

Objasni razliku između:

```text
INNER JOIN
LEFT JOIN
```

i posebno mi reci šta se dešava sa LEFT JOIN-om ako uslov nad desnom tabelom staviš u WHERE umesto u ON.
Daj konkretan primer sa:

```text
hotels
rooms
```

---

Oba sluze za spajanje tabela prema ralaciji, medjutim inner (ili samo join) ce vratiti samo rezultate za one koji imaju poklapanje sa obe strane poredjenja, dok ce left spojiti tako da ce prikazati i one koji imaju match sa obe strane a i oni koji imaju samo sa leve. Na primer hotels -> rooms, hocemo da vidimo sve sobe hotela

INNER JOIN - prikaze samo hotele koji imaju sobe

LEFT - prikaze i one koni nemaju, s tim sto ce za vrednost sobe biti NULL.

Mozemo postaviti dodatni uslov sa Where , na primer WHERE h.is_active =true kako bismo dobili samo aktivne hotele.

---

## Dakle, relacije ON

A filtriranjr WHERE.

Ako bismo stavili WHERE umesto ON:

Kod INNER ne bismo imali velike razlike ako zanemarimo losu praksu i necitljivost koda jer svakako ne vraca kolone sa Null, medjutim kod LEFT bismo prakticno ponistili spajanje po levoj tabeli jer WHERE eksplicitno trazi poklapanje i ako imamo null sa bilo koje srrane, fakticki dobijemo rezultate kao da smo radili INNER.

---

Vrlo dobro — 9.5/10. Suštinu si pogodio, samo bih precizirao jednu važnu nijansu.
Tačno:

```text
INNER JOIN
→ samo redovi koji imaju match sa obe strane
```

```text
LEFT JOIN
→ svi redovi sa leve strane
→ ako nema match desno, desne kolone su NULL
```

Tvoj primer:

hotels LEFT JOIN rooms

znači da hotel bez sobe i dalje ostaje u rezultatu, samo će r.* biti NULL.
Važna korekcija je ovo:

WHERE ne „poništava LEFT JOIN“ sam po sebi.

Ako napišeš:

```sql
WHERE h.is_active = TRUE
```

to filtrira levu tabelu i sasvim je kompatibilno sa LEFT JOIN.
Problem nastaje kada posle LEFT JOIN filtriraš kolonu sa desne strane na način koji odbacuje NULL.
Na primer:

```sql
SELECT h.id, h.name, r.name
FROM hotels h
LEFT JOIN rooms r
    ON r.hotel_id = h.id
WHERE r.is_active = TRUE;
```

Hotel bez sobe dobije:

r.is_active = NULL

a NULL = TRUE nije TRUE, pa taj hotel ispada.
Zato, ako želimo:

sve hotele, ali od soba spoji samo aktivne

pišemo:

```sql
SELECT h.id, h.name, r.name
FROM hotels h
LEFT JOIN rooms r
    ON r.hotel_id = h.id
   AND r.is_active = TRUE;
```

Mentalno:

```text
ON
→ odlučuje koji desni redovi se spajaju
```

```text
WHERE
→ filtrira finalni result set
```

I ovo što si rekao za INNER JOIN je praktično tačno: kod njega prebacivanje određenog join-filter uslova
između ON i WHERE često daje isti rezultat, mada semantički i zbog čitljivosti ipak razlikujemo relaciju
od naknadnog filtriranja.

---

## Interview pitanje 4

Objasni razliku između:

```text
EXISTS
IN
JOIN
```

u situaciji kada želimo:

sve korisnike koji imaju makar jedan hotel.

Ne moraš pisati sva tri query-ja ako ne želiš, ali me zanima mentalna razlika: šta svaki od ta tri pristupa zapravo pita bazu?

---

Najprakticnije i mozda najbolje resenje u ovom slucaju kada nam treba makar jedan, jest EXISTS jer on bas trazi korisnike koji imaju makar jedan hotel

```php
EXISTS(Select 1 from hotels where owner_id = user_id)
```

Tehnicki, ovo mozemo izvesti sa IN - koji pita da li medju hotelima ijedan ima owner_id kao ovaj user_id pa čak i sa JOIN koji spaja tabele na osnovu owner_id -> user_id ali je najlakse i najcitljivije sa EXISTS

---

Vrlo dobro — 9.4/10. Suštinu si pogodio, posebno da je EXISTS ovde semantički najčistiji.
Tvoj mentalni model bih samo malo precizirao:

```text
EXISTS
→ "Da li postoji makar jedan povezani red?"
```

```text
IN
→ "Da li je ova vrednost član skupa koji vraća subquery?"
```

```text
JOIN
→ "Spoji mi matching redove iz obe tabele."
```

Za EXISTS:

```sql
SELECT u.id, u.name
FROM users u
WHERE EXISTS (
    SELECT 1
    FROM hotels h
    WHERE h.owner_id = u.id
);
```

Ovo bukvalno pita za svakog user-a:

postoji li makar jedan hotel
gde je h.owner_id = u.id?

Kod IN bi logika bila:

```sql
SELECT id, name
FROM users
WHERE id IN (
    SELECT owner_id
    FROM hotels
);
```

Ovde subquery pravi skup:

owner_id vrednosti iz hotels

pa outer query pita:

da li je users.id u tom skupu?

Kod JOIN:

```sql
SELECT u.id, u.name
FROM users u
JOIN hotels h
    ON h.owner_id = u.id;
```

dobijaš matching kombinacije redova. Ako jedan user ima 5 hotela, isti user može izaći 5 puta.
Zato bi morao eventualno:

```sql
SELECT DISTINCT u.id, u.name
...
```

ako hoćeš samo jednog user-a po rezultatu.
Najvažnija interview nijansa: nemoj reći da je EXISTS uvek brži od IN ili JOIN. Moderni optimizer može različite forme pretvoriti u veoma slične planove. Ovde ga biramo prvenstveno zato što najbolje izražava nameru:

„ima li makar jedan povezani red?“

---

## Interview pitanje 5

Objasni mi razliku između:

```php
INDEX(city, is_active, base_price)
```

i tri zasebna indeksa:

```php
INDEX(city)
INDEX(is_active)
INDEX(base_price)
```

Posebno objasni leftmost prefix rule i zašto redosled kolona u composite index-u nije nebitan.

---

`INDEX(city, is_active, base_price)` je composite index za razliku od indeksiranja svakog polja pojedinacno.

On postuje leftmost prefix rule sto znaci da ce prioritet imati polja sa leva na desno. U praksi to znaci, ako u upitu imamo city, sam bez ostalih polja, bice indeksiran, a ako imamo is_active, bice indeksiran samo u slucaju da je u kombinaciji sa city, na primer where city = 12 AND is_ativ = true.
Isto tako, indeksirano ce biti i base_price samo u slucaju da se nadje u kombinaciji sa oba prethodna city = 12 and is_active = true and base_price > ...

Kada bismo recimo imali city = 12 and base_price >... tada bi samo city bio indeksiran ali ne i base_prive iz razloga sto nedostaje is_active.

Ovaj composite index je bolja varijanta od zasebnih kada imamo polja koja se mogu cesto pretrazivati ali nemaju tako preciznu i usku pretragu tj nisu kandidati za samostalne indekse jer na primer aktivnih hotela moze biti 95% u tabeli. Tada ih pripojimo sa polji.a sa kojima ihcesto trazimo.

---

Vrlo dobro — 9.3/10. Mentalni model ti je skoro potpuno tačan, ali dve formulacije bih izoštrio da budu intervju-spremne.
Najpre, ovo je dobro:

```php
INDEX(city, is_active, base_price)
```

je jedna composite indeksna struktura, dok:

```php
INDEX(city)
INDEX(is_active)
INDEX(base_price)
```

predstavljaju tri odvojena indeksa. MySQL ponekad može kombinovati zasebne indekse kroz optimizer strategije poput index merge-a, ali to nije isto što i imati jedan dobro dizajniran composite index za konkretan query pattern.
Za leftmost prefix si pogodio suštinu:

(city, is_active, base_price)

city ✅

city + is_active ✅

city + is_active + base_price ✅

is_active samo ⚠️

base_price samo ⚠️

Samo bih izbegao formulaciju:

„is_active će biti indeksiran samo ako je sa city“

Preciznije je:

Composite index je organizovan prvo po city, pa unutar toga po is_active, pa po base_price. Zato query koji preskoči city ne može prirodno koristiti isti leftmost lookup kao query koji kreće od city.

I ovo što si rekao:

```sql
WHERE city = 'Novi Sad'
  AND base_price > 100
```

je dobar primer. MySQL može koristiti city deo composite index-a da suzi pretragu, ali pošto je is_active između, ne možemo očekivati da base_price učestvuje u istom kontinuiranom index range lookup-u kao kod:

```sql
WHERE city = 'Novi Sad'
  AND is_active = TRUE
  AND base_price > 100;
```

Još jedna nijansa: razlog za composite nije samo „slabiji kandidati poput booleana“. Glavni kriterijum je:

koje query-je aplikacija stvarno izvršava?
+
kojim redosledom se kolone koriste?
+
equality / range / sorting?
+
selectivity?

Na primer:

```text
city = equality
is_active = equality
base_price = range
```

je vrlo lep kandidat za:

```php
INDEX(city, is_active, base_price)
```

Dakle ukupno: razumevanje vrlo dobro, samo malo manje apsolutnog jezika oko toga šta indeks „radi/ne radi“.

---

## Interview pitanje 6

Imamo query:

```sql
SELECT id, name
FROM users
WHERE email = 'bojan@test.com';
```

Tabela ima 5 miliona redova.
Objasni mi šta bi ti značilo ako EXPLAIN pokaže:

```text
type: ALL
possible_keys: NULL
key: NULL
rows: 5000000
```

i šta bi promenio u schema-i ako email po business pravilu mora biti jedinstven?

---

Znacilo bi da ce:

---

## Skenirati celu tablu

---

## Nece koristiti nijedan indeks

I pregledati 5M redova.

Logican nas sledeci korak je da dodamo UNIQUE na email polje, sto pre svega garantuje da ce biti unikatan email (ne moze biti 2 ista) a osim toga postavlja index na to polje i samim tim drasticno suzava pretragu.

---

Tačno — 10/10.
Tvoj EXPLAIN prevod je baš dobar:

```text
type: ALL
→ full table scan
```

```text
possible_keys: NULL
→ optimizer nema odgovarajući indeks ni kao kandidata
```

```text
key: NULL
→ nijedan indeks nije izabran
```

```text
rows: 5,000,000
→ procena da mora pregledati praktično celu tabelu
```

A pošto business rule kaže da email mora biti jedinstven, pravi potez nije samo:

```sql
CREATE INDEX idx_users_email
ON users(email);
```

nego:

```sql
ALTER TABLE users
```

---

## ADD CONSTRAINT uq_users_email

UNIQUE (email);

Tako dobijaš oba:

```text
integritet
→ nema dva ista email-a
```

```text
performance
→ unique index za lookup
```

Posle toga bismo ponovo pustili EXPLAIN i očekivali mnogo precizniji plan, tipično lookup nad unique key-em umesto skeniranja 5 miliona redova.

---

## Interview pitanje 7

Objasni mi šta je transaction i šta znači ACID.
Posebno zamisli booking flow:

1. INSERT booking
2. smanji inventory za 1. novembar
3. smanji inventory za 2. novembar
4. smanji inventory za 3. novembar

Ako četvrta operacija padne:

- šta želimo da se desi,
- koji ACID princip to najdirektnije opisuje,
- i da li sama transakcija automatski rešava problem kada dva korisnika istovremeno pokušaju da rezervišu poslednju sobu?

Transaction je operacija koju zelimo da primenimo kada prilikom izmene podatako imamo vise radnji ili vise tabela ukljuceno a da pri tom biznis politika zahteva da budu konzinstentni. U primeru koji si dao zelimo da sve bude izvrseno ili nista, dakle ako padne upis u inventory za poslednji datum me zelimo da ista bude sacuvano.

Ovde konkretno od ACID-a princip A - atomicity ili All or nothing.

Jos imamo:

```text
C - consistency
I - isolation
D - durability
```

Sama transakcija ne resava problem race condition-a odnosno ne zakljucava inventori, za to bismo morali da dodamo FOR UPDATE u upitu.
Vrlo dobro — 9.4/10. Glavna ideja ti je tačna, samo bih precizirao poslednju rečenicu da ne ostane previše apsolutna.

Tačno je:

```text
Transaction
→ grupiše više DB operacija u jednu logičku celinu
```

```text
Atomicity
→ sve uspe ili se sve poništi
```

Za booking primer, ako poslednji inventory update padne, želimo:

```sql
ROLLBACK;
```

pa da ne ostane ni booking ni delimično smanjen inventory.

ACID mapa:

```text
A — Atomicity
→ all or nothing
```

```text
C — Consistency
→ baza prelazi iz validnog stanja u validno stanje
```

```text
I — Isolation
→ paralelne transakcije ne smeju nekontrolisano da utiču jedna na drugu
```

```text
D — Durability
→ nakon uspešnog COMMIT-a podatak ostaje sačuvan
```

Jedina korekcija je ovo:

„transakcija ne zaključava inventory“

Preciznije:

sama činjenica da smo uradili START TRANSACTION
ne znači da će običan SELECT zaključati pročitane redove
UPDATE i druge write operacije svakako koriste lockove, ali flow poput:

```sql
START TRANSACTION;

SELECT available
FROM room_inventory
WHERE id = 15;
```

nije sam po sebi dovoljan da kaže:

„Ovaj row je moj dok ne završim.“

Ako hoćemo locking read:

```sql
SELECT available
FROM room_inventory
WHERE id = 15
FOR UPDATE;
```

Ili možemo koristiti atomic conditional update:

---

## UPDATE room_inventory

---

## SET available = available - 1

```sql
WHERE id = 15
  AND available > 0;
```

pa proveriti affected rows.

---

## Interview pitanje 8

Objasni razliku između:

dirty read
non-repeatable read
phantom read
i reci kako se oni povezuju sa isolation level-ima.

Ne moraš nabrajati svaku internu implementaciju MySQL-a, ali hoću da čujem mentalnu razliku između ta tri concurrency problema.

dirty read - je kada jedna transakcija procita rollback-ovanu vrednost druge transakcije. Na primer: jedna transakcija update price sa 100 na 200 a zatim uradi rollback, a druga transakcija selektuje i pprocita 200 kao novi vrednost.

non-repeatable read je kada imamo komitovane updated values ali: transakcija A procita 100 a potom transakcija B komituje 200, pa onda A opet selektuje i sada procita 200 sto je nekonzistentno.

phantom read - ovde se ne radi o vrednostima, nego o redovima. Transakcija ucita rezervacije, dobije 10 u međuvremenu B doda novu rezervaciju a onda kada A opet pročita, sada dobije 11.
Vrlo dobro — 9.6/10. Razlike si uhvatio gotovo savršeno; samo jednu formulaciju treba precizirati.

Kod dirty read nije baš da čitaš „rollback-ovanu vrednost“, nego:

čitaš UNCOMMITTED vrednost
koja može kasnije biti rollback-ovana
Dakle:

A:

```text
100 → 200
još nema COMMIT
```

B:

pročita 200

A:

ROLLBACK

```text
→ B je pročitao podatak koji nikada nije postao committed state
```

To je dirty read.

Za non-repeatable read ti je objašnjenje tačno:

```text
A pročita isti row → 100
```

```text
B promeni na 200 + COMMIT
A pročita isti row ponovo → 200
```

Dakle:

isti row
dva čitanja
različita committed vrednost
Za phantom read takođe tačno:

nije se promenila vrednost istog reda
nego se promenio SKUP redova koji zadovoljava query
Odličan mnemonic:

```text
dirty read
→ pročitao sam necommitovan podatak
```

```text
non-repeatable read
→ isti row se promenio
```

```text
phantom read
→ promenio se skup row-ova
```

Što se isolation level-a tiče, mentalna mapa je:

```text
READ UNCOMMITTED
→ može dirty read
```

```text
READ COMMITTED
→ sprečava dirty read
→ non-repeatable i phantom i dalje mogu
```

```text
REPEATABLE READ
→ stabilniji snapshot
→ sprečava non-repeatable read za consistent reads
```

```text
SERIALIZABLE
→ najstroža izolacija
→ najmanje concurrency slobode
```

Ne moraš na intervjuu da ulaziš odmah u MVCC, gap locks i next-key locks osim ako te pitaju dublje.

---

## Interview pitanje 9

Objasni mi razliku između:

1NF
2NF
3NF
i daj mi jedan praktičan primer zašto ipak ponekad namerno čuvamo dupliran podatak, recimo booked_price, iako generalno normalizacijom želimo da smanjimo duplication.

1NF 1st normal form - jedna ćelija treba da sadrzi jednu atomsku vrednost, ne skup vrednosti. Primer: hotel_amenities - bolje preko pivot-a.

2NF, second normal form - Svaka non key kolona mora da zavisi od jednog celog primary key-a ne od jednog njegovog dela. Primer: u pivot tabeli booking_id + room_id... ali imamo i room_name koji mora zavisiti iskljucivo od room_id.

3NF third normal form - non key kolone treba da zavise iskljucivo od kljuceva a ne od drugih non key kolona. U tabeli hotels ne treba da stoji city_name, dovoljno je city_id.

Kada ipak zelimo da imamo neke duple kolone iako normalizacija nalaze da svaka jedinica bude odvojena na svoje logicko mesto?

Primer je snapshot trenutne cene sobe koju je user rezervisao u datom momentu. Jer, ta cena kasnije moze biti promenjena i nije korektno da je vadimo iz hotels nego u bookings moramo imati purchased price.
Odlično — 9.8/10. Ovo ti je već vrlo jak interview odgovor.

Samo bih za 2NF malo precizirao formulaciju:

```text
2NF
→ svaka non-key kolona mora zavisiti od CELOG ključa,
  posebno kada imamo composite primary key
```

Dakle, nije poenta „od jednog celog primary key-a“ nego da kod npr:

---

## PRIMARY KEY (booking_id, room_id)

ne smeš imati:

room_name
jer room_name zavisi samo od:

room_id
a ne od cele kombinacije:

booking_id + room_id
To je partial dependency.

Za 3NF si pogodio:

```text
hotel.id
→ city_id
→ city_name
```

city_name ne treba da bude u hotels ako je to činjenica o gradu, ne o hotelu.

I snapshot primer ti je odličan. Samo bih rekao da booked_price najčešće snapshotuje cenu iz room_inventory / konkretnog room-date pricing-a, ne nužno iz hotels, ali logika je potpuno tačna:

```text
current price
→ može se promeniti
```

```text
booked_price
→ mora zauvek predstavljati cenu po kojoj je booking napravljen
```

To više nije „loša duplikacija“, jer su u pitanju dve različite poslovne činjenice.

---

## Interview pitanje 10

Objasni mi razliku između:

DELETE
TRUNCATE
DROP
Posebno me zanima:

šta svaki briše,
koji podržava WHERE,
šta ostaje od schema-e,
i koji od njih ne treba mentalno tretirati kao običan transactional DELETE.
DELETE brise redove jedne tabele i podrzava WHERE što je u praksi i izuzetno bitno, jer bez where:

DELETE FROM hotels bi obrisalo sve hotele,  a nama recimo treba WHERE id = 18.

TRUNCATE prakticno prazni tabelu s tom sto resetuje auto_increment pa bi prvi sledeci unos bio pod brojem 1. Dakle brisu se takodje svi redovi bez uslova i resetuje autoincrement. Takodje spada u DDL ponasanje i ne treba ga smatrati kao delete da je obican transactional delete.

DROP brise celu table bespovratno, tabela fizicki nece postojati vise u bazi.

---

## Ps. Drop moze da obrise i kolonu i index, ne samo table

Odlično — 9.8/10. Sve ključno si pogodio.

Tvoja mapa je dobra:

```text
DELETE
→ briše redove
→ podržava WHERE
→ tabela i schema ostaju
```

```text
TRUNCATE
→ prazni celu tabelu
→ nema WHERE
→ schema ostaje
→ tipično resetuje AUTO_INCREMENT
→ ima DDL semantiku, nije običan transactional DELETE
```

```text
DROP TABLE
→ uklanja samu tabelu
→ nestaju i podaci i struktura te tabele
```

I PS ti je tačan: DROP nije rezervisan samo za tabelu. Imaš, na primer:

```sql
ALTER TABLE hotels
```

DROP COLUMN description;

i:

```sql
DROP INDEX idx_hotels_city
ON hotels;
```

Dakle mentalno:

```text
DROP
→ ukloni schema object
```

samo sintaksa zavisi od toga šta uklanjaš.

Jedina sitna preciznost: kod TRUNCATE je bolje reći da tipično resetuje AUTO_INCREMENT sekvencu, pa sledeći generisani ID obično ponovo bude 1, nego da sam ID „postane 0“.

---

## ROLLBACK TO SAVEPOINT - korake do savepoint vraća na staro

Hm, nisam se dobro izrazio... ja sam gledao unazad. Transakcija ide od koraka 1 ka 5, savepoint je nakon 2, pukne na 5 a onda rollback to savepoint ide od 5 ka 1 i "cisti" sve do 2 do savepoint-a a onda "cuva" nakon... takva mu je bila slika u glavi
Da, sad je jasno — mentalna slika ti je zapravo bila dobra, samo je formulacija zvučala obrnuto.

Ako transakcija ide:

```text
1 → 2 → SAVEPOINT → 3 → 4 → 5
```

i pukne na 5, pa uradimo:

```sql
ROLLBACK TO SAVEPOINT ...
```

možeš mentalno da zamišljaš da ide unazad:

5 ← 4 ← 3 ← SAVEPOINT
i „čisti“ sve što je urađeno nakon savepoint-a.

Ono što je bilo pre njega:

```text
1 → 2
```

ostaje kao deo i dalje otvorene transakcije.

Dakle:

1 ✅ ostaje otvoreno
2 ✅ ostaje otvoreno
SAVEPOINT
3 ❌ poništeno
4 ❌ poništeno
5 ❌ poništeno
Pa tek onda:

```sql
COMMIT;
```

ili:

```sql
ROLLBACK;
```

odlučuju šta će biti konačno.

Tako da bih ti posle ovog pojašnjenja podigao prethodnu ocenu na 9.5/10 — koncept si razumeo, samo si ga opisao iz ugla „rollback ide unazad“.

Idemo na Interview pitanje 14 koje smo upravo otvorili:

Objasni razliku između:

DELETE
soft delete
ON DELETE CASCADE
na primeru hotels i hotel_images.

Posebno me zanima: ako fizički obrišemo hotel sa ON DELETE CASCADE, šta se dešava sa povezanim DB redovima, a šta ako uradimo soft delete preko deleted_at? I da li će ON DELETE CASCADE automatski obrisati i fizičke image fajlove sa filesystem-a?

DELETE - Permanentno brose red iz tabele.

Ako je fk hotel_id u hotel_images tabeli, podesen na ON DELETE CASCADE, to znaci da bi u slucaju brisanja hotela, sve images koje nose njegov hotel_id bile izbrisane iz db, ALI to ne znaci da ce te iste slike biti fizicki obrisane iz file systema. Dakle, da ne bismo imali orphan fajlove na file systemu, nakon brisanja hotela, moramo imati logiku koja radi cleanup slika na filesystemu.

Sa druge strane, soft delete, funkcionise tako sto se uradi UPDATE nad poljem deleted_at i umesto null postavi timestamp kada je obrisano. Slike obrisanog hotela u ovom slucaju nece biti obrisane, za to moramo imati opet posebnu logiku. Prednost: mogucnost restore hotela, mana: rizik da imamo zatrpanu bazu.
Odlično — 10/10. Ovde nema suštinske greške.

Tačno:

```text
DELETE
→ fizički briše DB red
```

```text
ON DELETE CASCADE
→ DB automatski briše povezane child redove
```

```text
filesystem
→ baza o njemu ništa ne zna
→ application mora da radi cleanup
```

Dakle, ako imamo:

```text
hotel_images.hotel_id
REFERENCES hotels(id)
ON DELETE CASCADE
```

i uradimo:

```sql
DELETE FROM hotels
WHERE id = 42;
```

MySQL može automatski obrisati:

hotels row #42
+
sve hotel_images redove sa hotel_id = 42
ali fajlovi poput:

/storage/hotels/42/image1.webp
/storage/hotels/42/image2.webp
ostaju dok ih application eksplicitno ne obriše. To je upravo ona razlika koju smo imali u filesystem modulu:

DB transaction / FK
≠
filesystem transaction
Za soft delete si takođe tačan:

---

## UPDATE hotels

---

## SET deleted_at = CURRENT_TIMESTAMP

```sql
WHERE id = 42;
```

Ovo uopšte nije DELETE, pa se:

ON DELETE CASCADE
ne aktivira.

Hotel red ostaje u bazi, a ostaju i hotel_images redovi,
osim ako application ima dodatnu logiku da i njih soft-delete-uje ili drugačije sakrije.
To je često čak i poželjno ako želiš pravi restore, jer su ti potrebne i slike nakon vraćanja hotela.

I da, tradeoff soft delete-a je upravo:

```text
+ restore
+ audit/history mogućnosti
```

```text
- više podataka ostaje u tabeli
- query-ji moraju voditi računa o deleted_at
- eventualno treba retention/cleanup politika
```

---

## Interview pitanje 15

Objasni mi zašto su ova dva query-ja različita sa aspekta indeksa:

```sql
SELECT *
FROM bookings
WHERE DATE(created_at) = '2026-10-15';
```

i:

```sql
SELECT *
FROM bookings
WHERE created_at >= '2026-10-15 00:00:00'
  AND created_at <  '2026-10-16 00:00:00';
```

Pretpostavi da imamo:

```php
INDEX(created_at)
```

Koji bi izabrao, zašto, i kako se zove osobina query-ja kada je napisan tako da optimizer može efikasno koristiti indeks?

Kada imamo, `INDEX(created_at)`, vazno je razumeti da funkcije nazalost nisu index like,
tj index ne radi kako ocekujemo ako tu postavimo funkciju. Zato je bolje izabrati tvoj
drugi primer (string) umesto prvog (funkcija).

Ovde govorimo o SARGable principu -> Search ARGumentable.
Tačno — 9.8/10.

Suština je baš ovo:

```php
INDEX(created_at)
```

najbolje radi kada indeksiranu kolonu ostavimo „golu“ u poređenju:

```sql
WHERE created_at >= '2026-10-15 00:00:00'
  AND created_at <  '2026-10-16 00:00:00'
```

Dok:

```sql
WHERE DATE(created_at) = '2026-10-15'
```

primenjuje funkciju nad indeksiranom kolonom i običan B-tree indeks često ne može da koristi na isti efikasan način.

Jedina mala korekcija: ne bih rekao „drugi primer je string“, jer glavna razlika nije string vs funkcija. Oba koriste date/time literale. Ključ je:

```text
DATE(created_at)
→ transformišemo indeksiranu kolonu ❌
```

created_at >= ...
AND created_at < ...

```text
→ direktan range nad indeksiranom kolonom ✅
```

I da — termin je SARGable, od otprilike Search ARGument able.

Još jedan plus drugog query-ja je half-open interval:

>= početak dana
< početak sledećeg dana
što je čistije nego pokušavati sa 23:59:59.

---

## Interview pitanje 16

Imamo:

users.email UNIQUE

ali u PHP-u pre INSERT-a takođe proveravamo:

da li email već postoji?

---

## Zašto application validation sama po sebi nije dovoljna da garantuje jedinstven email?

Objasni kroz scenario sa dva istovremena request-a i reci zašto nam i dalje treba DB UNIQUE constraint.

Sama application validation nije dovoljna i zato baza mora biti poslednja linije odbrane. Jer zamislimo da imamo 2 requesta u isto vreme, aplikacija moze da proveri pre upisa ali to ne sprecava race condition. Tako da Reqest A procita da li ima i ako nema upisuje a u medjuvremenu request B je vec napravio isto to.
Tačno — 10/10. To je upravo razlog zašto razlikujemo application validation od database integrity.

Race condition može izgledati ovako:

```text
Request A → SELECT email → ne postoji
Request B → SELECT email → ne postoji
```

```text
Request A → INSERT ✅
Request B → INSERT ❌ ako postoji UNIQUE
```

Bez:

```php
UNIQUE(email)
```

oba INSERT-a bi mogla proći, jer su oba request-a validaciju uradila nad stanjem baze pre nego što je drugi završio upis.

Zato je dobra podela:

```text
Application validation
→ lep i brz feedback korisniku
→ "Email je već zauzet"
```

```text
DB UNIQUE
→ konačna garancija integriteta
→ štiti i od concurrency-ja, bugova, drugih aplikacija i ručnog SQL-a
```

Dakle validaciju u PHP-u ne izbacujemo samo zato što postoji UNIQUE; želimo oba sloja.

---

Hej izvini, slucajno sam tipnuo na salji pre nego sam zavrsio...

Dakle, ako zelimo da nemamo duplikate, koristimo UNION koji uklanja duplikate ali isto tako ima dodatni posao, pa ako nam ne treba deduplikacija, efikasnije je da koristimo UNION ALL.

Takodje, da bi UNION I UNION ALL radili kako treba, oba SELECT-a treba da imaju isti broj polja.
Tačno — sada kompletan odgovor ide na 10/10.

Tvoj mentalni model je odličan:

```text
JOIN
→ horizontalno
→ spaja povezane redove i dodaje kolone
```

```text
UNION
→ vertikalno
→ spaja result setove
→ uklanja duplikate
```

```text
UNION ALL
→ vertikalno
→ zadržava duplikate
→ nema nepotrebnu deduplikaciju
```

I da, oba SELECT-a moraju vratiti isti broj kolona, a tipovi treba da budu međusobno kompatibilni.

Na primer ovo radi:

```sql
SELECT id, name
FROM hotels
```

UNION ALL

```sql
SELECT id, name
FROM archived_hotels;
```

a ovo ne:

```sql
SELECT id, name, city
FROM hotels
```

UNION ALL

```sql
SELECT id, name
FROM archived_hotels;
```

jer prvi vraća 3 kolone, drugi 2.

---

## Interview pitanje 22

Objasni mi razliku između:

---

## CTE / WITH

VIEW
subquery
Posebno:

šta je privremeno samo za jedan query,
šta ostaje sačuvano u schema-i,
i kada bi ti CTE bio čitljiviji izbor od ugnježdenog subquery-ja?
CTE / WITH - Common Table Expresion je posebno dobar, ukoliko subquery postane velik i nepregledan, WITH naziv_cte AS (sub), a onda se poziva kao tabela SELECT naziv_cte... Međutim, ovo nije trajna tabela i koristimo je samo kroz jedan upit.

VIEW - pogled, je vec nesto sto kreiramo i trajno ostaje. Recimo da imamo neki cest uput sa mnogo JOIN-a, kompleksan, tada napravimo VIEW koji funkcionisr kao zasebna tabela u smislu pozivanja, ALI on to realno nije i podaci ce uvek biti rezultat snimljenog upita a ne snapshot trenutnog stanja u bazi prilikom pravljenja VIEW-a.

subquery - je jednostavan podupit, unutar drugog, glavnog upita, oivicen zagradama (). Spoljni upit manipulise podacima koje vrati unutrasnji.
Odlično — 9.8/10. Razlike si objasnio vrlo precizno.

Za CTE je potpuno tačno:

```sql
WITH active_hotels AS (
    SELECT id, name
    FROM hotels
    WHERE is_active = TRUE
)
SELECT *
FROM active_hotels;
```

CTE dobija ime i možemo ga tretirati poput privremenog result seta, ali njegov scope je samo taj SQL statement. Posebno je koristan kada bi veliki ili višestruko ugnježden subquery postao nepregledan.

Za VIEW si pogodio najvažniju nijansu:

```text
VIEW
→ trajno ostaje kao schema object
→ čuva definiciju query-ja
→ standardni VIEW ne čuva snapshot njegovih rezultata
```

Dakle ako se podaci u originalnim tabelama promene:

```sql
SELECT * FROM active_hotels_view;
```

dobiće rezultat prema trenutnom stanju underlying tabela, ne prema stanju kada smo VIEW napravili.

I za subquery:

```sql
SELECT id, name
FROM hotels
WHERE base_price > (
    SELECT AVG(base_price)
    FROM hotels
);
```

tačno — query unutar drugog query-ja, najčešće u zagradama. Mala dopuna: subquery može vraćati jednu vrednost, skup vrednosti ili čak result set koji koristimo kao derived table, a može biti i correlated.

Najkraća interview mapa:

```text
SUBQUERY
→ query unutar query-ja
```

```text
CTE / WITH
→ imenovani privremeni result set
→ samo za trenutni statement
→ odličan za čitljivost
```

```text
VIEW
→ sačuvana query definicija u schema-i
→ može se kasnije ponovo SELECT-ovati
→ nije snapshot podataka
```

---

## Interview pitanje 23

Objasni mi šta radi UPSERT i zašto bi bio koristan za room_inventory.

Imamo constraint:

```php
UNIQUE(room_id, date)
```

i supplier šalje novi:

room_id
date
available
price
Objasni:

šta radi INSERT ... ON DUPLICATE KEY UPDATE,
kako MySQL zna da je nastao „duplicate“,
i zašto je to često bolje od application flow-a:

```text
SELECT da li postoji
→ IF
→ INSERT ili UPDATE
```

Ovaj poslednji application flow koji si dao moze biti dosta sporiji jer:

Prvo pitamo da li postoji, pa ako ne onda insert.

Ako vec imamo composite UNIQUE, MySql vec na osnovu njega moze da prepozna da li mu dolazi duplikat.

Zato koristimo INSERT... ON DUPLICATE KEY UPDATE, gde mysql kada prepozna duplikat, nece odbaciti upit nego ce updateovati poslednji. Dakle:

Nije duplikat i nema ga u tabeli -> INSERT

---

## Ima ga vec u tabeli -> UPDATE postojeći

Tačno — 9.8/10.

Najbitnije si pogodio:

INSERT ... ON DUPLICATE KEY UPDATE

```text
nema konflikta
→ INSERT
```

```text
postoji PK/UNIQUE konflikt
→ UPDATE postojećeg reda
```

I da, `UNIQUE(room_id, date)` upravo daje MySQL-u kriterijum da kaže:

ova kombinacija već postoji.

Jedina važna dopuna: prednost nije samo što izbegavamo dodatni SELECT i application branching, nego i što smanjujemo concurrency problem ovog tipa:

```text
Request A → SELECT → nema reda
Request B → SELECT → nema reda
```

```text
A → INSERT
B → INSERT
```

Sa DB UNIQUE constraint-om baza je poslednja linija odbrane, a upsert nam omogućava da taj slučaj rešimo direktnije u jednoj DB operaciji.

Dakle odličan mentalni model:

```text
UNIQUE constraint
→ definiše šta je duplicate
```

```text
UPSERT
→ ako ne postoji INSERT
→ ako postoji UPDATE
```

---

WHERE id IN (...) - proverava da li se u rezultatu unutrasnjeg uputa nalazi id

WHERE EXISTS (...) - proverava da li postoji u rezultatu upita s tim sto u podupitu mozemo pitati sa 1 - da li postoji makar jedan.

Ovaj drugi je bolji kada trazimo sa negacijom NOT in / exists, jer NOT IN u slucaju da unutrasnji upit vrati null moze da vrati neocekivano da id nije medju trazenim, dok je NOT EXISTS precizniji i eksplicitno trazi makar 1 da li postoji.
Dobar odgovor — 9/10. Suštinu si pogodio, ali NOT IN + NULL vredi precizno razumeti jer je klasična SQL zamka.

Kod:

```sql
WHERE id IN (
    SELECT owner_id
    FROM hotels
)
```

pitamo:

da li je id član skupa vrednosti koje je vratio subquery?

Kod:

```sql
WHERE EXISTS (
    SELECT 1
    FROM hotels h
    WHERE h.owner_id = u.id
)
```

pitamo:

postoji li makar jedan matching red?

SELECT 1 samo pokazuje da sadržaj reda nije bitan — EXISTS gleda samo da li red postoji.

Problem sa NOT IN nastaje ovako. Recimo subquery vrati:

15
20
NULL
a proveravamo:

```sql
WHERE id NOT IN (15, 20, NULL)
```

Za id = 30, SQL praktično mora da razmotri:

```text
30 != 15   → TRUE
30 != 20   → TRUE
30 != NULL → UNKNOWN
```

A zbog SQL three-valued logic:

```text
TRUE AND TRUE AND UNKNOWN
→ UNKNOWN
```

WHERE zadržava samo TRUE, ne UNKNOWN, pa red može potpuno ispasti iz rezultata.

Zato NOT IN sa nullable skupom može napraviti veoma iznenađujući rezultat — čak i da ne vrati nijedan red koji si očekivao.

NOT EXISTS je često prirodniji:

```sql
SELECT u.id, u.name
FROM users u
WHERE NOT EXISTS (
    SELECT 1
    FROM hotels h
    WHERE h.owner_id = u.id
);
```

Ovde za svakog user-a pitamo direktno:

postoji matching hotel?

```text
DA → izbaci user-a
NE → zadrži user-a
```

Ne zavisimo na isti način od NULL vrednosti u listi.

Mentalno:

```text
IN
→ članstvo u skupu
```

```text
EXISTS
→ postoji matching red
```

```text
NOT IN + NULL
→ oprez zbog UNKNOWN
```

```text
NOT EXISTS
→ često najjasniji za "ne postoji povezani zapis"
```

---

## Interview pitanje 26

Objasni mi razliku između:

---

## PRIMARY KEY (hotel_id, amenity_id)

i:

id BIGINT PRIMARY KEY
+

```php
UNIQUE(hotel_id, amenity_id)
```

na primeru pivot tabele hotel_amenities.

Koje su prednosti/mane oba pristupa i zašto ORM poput Laravel Eloquent-a često čini surrogate id praktičnijim?

Primary key je osnovni identifikator reda i on isto kao i Unique ne može da se ponavlja, s tim što samo jedna kolona može biti primary dok unique može nositi više kolona, a takođe primary ne može nikako biti null.

`UNIQUE(hotel_id, amenity_id)` je composite unique index i značilo bi: "Ova kombinacija ne sme biti ponovljena 2 puta". Dakle može biti nekoliko istih hotel_id/amenity_id posebno gledano ali ako imamo upareno 1 -> 1:

1-> 2 ili 2 > 1 može
1 -> 1 opet - NE može.

Prvi pristup:

Prednosti:

Manja tabela i manje indeksa (jedan indeks umesto dva, nema id kolone).
Model je semantički čist: red jeste veza između dva entiteta, ništa više.
Nema sekvence koja se troši niti “rupa” u ID-jevima.
Mane:

Referenciranje reda iz drugih tabela zahteva dve kolone umesto jedne.
Većina ORM-ova i alata slabo podržava composite ključeve.
Ako veza kasnije dobije svoj život (cena, status, redosled), složeni ključ počinje da smeta.

Drugi pristup:

Prednosti:

Svaki red ima jedinstven jednokolonski identitet, što olakšava ORM-ove, API-je (DELETE /hotel-amenities/42), admin panele, logove i audit.
Lako se dodaje FK iz druge tabele na ovu vezu.
Lakše je kasnije proširiti vezu atributima.

Mane:

Dodatna kolona i dodatni indeks (PK indeks na id plus unique indeks na par), dakle više prostora i sporiji upisi.
id je redundantan: pravi poslovni ključ je i dalje par.
Ako zaboraviš UNIQUE, imaš duplikate. Surrogate ključ sam po sebi ništa ne štiti, pa je constraint obavezan, ne opcionalan.
U InnoDB-u su redovi fizički poređani po id, ne po hotel_id, pa čitanje svih pogodnosti jednog hotela može biti razbacano po disku (ublažava ga indeks, ali je malo manje efikasno).

---

## Zašto je u Laravelu surrogate id praktičniji??

Eloquent pretpostavlja jednokolonski PK. Model ima `$primaryKey` (jedna kolona), `find($id)`, `save()`, `delete()` i `update()` rade preko tog ključa. Eloquent ne podržava composite primary keys, pa model nad takvom tabelom ne možeš normalno koristiti.
Klasični pivot (belongsToMany) radi i bez id. `attach()`, `detach()`, `sync()` rade sa composite ključem bez problema, jer ne koriste id pivot reda. Problem nastaje tek kad pivot postane pun model.
Pivot kao pravi model (->`using(HotelAmenity::class)`). Ako želiš događaje, accessore, relacije ili `$pivot->id`, model mora da ima identitet. Sa composite ključem `$pivot->save`() i `$pivot->delete`() ne mogu pouzdano da ciljaju jedan red.
…
Odlično razrađeno — 9.7/10. Ovo je već odgovor iznad tipičnog junior/junior-mid nivoa.

Jedina stvar koju moramo obavezno ispraviti je ova rečenica:

„samo jedna kolona može biti primary“

Ne baš. Tabela može imati samo jedan PRIMARY KEY constraint, ali taj primary key može sadržati više kolona:

PRIMARY KEY (hotel_id, amenity_id)

Dakle:

jedan PRIMARY KEY po tabeli ✅

---

## PRIMARY KEY može biti composite ✅

---

## PRIMARY KEY kolone ne mogu biti NULL ✅

Prvi pristup:

PRIMARY KEY (hotel_id, amenity_id)

je sasvim čist za klasičnu junction tabelu:

hotel_amenities
jer je identitet reda upravo:

hotel + amenity
i nema potrebe za dodatnim id ako taj red nikada ne tretiramo kao zaseban entitet.

Drugi pristup:

id BIGINT PRIMARY KEY,
UNIQUE (hotel_id, amenity_id)

je takođe potpuno validan. Posebno dobro si naglasio da id ne zamenjuje composite UNIQUE.

Bez njega bi ovo bilo moguće:

```text
id 1 → hotel 5 + amenity 8
id 2 → hotel 5 + amenity 8
```

PK je zadovoljan jer su id različiti, ali business pravilo je prekršeno.

Zato:

```text
surrogate id
→ identitet reda
```

```text
UNIQUE(hotel_id, amenity_id)
→ business integrity veze
```

A Laravel deo ti je posebno dobar. Običan `belongsToMany()` pivot često sasvim lepo radi bez sopstvenog id, dok stvari postaju komplikovanije kada pivot počne da se ponaša kao pravi model sa sopstvenim lifecycle-om, relacijama i operacijama.

Samo bih na intervjuu možda izostavio deo o InnoDB fizičkom rasporedu na disku osim ako te pitaju dublje — nije pogrešan smer razmišljanja, ali odvlači razgovor u storage-engine detalje koji nisu potrebni za osnovno pitanje.

---

## Interview pitanje 27

Objasni mi šta znači covering index.

Recimo imamo query:

```sql
SELECT city, base_price
FROM hotels
WHERE city = 'Novi Sad'
  AND is_active = TRUE;
```

i indeks:

```php
INDEX(city, is_active, base_price)
```

Šta znači kada kažemo da indeks može da „pokrije“ query i zašto to može biti brže nego kada MySQL pronađe red preko indeksa, a zatim mora dodatno da ode do same tabele po ostale kolone?

`INDEX(city, is_active, base_price)` je fantastičan primer composite indexa gde imamo čest query nad određenim kolonama gde je prioritet s leva na desno, a recimo drugu i treću kolonu nema smisla da tražimo po zasebnim indeksima.

Ovde se poštuje leftmost pravilo + najbolja kombinacija pretrage bi bila:

equal -> equal -> range, kao što je ovde.

Leftmost -> city će uvek biti pretraživan po indeksu, is_active samo ako je u kombinaciji city. is_active, a base_price samo ako je u kombinaciji city, is_active, base_price.

Ovo je bolji i efikasniji pristup nego da imamo zasebne indekse jer, kao prvo, ne trebaju nam zasebni indeksi za druge 2 kolone jer index nije besplatan, a s druge strane, composite se lakše pronalazi nego da sql ide i traži jedan po jedan što je sporije.
Dobar opis composite index-a, ali na konkretno pitanje o covering index-u nisi zapravo odgovorio. Zato bih za ovo dao 6.5/10 — ne zato što je napisano pogrešno, nego zato što si objasnio drugi koncept.

Covering index znači da indeks sadrži sve kolone koje su query-ju potrebne, pa MySQL može da odgovori direktno iz indeksa bez dodatnog odlaska do reda u tabeli.

Imamo:

```sql
SELECT city, base_price
FROM hotels
WHERE city = 'Novi Sad'
  AND is_active = TRUE;
```

i:

```php
INDEX(city, is_active, base_price)
```

Query koristi:

WHERE:

```text
city
is_active
```

SELECT:

city
base_price
Sve tri potrebne kolone već postoje u indeksu:

(city, is_active, base_price)
Zato indeks može da pokrije query.

Mentalno:

bez covering index-a:

```text
INDEX
→ pronađi matching row
→ idi u samu tabelu
→ pročitaj ostale potrebne kolone
```

sa covering index-om:

```text
INDEX
→ pronađi matching row
→ sve potrebne vrednosti već su ovde
→ gotovo
```

To može biti brže jer ima manje dodatnih lookup-ova / page access-a.

Na primer, ako promenimo query:

```sql
SELECT city, base_price, name
FROM hotels
WHERE city = 'Novi Sad'
  AND is_active = TRUE;
```

a indeks i dalje ostane:

```php
INDEX(city, is_active, base_price)
```

onda više nije potpuno covering, jer:

name
nije u indeksu.

MySQL može indeksom pronaći odgovarajuće redove, ali zatim mora da ode po name iz same tabele.

Važna stvar: covering nije posebna vrsta indeksa.

Isti composite index:

```php
INDEX(city, is_active, base_price)
```

može biti covering za jedan query, a ne-covering za drugi.

Dakle:

```text
composite index
→ indeks ima više kolona
```

```text
covering index
→ za konkretan query indeks sadrži sve što query treba
```

I još jedna korekcija iz tvog odgovora: naš query nema base_price kao range uslov, pa ovde nije primer:

```text
equality → equality → range
```

nego:

city = equality
is_active = equality
base_price = samo SELECT output

---

## Interview pitanje 28

Imamo:

```php
INDEX(city, is_active, base_price)
```

---

## Koji od ova dva query-ja je covering tim indeksom i zašto?

```sql
SELECT city, base_price
FROM hotels
WHERE city = 'Novi Sad'
  AND is_active = TRUE;
```

i:

```sql
SELECT id, name, city, base_price
FROM hotels
WHERE city = 'Novi Sad'
  AND is_active = TRUE;
```

Ne zanima me sada leftmost pravilo — samo da li indeks sadrži sve podatke koje SELECT treba.

Prvi upit jeste covering jer ima sva polja iz indexa dok drugi nije, jer sql mora posebno da ide po name (id je sam po sebi indeksiran)
Da — 9.8/10. Suština ti je tačna.

Prvi query:

```sql
SELECT city, base_price
FROM hotels
WHERE city = 'Novi Sad'
  AND is_active = TRUE;
```

jeste covering za:

```php
INDEX(city, is_active, base_price)
```

jer sve što query treba postoji u indeksu:

WHERE:

```text
city
is_active
```

SELECT:

city
base_price
Drugi:

```sql
SELECT id, name, city, base_price
FROM hotels
WHERE city = 'Novi Sad'
  AND is_active = TRUE;
```

nije potpuno covering zbog:

name
koji nije deo tog indeksa.

Mala napredna MySQL/InnoDB nijansa: kod secondary index-a InnoDB u njegovim zapisima čuva i vrednost PRIMARY KEY-a radi pronalaženja originalnog reda. Zato id u ovom konkretnom slučaju često ne pravi problem za covering; name je ono zbog čega mora da ode do clustered table row-a.

Dakle mentalno:

```php
INDEX(city, is_active, base_price)
```

city ✅
is_active ✅
base_price ✅
id ✅ često dostupan kroz InnoDB secondary index jer je PK
name ❌
I dobro je što sada razdvajaš:

```text
leftmost prefix
→ može li indeks efikasno da pronađe redove?
```

```text
covering
→ ima li indeks sve podatke potrebne query-ju?
```

---

## Interview pitanje 29

Imamo query:

```sql
SELECT id, name
FROM hotels
WHERE city = 'Novi Sad'
ORDER BY base_price ASC;
```

i indeks:

```php
INDEX(city, base_price)
```

Objasni mi zašto ovaj indeks može pomoći i za WHERE i za ORDER BY.
I šta bi se potencijalno promenilo kada bismo imali samo:

```php
INDEX(city)
```

pa MySQL mora dodatno da sortira pronađene hotele po base_price?

Zato isti indeks rešava oba dela upita:

WHERE koristi prvu kolonu (city) da suzi pretragu na jedan opseg.
ORDER BY koristi drugu kolonu (base_price): pošto je unutar tog opsega redosled već tačan, MySQL samo čita unose redom i ne sortira ništa.

Šta se menja sa samo `INDEX(city)`

Sa tim indeksom MySQL i dalje brzo nađe sve hotele iz Novog Sada, ali unutar tog opsega unosi su poređani po primarnom ključu (InnoDB u svaki sekundarni indeks implicitno dodaje PK), a ne po ceni. Zato mora:

da pročita sve pronađene unose,
da za svaki ode u clustered indeks po name (i base_price, jer ih indeks ne sadrži),
da ih sortira u memoriji (filesort),
da vrati rezultat.

Odlično — 9.8/10. Ovo je već vrlo dobar odgovor o indeksima.
Tačno si objasnio zašto:

```php
INDEX(city, base_price)
```

može da posluži za oba dela:

```sql
WHERE city = 'Novi Sad'
ORDER BY base_price ASC
```

Indeks je organizovan otprilike:

```text
city
  ↓
base_price
```

pa MySQL prvo pronađe odgovarajući city range, a unutar tog range-a base_price je već uređen. Zato može da izbegne posebnu sort operaciju.
I deo sa samo:

```php
INDEX(city)
```

ti je takođe dobar. MySQL može brzo pronaći Novi Sad, ali indeks mu ne daje traženi redosled po base_price, pa rezultate mora dodatno sortirati.
Samo jedna nijansa: nemoj filesort izjednačiti nužno sa:

„sortiranje u memoriji“.

Using filesort je naziv MySQL algoritma za dodatno sortiranje koje nije rešeno redosledom indeksa. Može koristiti memoriju, a zavisno od količine podataka i podešavanja može uključiti i privremeni disk rad. „Filesort“ ne znači automatski „piše fajl na disk“.
I još dobro što si primetio da:

```sql
SELECT id, name
```

nije covering za (city, base_price) zbog name, pa MySQL i dalje može morati da ode do clustered row-a. To ne poništava korist indeksa za WHERE + ORDER BY.

---

## Interview pitanje 30

U EXPLAIN rezultatu vidiš u Extra:

```text
Using index
Using filesort
Using temporary
```

Objasni mi svojim rečima šta svaka od ove tri stavke okvirno znači.
Posebno: da li je Using filesort automatski znak da je query loš i da li Using index znači samo „MySQL koristi neki indeks“?

---

Using index	- najbolji slučaj za čitanje, covering

Using filesort - na malom skupu	uglavnom bezbedno

---

## Using filesort - na velikom skupu ili uz LIMIT	kandidat za bolji indeks

Using temporary - Using filesort	tipično GROUP BY/DISTINCT nad nepoređanim podacima, vredi pogledati

Using filesort ne znači automatski da je loše, nego samo ukoliko imamo lIMIT ili ogroman skup da bi trebalo razmisliti o indeksu.

Using indes - Ne znači samo “koristi neki indeks”. To je covering index:

MySQL je sve kolone koje upit traži našao unutar samog indeksa i nikad ne ide u tabelu, po ostatak reda.
Vrlo dobro — 9.4/10. Najvažnije si pogodio, posebno oko Using index.

Tačno je:

```text
Using index
→ query je covering
→ potrebne kolone može da pročita iz indeksa
→ ne mora po ostatak reda u tabelu
```

I baš je važno da to nije isto što i:

key = idx_something
key znači da je neki indeks izabran za pristup podacima, dok:

Extra: Using index
govori da indeks pokriva ono što query treba.

Za Using filesort si takođe dobro rekao da nije automatski problem. Znači:

ORDER BY nije zadovoljen prirodnim redosledom odgovarajućeg indeksa, pa MySQL mora dodatno da sortira.

Ali mala korekcija: LIMIT sam po sebi ne čini Using filesort problematičnim.
Zapravo MySQL ponekad može vrlo efikasno sortirati mali top-N rezultat. Gledamo:

koliko redova se sortira?
koliko često query radi?
koliko su redovi veliki?
postoji li indeks koji bi prirodno rešio WHERE + ORDER BY?
Za Using temporary:

```text
Using temporary
→ MySQL koristi internu privremenu tabelu
  za deo obrade result seta
```

često kod:

---

## GROUP BY

DISTINCT

---

## ORDER BY

UNION
ili kompleksnijih kombinacija.

Može se pojaviti zajedno sa:

Using temporary; Using filesort
ali nisu ista stvar.

Mentalno:

```text
Using index
→ covering
```

```text
Using filesort
→ dodatno sortiranje
```

```text
Using temporary
→ privremena tabela za intermediate result
```

Ni Using temporary ni Using filesort nisu automatski alarm; gledamo kontekst i količinu podataka.

---

## Interview pitanje 31

Objasni mi razliku između:

DATE
DATETIME
TIMESTAMP
i daj po jedan primer iz booking aplikacije gde bi svaki imao smisla.

Posebno me zanima: zašto check_in često može biti DATE, a created_at traži datum + vreme?

```text
DATE - samo datum i pogodan je za check_in jer nam za to baš treba datum ulaska
a vreme može zavisiti od biznis politike svakog hotela po naosob
```

DATETIME - Datum i vreme i pogodan može biti za upis istorijskih datuma jer ima veći opseg
i e konvertuje se prema vremenskim zonama što upišeš to ti je (datum rođenja na primer)

---

## TIMESTAMP - datuim i vreme ali od UNIX epohe i on je najpogodniji za created_at

Vrlo dobro — 9.2/10. Mentalna razlika ti je dobra, ali bih ispravio dva detalja.

```text
DATE
→ samo datum
→ nema vreme
```

Za check_in je odličan izbor ako rezervacija znači „dolazak tog kalendarskog dana“, dok konkretno vreme prijave dolazi iz hotelske politike.

check_in DATE

Za DATETIME si dobro rekao:

```text
→ datum + vreme
→ vrednost se čuva kao kalendarski datum/vreme
→ MySQL je ne konvertuje automatski prema session timezone-u
```

Ali datum rođenja nije dobar primer za DATETIME, jer nam tu vreme uglavnom uopšte ne treba. Za njega bih koristio:

birth_date DATE

Bolji DATETIME primer bio bi nešto poput:

appointment_at
event_starts_at
hotel_local_checkin_deadline
kada želiš da sačuvaš baš određenu kalendarsku vrednost „kako je zapisana“.

Za TIMESTAMP si uhvatio važnu ideju, ali bih je precizirao:

```text
TIMESTAMP
→ datum + vreme
→ MySQL ga povezuje sa timezone konverzijom
→ zgodan za event/audit trenutke poput created_at
```

Kod njega MySQL radi konverzije između session timezone-a i interne reprezentacije. Zato je često zgodan za trenutke kao:

created_at
updated_at
logged_at
Ali nemoj na intervjuu reći da created_at mora biti TIMESTAMP. Sasvim legitimno je imati:

created_at DATETIME

posebno ako aplikacija ima sopstvenu jasnu UTC strategiju. Bitna je konzistentna odluka.

Najkraće:

```text
DATE
→ kalendarski datum
→ check_in, check_out, birth_date
```

```text
DATETIME
→ datum + vreme "kako je zapisano"
→ bez automatske timezone konverzije
```

```text
TIMESTAMP
→ trenutak u vremenu
→ timezone-aware MySQL ponašanje
→ često audit timestamps
```

---

## Interview pitanje 32

Objasni mi šta je razlika između:

optimistic locking
pessimistic locking
Na primeru rezervacije poslednje sobe.

Posebno: gde bi se uklopio naš:

```sql
SELECT ... FOR UPDATE
```

a kako bi izgledala ideja optimistic pristupa sa, recimo, version kolonom ili uslovnim UPDATE-om?

Ovo je klasičan race condition problem - 2 requesta gađaju istu, poslednju sobu.

---

## Pessimistic: “Verovatno će doći do konflikta, zato zaključaj odmah.”

Optimistic: “Konflikt je redak, zato ne zaključavaj, nego na kraju proveri da li se nešto promenilo.”

Optimistic locking: bez zaključavanja

Ne zaključavamo ništa pri čitanju. Umesto toga, pri upisu proveravamo da li se red promenio otkako smo ga pročitali. Postoje dve uobičajene varijante.

```text
Varijanta 1: version kolona
sql
-- čitanje (bez lock-a)
SELECT available_rooms, version FROM room_types WHERE id = 5;
-- vidiš: available_rooms = 1, version = 7
```

-- upis, samo ako se verzija nije promenila

---

## UPDATE room_types

SET available_rooms = available_rooms - 1,
version = version + 1

```sql
WHERE id = 5
  AND version = 7;
```

Aplikacija proverava broj pogođenih redova (affected rows):

1 znači da si uspeo, niko se nije umešao.
0 znači da je neko drugi promenio red između tvog čitanja i upisa. Ponovi čitanje i probaj opet, ili vrati grešku korisniku.

Korisnik A i B oba pročitaju version = 7.
A prvi izvrši UPDATE i uspe (verzija postaje 8).
B-ov UPDATE ... WHERE version = 7 pogađa 0 redova, pa B zna da je izgubio trku.

Varijanta 2: uslovni UPDATE bez version kolone

Za slučaj brojača ne treba ni version, jer sam uslov štiti invarijantu:

sql

---

## UPDATE room_types

---

## SET available_rooms = available_rooms - 1

```sql
WHERE id = 5
  AND available_rooms > 0;
```

affected rows = 1: rezervacija uspela.
affected rows = 0: nema više soba.

Pessimistic locking: SELECT ... FOR UPDATE

Ovde se uklapa FOR UPDATE. Zaključavamo red pre čitanja odluke, unutar transakcije:

```text
sql
START TRANSACTION;
```

```sql
SELECT available_rooms
FROM room_types
WHERE id = 5
FOR UPDATE;            -- zaključava red, drugi čekaju
```

-- aplikacija proverava: available_rooms > 0 ?

```sql
UPDATE room_types SET available_rooms = available_rooms - 1 WHERE id = 5;
INSERT INTO reservations (...) VALUES (...);

COMMIT;                -- lock se otpušta
```

Šta se dešava: korisnik A zaključa red. Korisnik B izvrši isti SELECT ... FOR UPDATE
i čeka dok A ne uradi COMMIT. Kad B dobije red, vidi available_rooms = 0 i odbija rezervaciju.

Prednosti:

Jednostavna logika, konflikt se ne može desiti.
Nema ponavljanja (retry) u aplikaciji.
Dobro kad je konflikt čest (npr. popularna soba, rasprodaja).

Mane:

Transakcije čekaju jedna drugu, što smanjuje propusnost.
Rizik od deadlocka ako više redova zaključavaš različitim redosledom.
Lock traje dok traje transakcija, pa ne smeš unutra raditi spore stvari (HTTP pozive, plaćanje).
Radi samo unutar jedne transakcije, ne preko više HTTP zahteva (korisnik gleda formu 5 minuta).
…
Odlično — 9.8/10. Ovo je već odgovor koji bih bez problema očekivao od kandidata iznad čistog junior nivoa.

Mentalni model ti je tačan:

```text
Pessimistic locking
→ očekujem konflikt
→ zaključavam resurs pre odluke
```

```text
Optimistic locking
→ očekujem da je konflikt redak
→ ne zaključavam pri čitanju
→ pri pisanju proveravam da li je stanje ostalo isto
```

SELECT ... FOR UPDATE si smestio tačno gde treba: pessimistic locking unutar otvorene transakcije.

I version primer je odličan:

---

## UPDATE room_types

SET available_rooms = available_rooms - 1,
version = version + 1

```sql
WHERE id = 5
  AND version = 7;
```

pa:

```text
affected rows = 1
→ pobedio sam race
```

```text
affected rows = 0
→ neko je promenio stanje pre mene
```

Još mi je bolji tvoj drugi primer za inventory:

---

## UPDATE room_types

---

## SET available_rooms = available_rooms - 1

```sql
WHERE id = 5
  AND available_rooms > 0;
```

Za ovakav prost invariant često ni ne treba eksplicitna version kolona — sama baza atomically proverava uslov i radi decrement.

Dve male preciznosti za intervju. Nemoj reći za pessimistic locking:

„Nema retry-a.“

Obično nema optimistic-style retry-a zato što je neko promenio version,
ali i pessimistic transaction može biti abortovana zbog deadlock-a ili lock timeout-a,
pa application i dalje ponekad mora imati retry mehanizam.

I umesto:

„konflikt se ne može desiti“

preciznije:

odgovarajući lock sprečava da dve transakcije istovremeno donesu odluku nad istim zaključanim stanjem.

To je jača tehnička formulacija.

Posebno dobro što si rekao da ne držiš DB lock dok korisnik pet minuta gleda formu ili dok čekaš spor HTTP/payment poziv.
Lock pripada kratkoj DB transakciji, ne čitavom korisničkom flow-u.

---

## Interview pitanje 33

Objasni mi šta je SQL injection i zašto ovo nije bezbedno:

```php
$email = $_POST['email'];

$sql = "
    SELECT *
    FROM users
    WHERE email = '$email'
";
```

Zatim objasni zašto su PDO prepared statements + bound parameters pravi pristup.

Posebno me zanima mentalna razlika između:

---

## SQL strukture

i:

untrusted vrednosti korisnika
i zašto obična validation/sanitization nije zamena za prepared statements.

Ovo povezujemo sa ranijim modulima?

Iako PDO još nismo radili, znam da je ovaj primer loš (inače sam i sam prve svoje redove tako pisao pa znam),
jer je u ovom slučaju email nevalidirani user controlled podatak kome ne smemo slepo verovati.
Čak  i da je savršeno prošao validaciju ne smemo mu verovati jer validacija pita samo
da li je validan string/email/određeni br karaktera i slično.

Zato se koriste prepared statements na primer :email, pa se radi bind (povezivanje) vrednosti, pa tek onda execute.
Da — ovo namerno povezujemo sa ranijim modulima, posebno sa Modulom 3 (validation/sanitization) i pravimo most ka sledećem Modulu 7 — PDO.

Za tvoj odgovor: 9.6/10.

Najvažnije si rekao upravo kako treba: problem nije samo što je `$email` „nevalidiran“. Čak i kada prođe:

```php
filter_var($email, FILTER_VALIDATE_EMAIL)
```

i dalje ga tretiramo kao untrusted data i ne lepimo ga direktno u SQL.

Mentalna podela je:

```text
SQL struktura
→ određuje aplikacija
```

```sql
SELECT ...
FROM users
WHERE email = ...
```

```text
korisnička vrednost
→ podatak
→ nikad ne sme postati deo SQL sintakse
```

Prepared statement upravo odvaja te dve stvari:

```php
$stmt = $pdo->prepare(
    'SELECT * FROM users WHERE email = :email'
);

$stmt->execute([
    'email' => $email,
]);
```

Bazi konceptualno šaljemo:

SQL:

```sql
WHERE email = :email
```

DATA:

```php
$email
```

umesto da napravimo jedan string gde korisnički input može da utiče na značenje SQL-a.

I potpuno si pogodio razliku sa validation:

```text
validation
→ "Da li je ovo validan email prema mojim pravilima?"
```

```text
prepared statement
→ "Ovu vrednost tretiraj isključivo kao podatak, ne kao SQL kod."
```

Zato:

validation ≠ SQL injection protection

sanitization ≠ zamena za parameter binding
PDO ćemo u sledećem modulu detaljno raditi: `prepare()`, `execute()`, named/positional placeholders,
`bindValue()`, `bindParam()`, fetch modes, transactions, errors itd.

---

## Interview pitanje 34

Vratimo se sada čisto na MySQL.

Objasni razliku između:

application validation
DB constraint
business/domain validation
Na primeru booking-a reci gde bi proveravao:

email mora biti validnog formata
check_out mora biti posle check_in
hotel mora imati dostupnu sobu za sve tražene datume
Ne tražim samo „PHP ili MySQL“, nego zašto određeno pravilo pripada određenom sloju.

application validation - validacija podataka unutar naše aplikacije - servisa, proveravamo da li je podatak string/karakteri
i ostalo i to radimo pre bilo kakvog pokušaja unosa u bazu.

DB constraint - Baza = poslednja linija odbrane. Dakle ne može biti prekršeno pravilo na primer unique.

business/domain validation - Ovo se isto radi u okviru aplikacije a može se naravno postaviti
i u bazi (primer: check in < check_out)

email mora biti validnog formata - aplikacija
check_out mora biti posle check_in - idealno i aplikacija i Baza kao poslednja linija odbrane
hotel mora imati dostupnu sobu za sve tražene datume - takođe i app i baza
Dobar odgovor — 9.2/10. Najvažniju podelu razumeš, ali poslednji primer traži jednu važnu nijansu.

application validation je uglavnom validacija samog inputa:

email postoji?
string je?
validan format?
max 255 karaktera?
datum je ispravnog formata?
Cilj je da korisnik dobije brz i razumljiv odgovor pre nego što dođemo do ozbiljne business/DB logike.

DB constraint je, kao što kažeš, poslednja linija odbrane za pravila koja baza može pouzdano sama da garantuje:

email `VARCHAR(255)` NOT NULL UNIQUE,

CHECK (check_out > check_in),

FOREIGN KEY (hotel_id) REFERENCES `hotels(id)`

Odlično si rekao: čak i ako aplikacija proveri UNIQUE, baza mora imati pravi UNIQUE constraint zbog concurrency-ja.

business/domain validation je malo drugačija kategorija. Nije pitanje samo „da li je vrednost validna“, nego:

---

## Da li je ova operacija dozvoljena prema pravilima sistema u trenutnom stanju?

Na primer:

hotel mora imati dostupnu sobu
za SVAKI datum rezervacije
To je primarno domain/business logika.

Recimo:

```text
2026-10-20 → available 1
2026-10-21 → available 1
2026-10-22 → available 0
```

Input može biti savršeno validan:

check_in  = validan DATE
check_out = validan DATE
room_id   = validan integer
ali booking kao poslovna operacija nije moguć.

Tu bih ispravio samo tvoje:

„takođe i app i baza“

Da, baza učestvuje, ali ne nužno kroz običan CHECK constraint. Ovo je dinamičko pravilo koje
zavisi od više redova i trenutnog stanja inventory-ja.

U BookYourHotel/StayCore stilu to bi bilo nešto poput:

```text
Service/domain logic
        ↓
START TRANSACTION
        ↓
proveri inventory za svaki datum
        ↓
FOR UPDATE / atomic UPDATE
        ↓
ako svi datumi imaju availability
    napravi booking
    smanji inventory
    COMMIT
```

inače
ROLLBACK
Dakle odlična mentalna mapa:

```text
Application validation
→ da li je input ispravan?
```

```text
Business/domain validation
→ da li je operacija dozvoljena?
```

```text
DB constraints
→ koja pravila baza mora da garantuje bez obzira ko joj pristupa?
```

I jedno pravilo može pripadati u više slojeva:

check_out > check_in

```text
app
→ lep error korisniku
```

```text
domain
→ booking sa obrnutim datumima nema smisla
```

```text
DB CHECK
→ nemoguće upisati nevalidno stanje
```

---

## Interview pitanje 35

Ovo neka bude jedan ozbiljniji završni scenario.

Imamo tabelu:

```text
room_inventory
--------------
id
room_id
date
available
price
```

```php
UNIQUE(room_id, date)
```

Dva korisnika istovremeno pokušavaju da rezervišu istu poslednju sobu za tri noći.

Objasni mi kompletan bezbedan DB flow od početka do kraja:

```sql
START TRANSACTION
```

?
?
?

---

## COMMIT / ROLLBACK

Zanima me:

kako proveravaš sva 3 datuma,
kako sprečavaš race condition,
kada praviš booking,
kada smanjuješ inventory,
šta radiš ako samo jedan od tri dana nema availability,
i zašto sve mora biti jedna transakcija.
Neću ovde pisati kod nego dajem sliku koraka:

```sql
START TRANSACTION:

SELECT ... FOR UPDATE; -> proveravamo availability + lock (za svaku sobu i datum) ali sa uslovom i OREDER by date:
```

---

## AND date >= '2026-10-10' AND date < '2026-10-13'

```sql
ORDER BY date            -- uvek isti redosled zbog deadlocka
```

ako ima availability:

INSERT u bookings

UPDATE inventory - smanjujemo za koliko soba je uzeto za određene datume. Najbolje na primer za jednu sobu:

SET available_rooms = available_rooms - 1 - sql ima već state.

---

## Da li je sve prošlo?

---

## DA -> COMMIT

NE -> ROLLBACK

PS. ima i druga varijanta bez eksplicitnog zaključavanja a tada bi smo proveravali da li je affected_rows == 3
…

Размишљао 20s

Da — ovo je vrlo dobro postavljeno: 9.8/10.

Tvoj flow je suštinski ispravan:

```text
START TRANSACTION
    ↓
SELECT inventory rows FOR UPDATE
    ↓
proveri sva 3 dana
    ↓
INSERT booking
    ↓
UPDATE inventory
    ↓
COMMIT
```

Najvažnija stvar koju bih samo eksplicitno dodao jeste: nije dovoljno proveriti da je svaki
vraćeni red available > 0 — moraš proveriti i da si zaista dobio tačno 3 inventory reda.

Za:

check_in  = 2026-10-10
check_out = 2026-10-13
noćenja su:

10.
11.
12.
pa očekujemo:

row count = 3

---

## AND svaki available >= 1

Ako, recimo, inventory red za 11. oktobar uopšte ne postoji, ne sme rezervacija
proći samo zato što preostala dva imaju availability.

Tvoj half-open range je baš dobar:

```text
date >= '2026-10-10'
AND date < '2026-10-13'
```

jer lepo prati booking logiku [check_in, check_out).

I ovo:

```sql
ORDER BY date
```

ima dobru ideju — želimo konzistentan redosled zaključavanja da smanjimo deadlock rizik. Još važnije je da imamo:

```php
UNIQUE(room_id, date)
```

što nam već daje koristan composite index za:

room_id + date range
pa sve transakcije treba da pristupaju tim inventory redovima na konzistentan način.

Ako ijedan od tri dana nema dostupnost:

available = 0
ili neki inventory red nedostaje:

ROLLBACK
i ništa ne ostaje delimično zapisano.

Takođe si lepo rekao:

SET available_rooms = available_rooms - 1

To je bolje nego da aplikacija pročita:

available = 3
pa izračuna 2 i pošalje:

SET available = 2

jer SQL radi promenu nad trenutnim DB state-om:

SET available = available - 1

Što se tiče redosleda INSERT booking / UPDATE inventory: oba mogu biti unutar iste transakcije.
Ako napraviš booking pa update trećeg dana propadne:

---

## INSERT booking ✅

---

## UPDATE day 1 ✅

---

## UPDATE day 2 ✅

---

## UPDATE day 3 ❌

ROLLBACK
rollback briše i booking i prethodna dva decrement-a. Zato je transakcija ključna.

A tvoj PS je takođe odličan. Druga varijanta može biti bez eksplicitnog FOR UPDATE:

---

## UPDATE room_inventory

---

## SET available = available - 1

```sql
WHERE room_id = ?
  AND date >= ?
  AND date < ?
  AND available > 0;
```

pa proveriš:

affected_rows == 3
Ako:

```text
affected_rows = 3
→ sva tri dana uspešno decrementovana
```

```text
affected_rows < 3
→ nešto nije dostupno
→ ROLLBACK
```

To je veoma lep atomic conditional update pristup.

Jedna važna stvar: affected_rows == 3 moraš tumačiti zajedno sa činjenicom da očekuješ baš tri noći.
Za rezervaciju od, recimo, 5 noći očekivao bi 5.

Ovim bih završio MySQL interview checkpoint.

Tvoj nivo kroz ovih 35 pitanja je otprilike 9.5/10. Najjače oblasti su ti transakcije/concurrency,
indeksi, JOIN/EXISTS, constraints, normalizacija i praktičan booking model. Najviše korekcija smo imali na nekoliko preciznih DB termina: covering index, NOT IN + NULL, pojedine EXPLAIN Extra stavke i poneku nijansu oko PK/DATE-TIMESTAMP.

To je dobar znak: više ne grešimo na nivou „ne znaš koncept“,
nego uglavnom na nivou preciznosti formulacije.

Sledeće po našem planu su SQL coding zadaci, jedan po jedan.
Prvi bih krenuo sa kombinacijom JOIN + GROUP BY + HAVING, pa onda NOT EXISTS, indeksiranje/EXPLAIN, i na kraju jedan ozbiljan booking/concurrency zadatak.
