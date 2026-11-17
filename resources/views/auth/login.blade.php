@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <div>
        <h2 class="text-base font-bold text-slate-800">Masuk ke Akun Anda</h2>
        <p class="text-xs text-slate-500 mt-1">Silakan masukkan email dan kata sandi untuk melanjutkan.</p>
    </div>

    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email atau Username</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </span>
                <input
                    id="email"
                    type="text"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="admin atau nama@bps.go.id"
                    class="w-full pl-9 pr-3.5 py-2.5 text-xs bg-slate-50/70 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-sage-500 focus:ring-2 focus:ring-sage-500/15 transition"
                >
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-slate-700">Kata Sandi</label>
                <a href="{{ route('password.request') }}" class="text-[11px] font-semibold text-sage-600 hover:text-sage-700 hover:underline">
                    Lupa kata sandi?
                </a>
            </div>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </span>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi"
                    class="w-full pl-9 pr-10 py-2.5 text-xs bg-slate-50/70 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-sage-500 focus:ring-2 focus:ring-sage-500/15 transition"
                >
                <button
                    type="button"
                    onclick="togglePassword('password', this)"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                    title="Tampilkan / sembunyikan kata sandi"
                >
                    <i data-lucide="eye" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between pt-0.5">
            <label for="remember" class="inline-flex items-center gap-2 cursor-pointer select-none">
                <input
                    id="remember"
                    type="checkbox"
                    name="remember"
                    {{ old('remember') ? 'checked' : '' }}
                    class="w-3.5 h-3.5 rounded border-slate-300 text-sage-600 focus:ring-sage-500/30"
                >
                <span class="text-xs text-slate-600">Ingat saya</span>
            </label>
        </div>

        <button
            type="submit"
            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl shadow-xs transition cursor-pointer"
        >
            <i data-lucide="log-in" class="w-4 h-4"></i>
            <span>Masuk</span>
        </button>
    </form>

    @if(app()->environment('local'))
        <div class="pt-4 border-t border-slate-100 space-y-2.5">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-700">Login Cepat (Mode Demo)</span>
                <span class="text-[10px] text-slate-400">1-Klik Masuk</span>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" onclick="quickLogin('admin', 'admin')" class="flex flex-col items-start p-2.5 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-slate-100 hover:border-slate-300 text-left transition cursor-pointer">
                    <span class="text-[11px] font-bold text-slate-800">Admin (IPDS)</span>
                    <span class="text-[10px] text-slate-400">admin / admin</span>
                </button>
                <button type="button" onclick="quickLogin('pimpinan@bps.go.id', 'password')" class="flex flex-col items-start p-2.5 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-slate-100 hover:border-slate-300 text-left transition cursor-pointer">
                    <span class="text-[11px] font-bold text-slate-800">Pimpinan</span>
                    <span class="text-[10px] text-slate-400">Kepala BPS</span>
                </button>
                <button type="button" onclick="quickLogin('katim.sosial@bps.go.id', 'password')" class="flex flex-col items-start p-2.5 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-slate-100 hover:border-slate-300 text-left transition cursor-pointer">
                    <span class="text-[11px] font-bold text-slate-800">Ketua Tim</span>
                    <span class="text-[10px] text-slate-400">Tim Sosial</span>
                </button>
                <button type="button" onclick="quickLogin('keuangan@bps.go.id', 'password')" class="flex flex-col items-start p-2.5 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-slate-100 hover:border-slate-300 text-left transition cursor-pointer">
                    <span class="text-[11px] font-bold text-slate-800">Keuangan</span>
                    <span class="text-[10px] text-slate-400">Subbag Umum</span>
                </button>
                <button type="button" onclick="quickLogin('organik1@bps.go.id', 'password')" class="flex flex-col items-start p-2.5 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-slate-100 hover:border-slate-300 text-left transition cursor-pointer">
                    <span class="text-[11px] font-bold text-slate-800">Pegawai Organik</span>
                    <span class="text-[10px] text-slate-400">organik1</span>
                </button>
                <button type="button" onclick="quickLogin('mitra1@bps.go.id', 'password')" class="flex flex-col items-start p-2.5 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-slate-100 hover:border-slate-300 text-left transition cursor-pointer">
                    <span class="text-[11px] font-bold text-slate-800">Mitra Statistik</span>
                    <span class="text-[10px] text-slate-400">mitra1</span>
                </button>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    function quickLogin(email, password) {
        const emailInput = document.getElementById('email');
        const passInput = document.getElementById('password');
        emailInput.value = email;
        passInput.value = password;
        emailInput.closest('form').submit();
    }

    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.innerHTML = isPassword
            ? '<i data-lucide="eye-off" class="w-4 h-4"></i>'
            : '<i data-lucide="eye" class="w-4 h-4"></i>';
        lucide.createIcons();
    }
</script>
@endpush
