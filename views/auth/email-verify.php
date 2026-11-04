<?php

require_once __DIR__ . '/../../includes/header.php';

// $token je već proveren u GET ruti.

?>

<body class="min-h-screen bg-gray-800 flex items-center justify-center p-4">

    <main class="w-full max-w-md bg-gray-900 border border-gray-700
                 rounded-2xl shadow-2xl p-8 text-center">

        <div class="w-16 h-16 mx-auto mb-6 rounded-full
                    bg-blue-500/10 flex items-center justify-center">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-9 h-9 text-blue-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.5"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 8l9 6 9-6M5 5h14a2 2 0 012 2v10
                       a2 2 0 01-2 2H5a2 2 0 01-2-2V7
                       a2 2 0 012-2z"
                />
            </svg>

        </div>

        <h1 class="text-2xl font-bold text-white mb-3">
            Verify your email
        </h1>

        <p class="text-gray-400 text-sm leading-relaxed mb-8">
            Your email address is almost verified.
            Click the button below to confirm your address
            and activate your account.
        </p>

        <form action="index.php?route=email-verify" method="POST">

            <input
                type="hidden"
                name="_token"
                value="<?= htmlspecialchars(
                    $session->get('csrf_token'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <input
                type="hidden"
                name="email_verify_token"
                value="<?= htmlspecialchars(
                    $token,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <button
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700
                       text-white font-semibold py-3 px-5
                       rounded-xl transition-colors
                       focus:outline-none focus:ring-2
                       focus:ring-blue-400"
            >
                Confirm Email Address
            </button>

        </form>

        <p class="text-gray-500 text-xs mt-6">
            This verification link can only be used once.
        </p>

        <a
            href="index.php?route=login"
            class="inline-block mt-6 text-sm text-blue-400
                   hover:text-blue-300 transition-colors"
        >
            Back to Login
        </a>

    </main>

</body>
</html>