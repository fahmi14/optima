<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Masuk') - OPTIMA</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        surface: {
                            50: '#F8F9FA',
                            100: '#F1F3F5',
                            200: '#E9ECEF',
                            300: '#DEE2E6',
                        },
                        sage: {
                            50: '#f4f7f5',
                            100: '#e5ece7',
                            200: '#cbdad0',
                            500: '#52796f',
                            600: '#354f52',
                            700: '#2f3e46',
                        },
                        clay: {
                            50: '#faf6f2',
                            100: '#f3ece3',
                            200: '#e4d5c4',
                            500: '#a3704c',
                            700: '#6b4423',
                        },
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background-color: #F8F9FA;
            color: #2D3748;
            -webkit-font-smoothing: antialiased;
        }
    </style>
</head>
<body class="min-h-full flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 bg-[#F8F9FA] text-slate-700">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Brand Header -->
        <div class="flex flex-col items-center text-center mb-6">
            <div class="w-11 h-11 rounded-xl bg-slate-800 text-white flex items-center justify-center shadow-sm mb-3">
                <i data-lucide="layers" class="w-5 h-5"></i>
            </div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">OPTIMA</h1>
            <p class="text-[11px] text-slate-400 font-medium tracking-wider uppercase mt-0.5">Sistem Penugasan BPS</p>
        </div>

        <!-- Auth Card -->
        <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs p-7 sm:p-8 space-y-5">
            <!-- Status & Flash Messages -->
            @if(session('status'))
                <div class="flex items-start gap-2.5 px-4 py-3 text-xs bg-[#f2f7f4] border border-[#cbe1d4] text-[#284838] rounded-xl">
                    <i data-lucide="check-circle" class="w-4 h-4 text-[#3d6f54] shrink-0 mt-0.5"></i>
                    <span class="font-medium leading-relaxed">{{ session('status') }}</span>
                </div>
            @endif

            @if(session('success'))
                <div class="flex items-start gap-2.5 px-4 py-3 text-xs bg-[#f2f7f4] border border-[#cbe1d4] text-[#284838] rounded-xl">
                    <i data-lucide="check-circle" class="w-4 h-4 text-[#3d6f54] shrink-0 mt-0.5"></i>
                    <span class="font-medium leading-relaxed">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="flex items-start gap-2.5 px-4 py-3 text-xs bg-[#fdf4f4] border border-[#f4cfcf] text-[#742a2a] rounded-xl">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-[#a83232] shrink-0 mt-0.5"></i>
                    <span class="font-medium leading-relaxed">{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="p-3.5 text-xs bg-[#fdf4f4] border border-[#f4cfcf] text-[#742a2a] rounded-xl space-y-1">
                    <div class="font-semibold flex items-center gap-2">
                        <i data-lucide="alert-octagon" class="w-4 h-4 text-[#a83232] shrink-0"></i>
                        <span>Terjadi kesalahan:</span>
                    </div>
                    <ul class="list-disc pl-5 space-y-0.5 text-slate-600">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>

        <p class="mt-6 text-center text-[11px] text-slate-400">
            &copy; {{ date('Y') }} OPTIMA &middot; Badan Pusat Statistik (BPS)
        </p>
    </div>

    <script>
        lucide.createIcons();
    </script>
    @stack('scripts')
</body>
</html>
