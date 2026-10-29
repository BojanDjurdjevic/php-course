# math

| PHP | Šta radi | Laravel pandan |
| --- | --- | --- |
| `+ - * /` | osnovna matematika | isti PHP |
| `%` | ostatak pri deljenju | isti PHP |
| `**` | stepenovanje | isti PHP |
| `round()` | zaokružuje | uglavnom isti PHP |
| `ceil()` | uvek naviše | isti PHP |
| `floor()` | uvek naniže | isti PHP |
| `abs()` | apsolutna vrednost | isti PHP |
| `min()` | najmanja vrednost | `collect()->min()` za kolekcije |
| `max()` | najveća vrednost | `collect()->max()` |
| `pow()` | stepen | obično `**` |
| `sqrt()` | kvadratni koren | isti PHP |
| `intdiv()` | celobrojno deljenje | isti PHP |
| `fmod()` | modulo za float | isti PHP |
| `number_format()` | formatira broj | `Number::format()` |
| `is_numeric()` | proverava da li je numeric | Laravel `numeric` validation |
| `is_int()` | proverava integer tip | Laravel `integer` validation |
| `is_float()` | proverava float | — |
| `(int)` | cast u integer | `$request->integer()` u nekim situacijama |
| `(float)` | cast u float | — |
| `intval()` | vrednost kao int | uglavnom `(int)` |
| `floatval()` | vrednost kao float | uglavnom `(float)` |
| `random_int()` | secure random integer | PHP i u Laravelu |
| `rand()` | pseudo-random broj | uglavnom ne preferirati |
| `pi()` | π | PHP |
| `deg2rad()` | stepeni → radijani | PHP |
| `rad2deg()` | radijani → stepeni | PHP |
| `log()` | logaritam | PHP |
| `log10()` | log baza 10 | PHP |
| `sin()`, `cos()`, `tan()` | trigonometrija | PHP |

---

## MAPA PAMĆENJA

```text
RAČUNAJ
+ - * / % **
```

```text
ZAOKRUŽI
round()
ceil()
floor()
```

```text
GRANICE
min()
max()
abs()
```

```text
CELOBROJNA MATEMATIKA
intdiv()
%
```

```text
PROVERI TIP / INPUT
is_numeric()
is_int()
is_float()
```

```text
KONVERTUJ
(int)
(float)
```

```text
RANDOM
random_int()
```

```text
PRIKAZ
number_format()
```

---

## LARAVEL

```text
FORMAT
Number::format()
```

```text
NOVAC
Number::currency()
```

```text
PROCENAT
Number::percentage()
```

---

## OGRANIČI RANGE

```php
Number::clamp()
```

---

## HUMAN READABLE

```php
Number::abbreviate()
Number::forHumans()
Number::fileSize()
```

```text
KOLEKCIJE
sum()
avg()
min()
max()
```

---

## Matematika i Novac

Neće uvek vratiti precizan decimalni br!!!

```text
obični brojevi → int / float
```

```text
novac → integer minor units
```

---

## ILI DECIMAL u DB + pažljivo računanje

ILI BCMath / money biblioteka za ozbiljnije slučajeve

---

## OSTALI OPERATORI

| PHP | Šta radi | Laravel pandan / napomena |
| --- | --- | --- |
| `===` | ista vrednost + isti tip | koristi često |
| `!==` | nije ista vrednost ili tip | koristi često |
| `==` | poredi uz type juggling | oprez |
| `!=` | loose inequality | oprez |
| `<=>` | spaceship operator | sorting/comparison |
| `is_null()` | proverava `null` | `blank()`/`filled()` nisu isto |
| `isset()` | postoji i nije `null` | veoma važno |
| `empty()` | proverava „praznu“ vrednost | Laravel `blank()` približno |
| `is_int()` | integer? | validation `integer` |
| `is_float()` | float? | validation `numeric` |
| `is_string()` | string? | validation `string` |
| `is_bool()` | bool? | validation `boolean` |
| `is_array()` | array? | validation `array` |
| `is_object()` | object? | — |
| `is_numeric()` | broj ili numeric string? | validation `numeric` |
| `gettype()` | vraća tip | debugging |
| `get_debug_type()` | precizniji tip | bolji za debugging |
| `(int)` | cast u integer | `$request->integer()` |
| `(float)` | cast u float | — |
| `(string)` | cast u string | — |
| `(bool)` | cast u boolean | `$request->boolean()` |
| `(array)` | cast u array | — |
| `??` | null coalescing | `data_get(..., default)` slična ideja |
| `??=` | dodeli ako je null | veoma korisno |
| `?:` | Elvis operator | koristi fallback za falsy |
| `?->` | nullsafe operator | često u Laravel objektima |
| `match` | strogo grananje po vrednosti | često bolje od `switch` |
| `instanceof` | proverava klasu/interface | veoma važno u OOP |

---

## ZAMKE U POREĐENJU I HVATANJU VREDNOSTI

```text
'5' == 5 → true
```

```text
'5' === 5 → false
```

```text
isset($x) → false ako je null
```

```text
empty(0) → true
```

```text
(bool) 'false' → true
```

```text
'0' → falsy
```

```text
??  → proverava null
```

```text
?: → proverava truthiness
```

---

## LARAVEL PANDAN

```php
$request->boolean()
$request->integer()

$request->has()
$request->filled()
$request->missing()

blank()
filled()

data_get()
```

validation:

```text
integer
numeric
boolean
string
array
```

Eloquent casts:

```text
integer
float
boolean
decimal
array
datetime
```

---

## MAPA PMĆENJA

POREDI VREDNOST + TIP

---

```text
POREDI UZ KONVERZIJU
==
```

```text
PROVERI POSTOJANJE
isset()
```

```text
PROVERI PRAZNINU
empty()
```

```text
PROVERI TIP
is_*()
```

```text
PRETVORI TIP
(int)
(float)
(string)
(bool)
```

FALLBACK AKO JE NULL
??

---

## FALLBACK AKO JE FALSY

?:

```text
SAFE NULL ACCESS
?->
```

```text
PROVERI KLASU
instanceof
```

---

## LARAVEL

```text
HTTP INPUT
$request->boolean()
$request->integer()
```

```text
VALIDACIJA
integer / numeric / boolean / string
```

```text
MODEL TYPES
casts()
```

```php
// https://vite.new/vue
```
