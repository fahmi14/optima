@extends('layouts.guest')

@section('title', 'Reset Kata Sandi')

@section('content')
    <div>
        <h2 class="text-base font-bold text-slate-800">Buat Kata Sandi Baru</h2>
        <p class="text-xs text-slate-500 mt-1">Masukkan kata sandi baru untuk akun Anda.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </span>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    required
                    autocomplete="email"
                    placeholder="nama@bps.go.id"
                    class="w-full pl-9 pr-3.5 py-2.5 text-xs bg-slate-50/70 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-sage-500 focus:ring-2 focus:ring-sage-500/15 transition"
                >
            </div>
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi Baru</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </span>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autofocus
                    autocomplete="new-password"
                    placeholder="Minimal 6 karakter"
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

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">Konfirmasi Kata Sandi</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                    <i data-lucide="lock-keyhole" class="w-4 h-4"></i>
                </span>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi baru"
                    class="w-full pl-9 pr-10 py-2.5 text-xs bg-slate-50/70 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-sage-500 focus:ring-2 focus:ring-sage-500/15 transition"
                >
                <button
                    type="button"
                    onclick="togglePassword('password_confirmation', this)"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                    title="Tampilkan / sembunyikan kata sandi"
                >
                    <i data-lucide="eye" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <button
            type="submit"
            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl shadow-xs transition cursor-pointer"
        >
            <i data-lucide="check" class="w-4 h-4"></i>
            <span>Reset Kata Sandi</span>
        </button>
    </form>

    <div class="pt-2 border-t border-slate-100 text-center">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali ke halaman login</span>
        </a>
    </div>
@endsection

@push('scripts')
<script>
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
