@extends('layouts.app')

@section('title', 'Alokasi Matrik Tugas')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center justify-between pb-2">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Alokasi Matrik Tugas Baru</h1>
            <p class="text-xs text-slate-500 mt-0.5">Petakan petugas, peran survei, wilayah penugasan, dan beban volume.</p>
        </div>
        <a href="{{ route('matrik.index') }}" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition">
            &larr; Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-xs">
        <form action="{{ route('matrik.store') }}" method="POST" class="space-y-4">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kegiatan Survei <span class="text-rose-500">*</span></label>
                    <select name="id_kegiatan" required class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400">
                        <option value="">-- Pilih Kegiatan --</option>
                        @foreach($kegiatans as $k)
                            <option value="{{ $k->id }}" {{ old('id_kegiatan') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Petugas (Pengguna) <span class="text-rose-500">*</span></label>
                    <select name="id_user" required class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                        <option value="">-- Pilih Petugas --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ old('id_user') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ ucfirst($u->tipe) }} - {{ $u->level }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Role (JSON) -->
            <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-lg space-y-3">
                <label class="block text-xs font-bold text-slate-700 uppercase">Role / Posisi Penugasan (Bisa Pilih Lebih dari 1 - JSON):</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @php
                        $rolesList = ['PCL (Pencacah Lapangan)', 'PML (Pengawas Lapangan)', 'Koseka', 'Pemeriksa', 'Operator Entri', 'Editor Coding'];
                    @endphp
                    @foreach($rolesList as $r)
                        <label class="flex items-center gap-2 text-xs bg-white p-2 rounded-lg border border-slate-200/80 cursor-pointer">
                            <input type="checkbox" name="role[]" value="{{ $r }}" {{ is_array(old('role')) && in_array($r, old('role')) ? 'checked' : '' }} class="rounded text-slate-800">
                            <span class="text-slate-700">{{ $r }}</span>
                        </label>
                    @endforeach
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Role Kustom Tambahan:</label>
                    <input type="text" name="custom_role" value="{{ old('custom_role') }}" placeholder="Contoh: Koordinator Wilayah" class="w-full text-xs p-2 rounded-lg border border-slate-200 bg-white focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Wilayah Tugas</label>
                    <select name="id_wilayah" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                        <option value="">-- Tanpa Wilayah Tertentu --</option>
                        @foreach($wilayahs as $w)
                            <option value="{{ $w->id }}" {{ old('id_wilayah') == $w->id ? 'selected' : '' }}>
                                {{ $w->nama }} ({{ $w->kec }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Tugas</label>
                    <input type="date" name="tgl" value="{{ old('tgl') }}" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Beban Tugas / Volume <span class="text-rose-500">*</span></label>
                    <input type="number" name="beban" value="{{ old('beban', 1) }}" required min="0" placeholder="10" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none font-bold text-slate-800">
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
                <a href="{{ route('matrik.index') }}" class="px-3.5 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-white rounded-lg shadow-xs">Simpan Alokasi</button>
            </div>
        </form>
    </div>
</div>
@endsection
