@extends('layouts.app')

@section('title', 'Tambah Tim')

@section('content')
<div class="max-w-xl mx-auto space-y-4">
    <div class="flex items-center justify-between pb-2">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Tambah Tim Kerja</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelompok kerja atau tim fungsional kegiatan.</p>
        </div>
        <a href="{{ route('tim.index') }}" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition">
            &larr; Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-xs">
        <form action="{{ route('tim.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Tim <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" value="{{ old('nama') }}" required placeholder="Contoh: Tim Statistik Sosial" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Penanggung Jawab (PJ)</label>
                <input type="text" name="pj" value="{{ old('pj') }}" placeholder="Contoh: Budi Santoso, S.ST" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
            </div>

            <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
                <a href="{{ route('tim.index') }}" class="px-3.5 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-white rounded-lg shadow-xs">Simpan Tim</button>
            </div>
        </form>
    </div>
</div>
@endsection
