<?php

// The mb_strlen(string) function returns the length of a string. MB_ -> radi sa UTF-8
echo "<b>" . "strlen()" . "<b>" . "</br>";

$name = 'Bojan';

echo mb_strlen($name);

echo "</br>" . "</br>";
//-----------------------------------------------------------

// The substr(string, start, length (optional)) function returns a part of a string. UTF-8 VERSION: mb_substr()

/**
 *   start	-> Required. 
 *       Specifies where to start in the string
 *       A positive number - Start at a specified position in the string
 *       A negative number - Start at a specified position from the end of the string
 *       0 - Start at the first character in string

 *   length	-> Optional. 
 *       Specifies the length of the returned string. Default is to the end of the string.
 *       A positive number - The length to be returned from the start parameter
 *       Negative number - The length to be returned from the end of the string
 *       If the length parameter is 0, NULL, or FALSE - it return an empty string
 * 
 *      If length param is omited the string will be taken until the end
 */

echo "<b>" . "substr()" . "<b>" . "</br>";

$name = 'BojanDjurdjevic';

echo mb_substr($name, 5);    // Djurdjevic
echo mb_substr($name, -10);  // Djurdjevic
echo mb_substr($name, 0, 5); // Bojan

echo "</br>" . "</br>";
//-----------------------------------------------------------

// The strpos(string, find, start) function finds the position of the first occurrence of a string inside another string. Is case-sensitive.

/**
 *   string	-> Required. 
 *      Specifies the string to search
 * 
 *   find  -> Required. 
 *       Specifies the string to find

 *   start	-> Optional. 
 *       Specifies where to begin the search. If start is a negative number, it counts from the end of the string.
 */

echo "<b>" . "strpos()" . "<b>" . "</br>";

$name = 'Bojan';

echo strpos($name, 'j'); // 2
echo strpos($name, 'J'); // Ne vraća ništa jer je case-sensitive

// ZAMKA if(strpos($name, 'B'))... -> B je ovde na 0 a 0 je falsy, zato bolje ili u varijablu ili strpos($name, 'B') !== false

echo "</br>" . "</br>";
//-----------------------------------------------------------

// The str_contains(string, substring (what we are searching for)) function checks if a string contains a specific substring.

/**
 *   string	     |   Required. Specifies the main string to be searched
 *   substring	 |   Required. Specifies the substring to search for
 */

echo "<b>" . "str_contains()" . "<b>" . "</br>";

$name = 'Bojan Djurdjevic';

echo str_contains($name, 'jan'); // true (1)

echo "</br>" . "</br>";
//-----------------------------------------------------------

// The str_starts_with(string, substr) / str_ends_with(string, substr) function checks if a string starts / ends with a specific substring.

// params kao i u str_contains

echo "<b>" . "str_starts_with() / _ends_with" . "<b>" . "</br>";

if (str_starts_with($name, 'Bo')) { // bool -> true
    echo 'Počinje sa Bo'; 
} elseif (str_ends_with($name, 'ić')) {
    echo 'Završava sa ić';
} else echo 'Nešto fali.';

echo "</br>" . "</br>";
//-----------------------------------------------------------

?>