<?php

include_once __DIR__ . '/../../includes/header.php';

?>

<body class="bg-gray-800 min-h-screen flex justify-center items-center">
    <div 
        x-data="{file: false}"
        class="w-full max-w-md m-8 p-9 bg-gray-900 rounded-2xl shadow-lg"
    >
        <form 
            action="upload.php" method="POST" enctype="multipart/form-data"
            class="flex flex-col gap-4"
        >

            <input 
                type="text" name="name" id="" placeholder="Ime" 
                class="w-full p-3 rounded-lg bg-gray-800 text-white
                placeholder-gray-400 border border-gray-700 focus:outline-none 
                focus:ring-2 focus:ring-blue-500"
                @change="file = true"
            >

            <input 
                type="file" name="photo" id=""

                class="w-full text-sm text-gray-300 
                file:mr-4 file:py-2 file:px-4 file:rounded-lg 
                file:border-0 file:bg-blue-600 file:text-white 
                file:cursor-pointer hover:file:bg-blue-700"
            >

            <button 
                type="submit" 
                class="w-full bg-green-600 hover:bg-green-700 transition-colors text-white font-medium p-3 rounded-lg" 
            >Pošalji</button>

            

        </form>

        <p 
            x-show="file"
            class="text-red-400 text-sm mt-4 text-center"
        >Šalje se...</p>
    </div>  
</body>