<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                        brand: {
                            50: '#f5f7fa',
                            100: '#ebeff5',
                            200: '#d7e0ec',
                            500: '#475569',
                            800: '#1e293b',
                            900: '#0f172a',
                        },
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
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background-color: #F8FAFC; color: #334155; }
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
        .pastel-card {
            background-color: #FFFFFF;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
            border-radius: 1.25rem;
        }
    </style>
</head>
<body class="h-full flex overflow-hidden bg-[#F8FAFC] text-slate-700 antialiased" x-data="{ sidebarOpen: false }">

    <!-- Mobile Backdrop -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-30 lg:hidden transition-opacity"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 w-68 bg-white border-r border-slate-200/70 flex flex-col shrink-0 transition-transform duration-200 ease-in-out lg:static lg:translate-x-0">
        <!-- Brand -->
        <div class="h-18 flex items-center justify-between px-6 border-b border-slate-100">
            <div class="flex items-center gap-3.5">
                <div class="w-9 h-9 rounded-xl bg-slate-800 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                </div>
                <div>
                    <span class="font-bold text-slate-800 tracking-tight text-sm block leading-none">OPTIMA 3307</span>
                    <span class="text-[11px] text-slate-400 font-medium tracking-wide block mt-1.5">BPS Kab. Wonosobo</span>
                </div>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden p-1.5 text-slate-400 hover:text-slate-600 rounded-lg">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
            <div>
                <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Utama</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="layout-grid" class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('kalender.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('kalender.*') ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="calendar" class="w-4 h-4 {{ request()->routeIs('kalender.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Kalender Pekerjaan</span>
                    </a>

                    <a href="{{ route('tugas-saya.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('tugas-saya.*') ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="check-square" class="w-4 h-4 {{ request()->routeIs('tugas-saya.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Tugas Saya</span>
                    </a>
                </div>
            </div>

            @if(auth()->user()->hasAnyRole(['admin', 'katim', 'keuangan']))
            <div>
                <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Kegiatan &amp; SPJ</p>
                <div class="space-y-1">
                    @if(auth()->user()->hasAnyRole(['admin', 'katim']))
                    <a href="{{ route('kegiatan.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('kegiatan.*') ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="folder-kanban" class="w-4 h-4 {{ request()->routeIs('kegiatan.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Kegiatan &amp; Penugasan</span>
                    </a>
                    @endif

                    <a href="{{ route('transport.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('transport.*') ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="receipt" class="w-4 h-4 {{ request()->routeIs('transport.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Transport &amp; SPJ</span>
                    </a>
                </div>
            </div>
            @endif

            @if(auth()->user()->hasRole('admin'))
            <div>
                <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Master Data &amp; Konfigurasi</p>
                <div class="space-y-1">
                    <a href="{{ route('user.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('user.*') ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="users" class="w-4 h-4 {{ request()->routeIs('user.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Pegawai &amp; Mitra</span>
                    </a>
                    <a href="{{ route('tim.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('tim.*') ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="briefcase" class="w-4 h-4 {{ request()->routeIs('tim.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Tim Kerja</span>
                    </a>
                    <a href="{{ route('kecamatan.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('kecamatan.*') ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="map-pin" class="w-4 h-4 {{ request()->routeIs('kecamatan.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Kecamatan Wonosobo</span>
                    </a>
                    <a href="{{ route('pengaturan.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('pengaturan.*') ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i data-lucide="settings" class="w-4 h-4 {{ request()->routeIs('pengaturan.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>SBML &amp; Jam Kerja</span>
                    </a>
                </div>
            </div>
            @endif
        </nav>

        <!-- Footer User -->
        <div class="p-4 border-t border-slate-100">
            <div class="flex items-center gap-3 p-2.5 bg-slate-50/80 rounded-2xl border border-slate-100">
                <div class="w-8.5 h-8.5 rounded-xl bg-slate-800 text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-2xs">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-slate-800 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">{{ auth()->user()->roleLabel() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" title="Keluar" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Topbar -->
        <header class="h-18 bg-white border-b border-slate-200/70 flex items-center justify-between px-6 sm:px-10 shrink-0">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2 text-slate-500 hover:text-slate-800 rounded-xl hover:bg-slate-100">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="flex items-center gap-2.5 text-xs text-slate-500">
                    <span class="hidden sm:inline hover:text-slate-700 font-medium">OPTIMA 3307</span>
                    <span class="hidden sm:inline text-slate-300">/</span>
                    <span class="text-slate-800 font-bold">@yield('title', 'Dashboard')</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200/70">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    {{ auth()->user()->roleLabel() }}
                </span>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto p-6 sm:p-8 lg:p-10">
            <div class="max-w-7xl mx-auto space-y-7">
                @if(session('success'))
                    <div class="flex items-center justify-between px-5 py-3.5 text-xs bg-emerald-50/80 border border-emerald-200 text-emerald-800 rounded-2xl shadow-2xs">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                            <span class="font-semibold">{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 p-1"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="flex items-center justify-between px-5 py-3.5 text-xs bg-rose-50/80 border border-rose-200 text-rose-800 rounded-2xl shadow-2xs">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                            <span class="font-semibold">{{ session('error') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 p-1"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                    </div>
                @endif

                @if(session('import_report'))
                    @php $rep = session('import_report'); @endphp
                    <div class="p-5 text-xs bg-white border border-slate-200 rounded-2xl shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800 text-sm">{{ $rep['title'] }}</span>
                            <button onclick="this.parentElement.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                        </div>
                        <p class="text-emerald-700 font-semibold">Berhasil diproses: {{ $rep['success_count'] }} baris.</p>
                        @if(!empty($rep['errors']))
                            <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 space-y-1.5">
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
                    <div class="p-5 text-xs bg-rose-50/80 border border-rose-200 text-rose-800 rounded-2xl space-y-1.5 shadow-2xs">
                        <div class="font-bold flex items-center gap-2 text-rose-900 mb-1">
                            <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-600"></i>
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

            <footer class="max-w-7xl mx-auto mt-14 pt-8 border-t border-slate-200/60 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400">
                <p>&copy; {{ date('Y') }} OPTIMA 3307 &mdash; Optimalisasi Penugasan Tim Kerja &amp; Manajemen Administrasi</p>
                <p class="font-medium text-slate-500">BPS Kabupaten Wonosobo</p>
            </footer>
        </main>
    </div>

    @include('components.chat-widget')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
    </script>
</body>
</html>
