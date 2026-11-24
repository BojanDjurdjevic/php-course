<?php

session_start();

include_once __DIR__ . '/../../includes/header.php';

$errors = $_SESSION['errors'] ?? null;
$flash = $_SESSION['flash'] ?? null;
$old = $_SESSION['old'] ?? [];

unset(
    $_SESSION['errors'],
    $_SESSION['flash'],
    $_SESSION['old']
);

?>
<body class="bg-gray-800 p-3 min-h-screen flex flex-col justify-center items-center gap-4">
    <div>
        <p class="text-red-500">
            <?= htmlspecialchars($errors ?? '', ENT_QUOTES, 'UTF-8')?>
        </p>
        <p class="text-green-500">
            <?= htmlspecialchars($flash ?? '', ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>
    <div class="bg-gray-800 w-full max-w-sm flex justify-center items-center">
        <form method="POST" action="store.php"
            class="bg-gray-900 w-full max-w-sm rounded-2xl shadow-lg flex flex-col gap-4"
        >

            <input type="text" name="name" placeholder="Name"
                class="w-full p-3 rounded-lg text-gray-900 text-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >

            <input type="email" name="email" placeholder="email@test.com"
                class="w-full p-3 rounded-lg text-gray-900 text-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >

            <button type="submit"
                class="w-full p-3 bg-green-600 hover:bg-green-700 disabled:bg-gray-500 disabled:cursor-not-allowed 
            transition-colors text-white rounded-lg font-medium"
            >Save</button>

        </form>
    </div>
    
</body>

</html>