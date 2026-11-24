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

// The str_replace(find, replace, string, count (optional)) function replaces some characters with some other characters in a string.

/**
 *   find	    Required. Specifies the value to find
 *   replace	Required. Specifies the value to replace the value in find
 *   string	    Required. Specifies the string to be searched
 *   count	    Optional. A variable that counts the number of replacements
 */

/**
 *   If the string to be searched is an array, it returns an array
 *   If the string to be searched is an array, find and replace is performed with every array element
 *   If both find and replace are arrays, and replace has fewer elements than find, an empty string will be used as replace
 *   If find is an array and replace is a string, the replace string will be used for every find value
 */

echo "<b>" . "str_replace()" . "<b>" . "</br>";

$str = "Hello world!";
$myName = "Bojan";

echo str_replace('world', $myName, $str);

$arr = ['blue', 'red', 'pink', 'yellow', 'red'];
$find = 'red';
$replace = 'green';

print_r(str_replace($find, $replace, $arr, $i));
echo "</br>" . "Replacements $i" . "</br>";

echo "</br>" . "</br>";
//-----------------------------------------------------------

// The trim(string) function removes whitespace and other predefined characters from both sides of a string.

/**
 *   string	    R   equired. Specifies the string to check
 *   charlist	    Optional. Specifies which characters to remove from the string. If omitted, all of the following characters are removed:
                    
                    *   "\0" - NULL
                    *   "\t" - tab
                    *   "\n" - new line
                    *   "\x0B" - vertical tab
                    *   "\r" - carriage return
                    *   " " - ordinary white space
 */

echo "<b>" . "trim()" . "<b>" . "</br>";

$str = " Hello World! ";
echo "Without trim: " . $str;
echo "<br>";
echo "With trim: " . trim($str);

/**
 *   trim()
 *   ltrim() samo levo
 *   rtrim() samo desno
 * 
 * ----- LARAVEL ------------
 * Str::trim($name);
 * Str::squish('  Hello     World  '); -> Hello World
 */

echo "</br>" . "</br>";
//-----------------------------------------------------------

// The explode() function breaks a string into an array.

// params kao i u str_contains

echo "<b>" . "explode()" . "<b>" . "</br>";

/**
 *   separator	Required.   Specifies where to break the string
 *   string	    Required.   The string to split
 *   limit	    Optional.   Specifies the number of array elements to return.
 * 
 *   Possible values:

 *   Greater than 0 - Returns an array with a maximum of limit element(s)
 *   Less than 0 - Returns an array except for the last -limit elements()
 *   0 - Returns an array with one element
 */

$skils = "PHP, Laravel, MySql, Alpine.js, Livewire, Vue.js, Nuxt, JavaScript, TypeScript";

$skills_array = explode(',', $skils);

print_r($skills_array);

echo "</br>" . "</br>";
//-----------------------------------------------------------

// implode() -- The oposit of explode:

$newString = implode(",", $skills_array);

echo $newString;

echo "</br>" . "</br>";
//-----------------------------------------------------------

// strtolower() / strtoupper() - Change the letters of string to lower / upper cases.

$mystring = 'PHP Laravel Developer';

echo strtolower($mystring) . "</br>";

echo strtoupper($mystring) . "</br>";

echo "</br>" . "</br>";
//-----------------------------------------------------------

// ucfirst(string) - First letter to upper

echo "<b>" . "ucfirst()" . "<b>" . "</br>";

$name = 'bojan';

echo ucfirst($name);
// Bojan

echo "</br>" . "</br>";
//-----------------------------------------------------------

// ucfirst(string) - First letter of all words to upper

echo "<b>" . "ucfirst()" . "<b>" . "</br>";

$name = 'bojan djurdjevic';

echo ucwords($name);
// Bojan Djurdjevic

echo "</br>" . "</br>";
//-----------------------------------------------------------

// sprintf() - The sprintf() function writes a formatted string to a variable.
/*
The arg1, arg2, ++ parameters will be inserted at percent (%) signs in the main string. This function works "step-by-step". 
At the first % sign, arg1 is inserted, at the second % sign, arg2 is inserted, etc.

*   %s → string
    %d → integer
    %f → float
*/

echo "<b>" . "sprintf()" . "<b>" . "</br>";

$user = 'Bojan';
$count = 3;

$message = sprintf(
    "User %s has %d bookings.",
    $user,
    $count
);

$price = 149.99;

$display = sprintf(
    'Price: %.2f EUR',
    $price
);

echo $message . " " . " " . $display;
// Bojan

echo "</br>" . "</br>";
//-----------------------------------------------------------

// The str_repeat(string, repeates) function repeats a string a specified number of times.

echo "<b>" . "str_repeat()" . "<b>" . "</br>";

echo str_repeat('*', 5);
// *****

echo "</br>" . "</br>";
//-----------------------------------------------------------

// The str_pad(string, length, pad_string (optional), pad_type (optional)) function pads a string to a new length.

echo "<b>" . "str_pad()" . "<b>" . "</br>";

$id = '42';

echo str_pad(
    $id,
    5,
    '0',
    STR_PAD_LEFT
);

// Laravel -> Str::padLeft('42', 5, '0'); :)

echo "</br>" . "</br>";
//-----------------------------------------------------------

// The htmlspecialchars() function converts some predefined characters to HTML entities.

echo "<b>" . "htmlspecialchars()" . "<b>" . "</br>";

$input = "This is some <b>bolded</b> text and this is a <script>JS malware<script>";

echo htmlspecialchars(
    $input,
    ENT_QUOTES,
    'UTF-8'
);

echo "</br>" . "</br>";
//-----------------------------------------------------------

// The preg_match checks our string with requested regex

echo "<b>" . "preg_match()" . "<b>" . "</br>";

$email = 'test@example.com';

if (preg_match('/@example\.com$/', $email)) {
    echo 'Example email';
}

echo "</br>" . "</br>";
//-----------------------------------------------------------

// The preg_replace checks our string with requested regex and replaces the unnecessary characters

echo "<b>" . "preg_replace()" . "<b>" . "</br>";

$phone = '+381 (64) 123-456';

$phone = preg_replace(
    '/\D/',
    '',
    $phone
);

echo $phone;

echo "</br>" . "</br>";
//-----------------------------------------------------------

?>