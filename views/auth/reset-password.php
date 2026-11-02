<?php

require_once __DIR__ . '/../../includes/header.php';

$errors = $session->flashGet('errors', []);

?>
<body class="p-3 bg-gray-800 flex flex-col justify-center items-center w-screen h-screen">

    <h2 class="text-center text-white">Reset password</h2>

    <?php if (isset($errors['reset'])): ?>
        <p class="text-red-400 text-sm text-center">
            <?= htmlspecialchars($errors['reset'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form 
        action="/../php-course/index.php?route=reset-password" method="POST"
        class="w-full max-w-md max-h-full min-h-72 p-6 m-3 bg-gray-900 rounded-2xl shadow-lg
        flex flex-col justify-around"    
    >

        <input 
            type="hidden" name="_token"
            value="<?= htmlspecialchars($session->get('csrf_token'), ENT_QUOTES, 'UTF-8') ?>"
        >

        <input 
            type="hidden" name="token"
            value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
        >

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
            type="text" name="password" id="password" placeholder="Password" autocomplete="current-password"
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
            type="text" name="password_confirmation" id="password_confirmation" placeholder="Password Confirmation" autocomplete="current-password"
            class="w-full p-3 rounded-lg bg-gray-800 text-white
            placeholder-gray-400 border border-gray-700 focus:outline-none 
            focus:ring-2 focus:ring-blue-500"    
        >
        <?php if (isset($errors['password_confirmation'])): ?>
            <p class="text-red-400 text-sm mt-1">
                <?= htmlspecialchars($errors['password_confirmation'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        

        <button
            class="w-full bg-green-600 hover:bg-green-700 transition-colors text-white font-medium p-3 rounded-lg" 
        >Submit</button>

    </form>

</body>
</html>