<?php

// date(format, timestamp(optional)) -> The date() function formats a local date and time, and returns the formatted date string.

$now = date('d.m.Y H:i:s');
echo "Trenutno - $now";

echo "</br>";
echo "</br>";
//-------------------------------

// The time() function returns the current time in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).

$t = time();
echo "UNIX = $t . </br>";
echo "Datum: " . date("Y-m-d", $t);

echo "</br>" . "</br>";
//-------------------------------

/**
 *   The strtotime(datetime_string, basetimestamp) function parses an English textual datetime into a Unix timestamp (the number of seconds since January 1 1970 00:00:00 GMT).

 *   Note: If the year is specified in a two-digit format, values between 0-69 are mapped to 2000-2069 and values between 70-100 are mapped to 1970-2000.

 *   Note: Be aware of dates in the m/d/y or d-m-y formats; if the separator is a slash (/), then the American m/d/y is assumed. 
 *   If the separator is a dash (-) or a dot (.), then the European d-m-y format is assumed. 
 *   To avoid potential errors, you should YYYY-MM-DD dates or date_create_from_format() when possible.
 */
echo "<b>STRTOTIME()</b> " . "</br>" . "</br>";

echo strtotime("now") . "</br>";
echo strtotime("3 October 2015") . "</br>";
echo strtotime("+5 hours") . "</br>";
echo "7 dana od danas: " . strtotime("+7 days") . "</br>";
echo "1 sedmica, 3 dana, 7 sati i 5 sekundi " . date('d.m.Y H:i:s', strtotime("+1 week 3 days 7 hours 5 seconds")) . "</br>";
echo "Sledeći Ponedeljak: " . date('d.m.Y', strtotime("next Monday")). "</br>";
echo "Prošla Nedelja: " . date('d.m.Y', strtotime("last Sunday")) . "</br>";

echo "</br>" . "</br>";
//-------------------------------

echo "<b>mktime()</b> " . "</br>" . "</br>";

// The mktime(hour, minute, second, month, day, year -> all optional) function returns the Unix timestamp for a date.

echo "8. Decembar 1986 je bio u: ". date("l", mktime(0,0,0,12,8,1986));

echo "</br>" . "</br>";
//-------------------------------

echo "<b>checkdate()</b> " . "</br>" . "</br>";

// The checkdate(month, day, year -> all required) function is used to validate a Gregorian date.

var_dump(checkdate(12, 8, 1986));

echo "</br>" . "</br>";
//-------------------------------

// The getdate(timestamp - optional) function returns date/time information of a timestamp or the current local date/time.

echo "<b>getdate()</b></br>";
print_r(getdate());

echo "</br>" . "</br>";
//-------------------------------

echo "<b>gmdate()</b></br>";

// The gmdate(format, timestamp (optional)) function formats a GMT/UTC date and time, and returns the formatted date string.

// Prints the day
echo gmdate("l") . "<br>";

// Prints the day, date, month, year, time, AM or PM
echo gmdate("l jS \of F Y h:i:s A");

echo "</br>" . "</br>";
//-------------------------------

// The microtime() function returns the current Unix timestamp with microseconds.

/**
 * 	Returns the string "microsec sec" by default, 
 * where sec is the number of seconds since the Unix Epoch (0:00:00 January 1, 1970 GMT), 
 * and microsec is the microseconds part. 
 * If the return_float parameter is set to TRUE, 
 * it returns a float representing the current time in seconds since the Unix epoch accurate to the nearest microsecond
 */
echo "<b>microtime()</b></br>";

echo microtime();
echo "</br>" . "</br>";
//-------------------------------

// hrtime()

echo "<b>hrtime()</b></br>";

echo hrtime(true);

echo "</br>" . "</br>";
//-------------------------------

//The date_default_timezone_set(timezone) function sets the default timezone used by all date/time functions in the script.
echo "<b>date_default_timezone_set()</b></br>";

