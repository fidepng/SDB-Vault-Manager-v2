<!-- Perbaikan app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SDB Vault Manager') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Sembunyikan tombol 'x' bawaan browser pada input search */
        input[type="search"]::-webkit-search-cancel-button {
            -webkit-appearance: none;
            display: none;
        }

        input[type="search"]::-ms-clear {
            display: none;
            width: 0;
            height: 0;
        }
    </style>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen flex flex-col">
        @include('layouts.navigation')

        <!-- Page Content -->
        <main class="flex-1 pb-16 h-full">
            {{ $slot }}
        </main>

        <!-- Footer dengan posisi yang benar -->
        {{-- <footer class="bg-white shadow-inner border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
                © {{ date('Y') }} SDB Vault Manager. All rights reserved.
            </div>
        </footer> --}}
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed top-4 right-4 z-50"></div>

    <script>
        // Global notification function tetap sama
        window.showNotification = function(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `mb-4 px-6 py-4 rounded-lg shadow-lg text-white transform transition-all duration-300 ease-in-out translate-x-full ${
                    type === 'success' ? 'bg-green-500' : 
                    type === 'error' ? 'bg-red-500' : 
                    type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
                }`;
            toast.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span>${message}</span>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                `;

            document.getElementById('toast-container').appendChild(toast);

            // Animate in
            setTimeout(() => toast.classList.remove('translate-x-full'), 100);

            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        };
    </script>
</body>

</html>
