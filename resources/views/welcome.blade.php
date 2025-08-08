<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mr. Market NEPSE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Tailwind CSS -->
    @vite('resources/css/app.css') {{-- Or link to your compiled Tailwind CSS file --}}

    <style>
        /* Background gradient animation */
        body {
            background: linear-gradient(-45deg, #1e293b, #0f172a, #1e566c, #00CED1);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }

        @keyframes gradientBG {
            0% {background-position: 0% 50%;}
            50% {background-position: 100% 50%;}
            100% {background-position: 0% 50%;}
        }

        .fade-in {
            opacity: 0;
            animation: fadeIn 2s ease-in-out forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }
    </style>
</head>
<body class="text-white min-h-screen flex items-center justify-center font-sans">

    <div class="text-center fade-in">
        <h1 class="text-5xl font-extrabold mb-6 tracking-tight">
            Welcome to <span class="text-yellow-400">Mr. Market</span> NEPSE
        </h1>
        <p class="text-lg mb-10 text-gray-200 max-w-xl mx-auto">
            A smarter way to manage and track your NEPSE investments with confidence and insight.
        </p>

        <a href="{{ route('filament.admin.auth.login') }}"
           class="inline-block px-8 py-4 bg-yellow-400 text-black font-semibold rounded-lg shadow-md hover:shadow-xl transition-all duration-300 hover:bg-yellow-300">
            Login to Dashboard.
        </a>
    </div>

</body>
</html>
