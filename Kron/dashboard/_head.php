<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard - KRON' ?></title>
    <link rel="icon" type="image/png" href="../asset/kron.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            bg: '#000000',
                            surface: '#0A0A0A',
                            border: '#1F1F1F',
                            text: '#E5E5E5',
                            muted: '#A3A3A3'
                        },
                        brand: {
                            500: '#3B82F6',
                            600: '#2563EB',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            @apply bg-gray-50 text-gray-900 transition-colors duration-200; 
        }
        .dark body {
            @apply bg-dark-bg text-dark-text;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { @apply bg-transparent; }
        ::-webkit-scrollbar-thumb { @apply bg-gray-300 rounded-full; }
        .dark ::-webkit-scrollbar-thumb { @apply bg-gray-800; }
        ::-webkit-scrollbar-thumb:hover { @apply bg-gray-400; }
        .dark ::-webkit-scrollbar-thumb:hover { @apply bg-gray-700; }
    </style>
</head>
