<?php

require_once __DIR__ . '/../includes/header.php';

?>

<body class="min-h-screen bg-gray-800 text-white flex justify-center items-center p-4">

    <main class="w-full max-w-xl bg-gray-900 p-10 rounded-2xl shadow-xl text-center">

        <div class="text-5xl mb-6">
            👋
        </div>

        <h1 class="text-3xl font-bold mb-4">
            Welcome,
            <span class="text-green-400">
                <?= htmlspecialchars($user->name(), ENT_QUOTES, 'UTF-8') ?>!
            </span>
        </h1>

        <p class="text-gray-400">
            You have successfully logged in.
        </p>

        <form action="?route=logout" method="POST">
            <input 
                type="hidden" name="_token"
                value="<?= htmlspecialchars($session->get('csrf_token'), ENT_QUOTES, 'UTF-8') ?>"
            >

            <button type="submit">Logout</button>
        </form>

    </main>

</body>
</html>