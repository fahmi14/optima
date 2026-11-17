@extends('layouts.guest')

@section('title', 'Lupa Kata Sandi')

@section('content')
    <div>
        <h2 class="text-base font-bold text-slate-800">Lupa Kata Sandi</h2>
        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
            Masukkan alamat email yang terdaftar. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi Anda.
        </p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email Terdaftar</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </span>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="nama@bps.go.id"
                    class="w-full pl-9 pr-3.5 py-2.5 text-xs bg-slate-50/70 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-sage-500 focus:ring-2 focus:ring-sage-500/15 transition"
                >
            </div>
        </div>

        <button
            type="submit"
            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl shadow-xs transition cursor-pointer"
        >
            <i data-lucide="send" class="w-4 h-4"></i>
            <span>Kirim Tautan Reset</span>
        </button>
    </form>

    <div class="pt-2 border-t border-slate-100 text-center">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali ke halaman login</span>
        </a>
    </div>
@endsection
