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

?>