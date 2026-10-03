<?php

session_start();

include_once __DIR__ . '/../../includes/header.php';

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$message = $_SESSION['success'] ?? '';

unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['success']);

?>

<body 
    class="bg-gray-900 p-3 min-h-screen flex justify-center items center"
>

    <div
        class="w-full max-w-md p-8 bg-gray-900 rounded-2xl shadow-lg"
    >
        <form action="store.php" method="POST"
            class="flex flex-col gap-4"
        >

            <input type="text" name="name" id="" placeholder="Name"
                class="w-full p-3 rounded-lg bg-gray-800 text-white placeholder-gray-400 border-gray-700
                focus:outline-none focus:ring-2 focus:ring-blue-500"
                value="<?=htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8');?>"
            >

            <?php if(isset($errors['name'])): ?>
            <p class="text-red-500">
                <?= 
                    htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8');
                ?>
            </p>
            <?php endif; ?>

            <input type="email" name="email" id="" placeholder="emailexample.com"
                class="w-full p-3 rounded-lg bg-gray-800 text-white placeholder-gray-400 border-gray-700
                focus:outline-none focus:ring-2 focus:ring-blue-500"
                value="<?=htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8');?>"
            >

            <?php if(isset($errors['email'])): ?>
            <p class="text-red-500">
                <?= 
                    htmlspecialchars($errors['email'] ?? '', ENT_QUOTES, 'UTF-8');
                ?>
            </p>
            <?php endif; ?>

            <input type="text" name="age" id="" placeholder="Age"
                class="w-full p-3 rounded-lg bg-gray-800 text-white placeholder-gray-400 border-gray-700
                focus:outline-none focus:ring-2 focus:ring-blue-500"
                value="<?=htmlspecialchars($old['age'] ?? '', ENT_QUOTES, 'UTF-8');?>"
            >

            <?php if(isset($errors['age'])): ?>
            <p class="text-red-500">
                <?= 
                    htmlspecialchars($errors['age'] ?? '', ENT_QUOTES, 'UTF-8');
                ?>
            </p>
            <?php endif; ?>

            <button
                class="w-full bg-green-600 hover:bg-green-700 transition-colors text-white font-medium p-3 rounded-lg"
            >
                Pošalji
            </button>

        </form>
        <?php if($message !== ''): ?>
        <div class="p-8 mt-8">
            <p class="text-green-600"> <?= $message ?> </p>
        </div>
        <?php endif; ?>
    </div>

    
    
</body>

</html>