date_default_timezone_set("Europe/Belgrade");
echo date_default_timezone_get() . "</br>";

echo "</br>" . "</br>";
//-------------------------------

//The timezone_identifiers_list(what, country - both optional) returns an indexed array containing all timezone identifiers.

/**
 *   1 = AFRICA
 *   2 = AMERICA
 *   4 = ANTARCTICA
 *   8 = ARCTIC
 *   16 = ASIA
 *   32 = ATLANTIC
 *   64 = AUSTRALIA
 *   128 = EUROPE
 *   256 = INDIAN
 *   512 = PACIFIC
 *   1024 = UTC
 *   2047 = ALL
 *   4095 = ALL_WITH_BC
 *   4096 = PER_COUNTRY
 */

echo "<b>date_default_timezone_set()</b></br>";

print_r(timezone_identifiers_list(128));
echo "</br>";

echo "</br>" . "</br>";
//-------------------------------

// The date_parse(date) function returns an associative array with detailed information about a specified date.

echo "<b>date_parse()</b></br>";

print_r(date_parse("2013-05-01 12:30:45.5"));

echo "</br>" . "</br>";
//-------------------------------

// ------------- DATE AS OBJECT ------------------- DATE AS OBJECT ------------------------ DATE AS OBJECT -------------- DATE AS OBJECT ----------------- DATE AS OBJECT -------------------- DATE AS OBJECT ------------------------------

// DateTime je mutable i njegove metode mogu promeniti postojeći objekat

echo "new DateTime() class" . "</br>"; 

$date = new DateTime();

echo $date->format('Y-m-d H:i:s');
echo "</br>" . "</br>";

$date->modify("+1 week");
echo $date->format('Y-m-d H:i:s');

echo "</br>" . "</br>";
//-------------------------------

// DateTimeInmutable() radi slično ali ne menja objekat

$date = new DateTimeImmutable('2026-09-19');

$newDate = $date->modify('+1 month');

echo $date->format('Y-m-d') . "</br>";
// 2026-09-19

echo $newDate->format('Y-m-d');
// 2026-10-19

echo "</br>" . "</br>";

/**
 *  DateTime
 *   modify() menja postojeći objekat
 *  DateTimeImmutable
 *  modify() vraća novi objekat 
 */
//------------------------------

// setDate()

echo "<b>setDate()<b> </br>";

$date = new DateTime();

$date->setDate(2030, 3, 21);

echo $date->format("d.m.Y");

echo "</br>" . "</br>";
//-------------------------------

// setTime() - modifikuje vreme

$date = new DateTime('2026-09-19');

$date->setTime(14, 30); // h, m, mogu se dodati i s

echo $date->format('Y-m-d H:i:s');
// 2026-09-19 14:30:00

echo "</br>" . "</br>";
//-------------------------------

// setTimezone()

echo "<b>setTimezone()<b> </br>";

$date = new DateTime(
    "today", // zašto postavlja na 00 ?
    new DateTimeZone("Europe/Belgrade")
);

$date->setTimezone(
    new DateTimeZone("America/New_York")
); 

echo $date->format("d.m.Y H:i:s");

echo "</br>" . "</br>";
//-------------------------------

// get / setTimestamp()

echo "<b>get / setTimestamp()<b> </br>";

$date = new DateTime('2026-09-19 12:00:00');

$timestamp = $date->getTimestamp();

echo $timestamp . "</br>" . "</br>";

$date = new DateTime();

$date->setTimestamp(1789812000);

echo $date->format('Y-m-d H:i:s');

echo "</br>" . "</br>";
//-------------------------------------------------- VAŽNO!!! Računa razlkiku između 2 datuma -------------------------------------------------

// diff()

echo "<b>diff()<b> </br>";

$start = new DateTime('2020-01-01');

$end = new DateTime('2026-09-19');

$diff = $start->diff($end);

echo $diff->y;

/**
 *   echo $diff->y; // godine
 *   echo $diff->m; // meseci
 *   echo $diff->d; // dani
 */

