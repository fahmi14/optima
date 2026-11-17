<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - OPTIMA 3307 BPS Kabupaten Wonosobo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        sage: {
                            50: '#f4f7f5',
                            100: '#e5ece7',
                            200: '#cbdad0',
                            500: '#52796f',
                            600: '#354f52',
                            700: '#2f3e46',
                        },
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background-color: #F8F9FA; color: #1E293B; }
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 9999px; }
    </style>
</head>
<body class="h-full flex overflow-hidden bg-[#F8F9FA] text-slate-700" x-data="{ sidebarOpen: false }">

    <!-- Mobile Backdrop -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/40 z-30 lg:hidden"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200/80 flex flex-col shrink-0 transition-transform duration-200 lg:static lg:translate-x-0">
        <!-- Brand -->
        <div class="h-16 flex items-center justify-between px-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-800 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                </div>
                <div>
                    <span class="font-bold text-slate-800 tracking-tight text-sm block leading-none">OPTIMA 3307</span>
                    <span class="text-[10px] text-slate-400 font-medium tracking-wide block mt-1">BPS Kab. Wonosobo</span>
                </div>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5">
            <div>
                <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Utama</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="layout-grid" class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('kalender.index') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('kalender.*') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="calendar" class="w-4 h-4 {{ request()->routeIs('kalender.*') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Kalender Pekerjaan</span>
                    </a>

                    <a href="{{ route('tugas-saya.index') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('tugas-saya.*') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="check-square" class="w-4 h-4 {{ request()->routeIs('tugas-saya.*') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Tugas Saya</span>
                    </a>
                </div>
            </div>

            @if(auth()->user()->hasAnyRole(['admin', 'katim', 'keuangan']))
            <div>
                <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Operasional</p>
                <div class="space-y-1">
                    @if(auth()->user()->hasAnyRole(['admin', 'katim']))
                    <a href="{{ route('kegiatan.index') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('kegiatan.*') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="calendar-range" class="w-4 h-4 {{ request()->routeIs('kegiatan.*') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Kegiatan &amp; Penugasan</span>
                    </a>
                    @endif

                    <a href="{{ route('transport.index') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('transport.*') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="receipt" class="w-4 h-4 {{ request()->routeIs('transport.*') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Rekap Transport SPJ</span>
                    </a>
                </div>
            </div>
            @endif

            @if(auth()->user()->isAdmin())
            <div>
                <p class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Master Data (IPDS)</p>
                <div class="space-y-1">
                    <a href="{{ route('user.index') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('user.*') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="users" class="w-4 h-4 {{ request()->routeIs('user.*') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Pegawai &amp; Mitra</span>
                    </a>
                    <a href="{{ route('tim.index') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('tim.*') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="briefcase" class="w-4 h-4 {{ request()->routeIs('tim.*') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Tim Kerja</span>
                    </a>
                    <a href="{{ route('kecamatan.index') }}" class="flex items-center gap-3 px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ request()->routeIs('kecamatan.*') ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="map-pin" class="w-4 h-4 {{ request()->routeIs('kecamatan.*') ? 'text-slate-800' : 'text-slate-400' }}"></i>
                        <span>Kecamatan Wonosobo</span>
                    </a>
                </div>
            </div>
            @endif
        </nav>

        <!-- Footer User -->
        <div class="p-3 border-t border-slate-100">
            <div class="flex items-center gap-2.5 p-2 bg-slate-50 rounded-xl">
                <div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs font-bold shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-slate-800 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-slate-400 truncate">{{ auth()->user()->roleLabel() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" title="Keluar" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Topbar -->
        <header class="h-16 bg-white border-b border-slate-200/80 flex items-center justify-between px-4 sm:px-8 shrink-0">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2 text-slate-500 hover:text-slate-800 rounded-lg hover:bg-slate-100">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="hidden sm:inline hover:text-slate-700">OPTIMA 3307</span>
                    <span class="hidden sm:inline">/</span>
                    <span class="text-slate-800 font-semibold">@yield('title', 'Dashboard')</span>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-[#f0f4f1] text-[#3d5a4c] border border-[#d6e3db]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#52796f]"></span>
                    {{ auth()->user()->roleLabel() }}
                </span>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                @if(session('success'))
                    <div class="flex items-center justify-between px-4 py-3 text-xs bg-[#f2f7f4] border border-[#cbe1d4] text-[#284838] rounded-xl">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4 text-[#3d6f54] shrink-0"></i>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="flex items-center justify-between px-4 py-3 text-xs bg-[#fdf4f4] border border-[#f4cfcf] text-[#742a2a] rounded-xl">
                        <div class="flex items-center gap-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-[#a83232] shrink-0"></i>
                            <span class="font-medium">{{ session('error') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                    </div>
                @endif

                @if(session('import_report'))
                    @php $rep = session('import_report'); @endphp
                    <div class="p-4 text-xs bg-white border border-slate-200 rounded-xl shadow-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">{{ $rep['title'] }}</span>
                            <button onclick="this.parentElement.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                        </div>
                        <p class="text-emerald-700 font-semibold">Berhasil diproses: {{ $rep['success_count'] }} baris.</p>
                        @if(!empty($rep['errors']))
                            <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 space-y-1">
                                <p class="font-semibold">Baris gagal / dilewati ({{ count($rep['errors']) }}):</p>
                                <ul class="list-disc pl-5 space-y-0.5 max-h-40 overflow-y-auto">
                                    @foreach($rep['errors'] as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                @if($errors->any())
                    <div class="p-4 text-xs bg-[#fdf4f4] border border-[#f4cfcf] text-[#742a2a] rounded-xl space-y-1">
                        <div class="font-semibold flex items-center gap-2 mb-1">
                            <i data-lucide="alert-octagon" class="w-4 h-4 text-[#a83232]"></i>
                            Terdapat kesalahan input:
                        </div>
                        <ul class="list-disc pl-5 space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>

            <footer class="max-w-7xl mx-auto mt-12 pt-6 border-t border-slate-200/60 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-slate-400">
                <p>&copy; {{ date('Y') }} OPTIMA 3307 &mdash; Optimalisasi Penugasan Tim Kerja &amp; Manajemen Administrasi</p>
                <p>BPS Kabupaten Wonosobo</p>
            </footer>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>
