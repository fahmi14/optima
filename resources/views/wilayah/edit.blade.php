@extends('layouts.app')

@section('title', 'Edit Wilayah')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center justify-between pb-2">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Edit Wilayah #{{ $wilayah->id }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Perbarui data wilayah administratif.</p>
        </div>
        <a href="{{ route('wilayah.index') }}" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition">
            &larr; Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-xs">
        <form action="{{ route('wilayah.update', $wilayah) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Wilayah / Keterangan <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" value="{{ old('nama', $wilayah->nama) }}" required class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kabupaten/Kota</label>
                    <input type="text" name="kab" value="{{ old('kab', $wilayah->kab) }}" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Kabupaten (kd_kab)</label>
                    <input type="text" name="kd_kab" value="{{ old('kd_kab', $wilayah->kd_kab) }}" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kecamatan</label>
                    <input type="text" name="kec" value="{{ old('kec', $wilayah->kec) }}" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Kecamatan (kd_kec)</label>
                    <input type="text" name="kd_kec" value="{{ old('kd_kec', $wilayah->kd_kec) }}" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Desa / Kelurahan</label>
                    <input type="text" name="desa" value="{{ old('desa', $wilayah->desa) }}" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Desa (kd_desa)</label>
                    <input type="text" name="kd_desa" value="{{ old('kd_desa', $wilayah->kd_desa) }}" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">SLS</label>
                    <input type="text" name="sls" value="{{ old('sls', $wilayah->sls) }}" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kode SLS (kd_sls)</label>
                    <input type="text" name="kd_sls" value="{{ old('kd_sls', $wilayah->kd_sls) }}" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sub SLS</label>
                    <input type="text" name="subsls" value="{{ old('subsls', $wilayah->subsls) }}" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
                <a href="{{ route('wilayah.index') }}" class="px-3.5 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-white rounded-lg shadow-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
