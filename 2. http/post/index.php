<?php

require_once __DIR__ . '/../../includes/header.php';

?>
<body class="p-3 bg-gray-800 flex flex-col justify-center items-center w-screen h-screen">

    <form 
        action="registracija.php" method="post"
        class="w-full max-w-md max-h-full min-h-72 p-6 m-3 bg-gray-900 rounded-2xl shadow-lg
        flex flex-col justify-around"    
    >

        <input 
            type="text" name="ime" id="" placeholder="Ime"
            class="w-full p-3 rounded-lg bg-gray-800 text-white
            placeholder-gray-400 border border-gray-700 focus:outline-none 
            focus:ring-2 focus:ring-blue-500"    
        >

        <input 
            type="text" name="lozinka" id="" placeholder="Lozinka"
            class="w-full p-3 rounded-lg bg-gray-800 text-white
            placeholder-gray-400 border border-gray-700 focus:outline-none 
            focus:ring-2 focus:ring-blue-500"    
        >

        <button
            class="w-full bg-green-600 hover:bg-green-700 transition-colors text-white font-medium p-3 rounded-lg" 
        >Registruj me</button>

    </form>

</body>
</html>