echo "</br>" . "</br>";

echo "<b>Razlika u godinama (sa datumima) - rođendan->danas<b> </br>";

$birth = new DateTime('1986-12-08');
$today = new DateTime('today');

$age = $birth->diff($today)->y;

echo $age;
// 40

echo "</br>" . "</br>";
//--------------------------------------------------

// add() -> dodaje date interval. sub() oduzima interval

echo "<b>DateTime::add()<b> </br>";

$start = new DateTime('today');

$interval = new DateInterval('P5D');

$date->add($interval);

echo $date->format('Y-m-d');

/**
 *   P5D -> 5 days
 *   P1M -> 1 month
 *   P1Y -> 1 year
 *   P2W -> 2 weeks    
 *   PT2H    2 hours
 *   PT30M   30 min
 *   PT45S   45 sec
 */

echo "</br>" . "</br>";

echo "<b>sub()<b> </br>";

$interval = new DateInterval('P10D');

$date->sub($interval);

echo $date->format('Y-m-d');


echo "</br>" . "</br>";
//--------------------------------------------------

/**
 *   DateInterval::format()
 * 
  *  new DateInterval('P2DT3H'); -> 2 days & 3 hours

  *  Ne mešati sa DateInterval (koristi Y-m-d)
  *  DateInterval::format() koristi:

  *  %y → godine
  *  %m → meseci
  *  %d → dani
  *  %h → sati
  *  %i → minuti
  *  %s → sekunde

  * !!! Formatira rezultat intervala
 */

 $start = new DateTime('2020-03-10');
 $end = new DateTime('2026-09-19');

 $diff = $start->diff($end);

 echo $diff->format('%y godina, %m meseci, %d dana');

 echo "</br>" . "</br>";
//--------------------------------------------------

// new DatePeriod() -> ODLIČAN ZA GENERISANJE NIZA DATUMA

echo "<b>" . "DatePeriod " . "</b>" . "</br></br>";

$start = new DateTime('tomorrow');
$end = new DateTime('+8 days');

$interval = new DateInterval('P1D');

$period = new DatePeriod($start, $interval, $end); // 3 params: pčetak, koji interval, kraj

foreach ($period as $date) {
    echo $date->format('Y-m-d') . "</br>";
}

 echo "</br>" . "</br>";
//--------------------------------------------------

// new DateTime::createFromFormat() -> Formatiranje datuma (korisno kod user unosa, i pravilan unos u bazu)

echo "<b>" . "DateTime::createFromFortmat() " . "</b>" . "</br></br>";

$input = '19.09.2026 14:30';

$date = DateTime::createFromFormat(
    'd.m.Y H:i',
    $input
);

echo $date->format('Y-m-d H:i:s');
// 2026-09-19 14:30:00

 echo "</br>" . "</br>";
//--------------------------------------------------

// new DateTime::getLastErrors() -> Korisno da se proveri datum, jer PHP ponekad može da normalizuje nevalidan datum

echo "<b>" . "DateTime::getLastErrors() " . "</b>" . "</br></br>";

$date = DateTime::createFromFormat(
    'd/m/Y',
    '32/09/2026'
);

$errors = DateTime::getLastErrors();

print_r($errors);
echo "</br>";

if ($errors !== false && $errors['warning_count'] > 0) {
    echo 'Datum nije validan.';
}

echo "</br>" . "</br>";
//--------------------------------------------------

// Bolji primer lastErrors:

echo "<b>" . "DateTime::getLastErrors() - još jedan primer " . "</b>" . "</br></br>";

$input = '19/09/2026';

$date = DateTime::createFromFormat('d/m/Y', $input);

$errors = DateTime::getLastErrors();

if (
    $date === false ||
    ($errors !== false &&
        ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
) {
    echo 'Neispravan datum.';
} else {
    echo $date->format('Y-m-d');
}

echo "</br>" . "</br>";
//--------------------------------------------------

?>