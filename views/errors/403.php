<?php

require_once __DIR__ . '/../../includes/header.php';

$page = $_SESSION['page'];
unset($_SESSION['page']);

$page = in_array($page ?? '', ['loginpage', 'register', 'dashboard'], true) ? $page : 'loginpage';

//var_dump($page);

?>

<body class="min-h-screen bg-gray-800 text-white flex justify-center items-center">

    <div class="bg-gray-900 p-8 rounded-2xl shadow-lg text-center">

        <h1 class="text-4xl text-red-400 font-bold mb-4">
            403 — Forbidden
        </h1>

        <p class="text-gray-300 mb-6">
            Neispravan ili istekao CSRF token.
        </p>

        <a
            href="?route=<?= htmlspecialchars($page, ENT_QUOTES, 'UTF-8') ?>"
            class="bg-blue-600 hover:bg-blue-700 px-5 py-3 rounded-lg"
        >
            Back to <?= htmlspecialchars($page, ENT_QUOTES, 'UTF-8') ?>
        </a>

    </div>

</body>
</html>