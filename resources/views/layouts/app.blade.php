<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'OPTIMA') - Sistem Manajemen BPS</title>

    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Tailwind CSS (Play CDN with custom pastel palette config) -->
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
                        // Muted natural pastel tones (warm, organic, professional)
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
                        slatecustom: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
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
        /* Custom clean scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #CBD5E1; }
    </style>
</head>

<body class="h-full flex overflow-hidden text-slate-700 bg-[#F8F9FA]">
    <!-- Clean Minimalist Sidebar -->
    <aside class="w-64 bg-white border-r border-slate-200/80 flex flex-col shrink-0 z-30 transition-all duration-200">
        <!-- Brand Header -->
        <div class="h-16 flex items-center px-6 border-b border-slate-100 gap-3">
            <div class="w-8 h-8 rounded-lg bg-slate-800 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                <i data-lucide="layers" class="w-4 h-4"></i>
            </div>
            <div>
                <span class="font-bold text-slate-800 tracking-tight text-base block leading-none">OPTIMA</span>
                <span class="text-[10px] text-slate-400 font-medium tracking-wider uppercase block mt-1">Sistem Penugasan</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
            <div>
                <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Utama</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="layout-grid" class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>

            <div>
                <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Master Data</p>
                <div class="space-y-1">
                    <a href="{{ route('wilayah.index') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('wilayah.*') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="map-pin" class="w-4 h-4 {{ request()->routeIs('wilayah.*') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Wilayah</span>
                    </a>

                    <a href="{{ route('tim.index') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('tim.*') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="users-2" class="w-4 h-4 {{ request()->routeIs('tim.*') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Tim Kerja</span>
                    </a>

                    <a href="{{ route('user.index') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('user.*') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="user-check" class="w-4 h-4 {{ request()->routeIs('user.*') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Pengguna & Petugas</span>
                    </a>
                </div>
            </div>

            <div>
                <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Operasional</p>
                <div class="space-y-1">
                    <a href="{{ route('kegiatan.index') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('kegiatan.*') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="calendar-range" class="w-4 h-4 {{ request()->routeIs('kegiatan.*') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Kegiatan Survei</span>
                    </a>

                    <a href="{{ route('matrik.index') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('matrik.*') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="clipboard-list" class="w-4 h-4 {{ request()->routeIs('matrik.*') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Matrik Tugas</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Sidebar Footer Status -->
        <div class="p-3 border-t border-slate-100">
            <div class="flex items-center gap-3 p-2 bg-slate-50 rounded-lg">
                <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-semibold">
                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-slate-800 truncate">Administrator</p>
                    <p class="text-[10px] text-slate-400 truncate">admin@bps.go.id</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Topbar -->
        <header class="h-16 bg-white border-b border-slate-200/80 flex items-center justify-between px-8 shrink-0">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="hover:text-slate-700">OPTIMA</span>
                <span>/</span>
                <span class="text-slate-800 font-semibold capitalize">@yield('title', 'Dashboard')</span>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-[#f0f4f1] text-[#3d5a4c] border border-[#d6e3db]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#52796f]"></span>
                    Mode Admin
                </span>
            </div>
        </header>

        <!-- Body Content -->
        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Notifications -->
                @if(session('success'))
                    <div class="flex items-center justify-between px-4 py-3 text-xs bg-[#f2f7f4] border border-[#cbe1d4] text-[#284838] rounded-xl shadow-xs">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4 text-[#3d6f54]"></i>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="flex items-center justify-between px-4 py-3 text-xs bg-[#fdf4f4] border border-[#f4cfcf] text-[#742a2a] rounded-xl shadow-xs">
                        <div class="flex items-center gap-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-[#a83232]"></i>
                            <span class="font-medium">{{ session('error') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="p-4 text-xs bg-[#fdf4f4] border border-[#f4cfcf] text-[#742a2a] rounded-xl space-y-1">
                        <div class="font-semibold flex items-center gap-2 mb-1">
                            <i data-lucide="alert-octagon" class="w-4 h-4 text-[#a83232]"></i>
                            Terdapat kesalahan input:
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

            <!-- Footer -->
            <div class="max-w-7xl mx-auto mt-12 pt-6 border-t border-slate-200/60 flex items-center justify-between text-[11px] text-slate-400">
                <p>&copy; {{ date('Y') }} OPTIMA. Didesain bersih, fungsional, dan human-first.</p>
                <p>Badan Pusat Statistik (BPS)</p>
            </div>
        </main>
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
    @stack('scripts')
</body>
</html>
