<?php

include_once __DIR__ . '/../../includes/header.php';

?>

<body class="bg-gray-800 flex justify-center items-center">
    <div 
        x-data="{file: false}"
        class="m-8 p-9 w-screen h-auto"
    >
        <form 
            action="upload.php" method="POST" enctype="multipart/form-data"
            class="flex flex-column justify-between items-center"
        >

            <input type="text" name="name" id="" placeholder="Ime" @change="file = true">

            <input type="file" name="photo" id="">

            <button type="submit" class="bg-green-600 text-white p-3 m-1" >Pošalji</button>

            

        </form>

        <p 
            x-show="file"
            class="text-red-700"
        >Šalje se...</p>
    </div>  
</body>