<?php

use App\Session\Session;

require_once __DIR__ . '/../../includes/header.php';

//$session = new Session();

$errors = $session->flashGet('errors', []);

?>
<body class="p-3 bg-gray-800 flex flex-col justify-center items-center w-screen h-screen">

    <form 
        action="/../php-course/index.php?route=register" method="POST"
        class="w-full max-w-md max-h-full min-h-72 p-6 m-3 bg-gray-900 rounded-2xl shadow-lg
        flex flex-col justify-around"    
    >

        <input 
            type="hidden" name="_token"
            value="<?= htmlspecialchars($session->get('csrf_token'), ENT_QUOTES, 'UTF-8') ?>"
        >

        <input 
            type="text" name="name" id="" placeholder="Ime"
            class="w-full p-3 rounded-lg bg-gray-800 text-white
            placeholder-gray-400 border border-gray-700 focus:outline-none 
            focus:ring-2 focus:ring-blue-500"    
        >
        <?php if (isset($errors['name'])): ?>
            <p class="text-red-400 text-sm mt-1">
                <?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <input 
            type="email" name="email" id="" placeholder="Email"
            class="w-full p-3 rounded-lg bg-gray-800 text-white
            placeholder-gray-400 border border-gray-700 focus:outline-none 
            focus:ring-2 focus:ring-blue-500"    
        >
        <?php if (isset($errors['email'])): ?>
            <p class="text-red-400 text-sm mt-1">
                <?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <input 
            type="text" name="password" id="" placeholder="Lozinka"
            class="w-full p-3 rounded-lg bg-gray-800 text-white
            placeholder-gray-400 border border-gray-700 focus:outline-none 
            focus:ring-2 focus:ring-blue-500"    
        >
        <?php if (isset($errors['password'])): ?>
            <p class="text-red-400 text-sm mt-1">
                <?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <input 
            type="text" name="password_confirmation" id="" placeholder="Potvrda Lozinke"
            class="w-full p-3 rounded-lg bg-gray-800 text-white
            placeholder-gray-400 border border-gray-700 focus:outline-none 
            focus:ring-2 focus:ring-blue-500"    
        >
        <?php if (isset($errors['password_confirmation'])): ?>
            <p class="text-red-400 text-sm mt-1">
                <?= htmlspecialchars($errors['password_confirmation'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <input 
            type="text" name="city" id="" placeholder="City"
            class="w-full p-3 rounded-lg bg-gray-800 text-white
            placeholder-gray-400 border border-gray-700 focus:outline-none 
            focus:ring-2 focus:ring-blue-500"    
        >
        <?php if (isset($errors['city'])): ?>
            <p class="text-red-400 text-sm mt-1">
                <?= htmlspecialchars($errors['city'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <button
            class="w-full bg-green-600 hover:bg-green-700 transition-colors text-white font-medium p-3 rounded-lg" 
        >Registruj me</button>

    </form>

</body>
</html>