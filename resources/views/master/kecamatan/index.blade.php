@extends('layouts.app')

@section('title', 'Master Kecamatan Wonosobo')

@section('content')
<div x-data="{ openAdd: false, editKec: null }">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Master Kecamatan Kabupaten Wonosobo</h1>
            <p class="text-xs text-slate-500">15 Kecamatan wilayah kerja BPS Kabupaten Wonosobo.</p>
        </div>
        <button @click="openAdd = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-800 text-white rounded-xl hover:bg-slate-900 cursor-pointer">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            <span>Tambah Kecamatan</span>
        </button>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-semibold text-slate-500 uppercase">
                    <th class="py-3 px-4">Kode BPS</th>
                    <th class="py-3 px-4">Nama Kecamatan</th>
                    <th class="py-3 px-4">Statistik</th>
                    <th class="py-3 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($kecamatans as $kec)
                <tr class="hover:bg-slate-50/50">
                    <td class="py-3 px-4 font-mono text-slate-600">{{ $kec->kode }}</td>
                    <td class="py-3 px-4 font-semibold text-slate-800">Kecamatan {{ $kec->nama }}</td>
                    <td class="py-3 px-4 text-slate-500">{{ $kec->users_count }} personil domisili &middot; {{ $kec->penugasans_count }} penugasan</td>
                    <td class="py-3 px-4 text-right space-x-1">
                        <button @click="editKec = {{ $kec->toJson() }}" class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 text-slate-700 rounded-lg cursor-pointer">Edit</button>
                        <form method="POST" action="{{ route('kecamatan.destroy', $kec) }}" class="inline" onsubmit="return confirm('Hapus Kecamatan ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold bg-rose-50 text-rose-700 rounded-lg cursor-pointer">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div x-show="openAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="openAdd = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-800">Tambah Kecamatan</h3>
            <form method="POST" action="{{ route('kecamatan.store') }}" class="space-y-3 text-xs">
                @csrf
                <div><label class="block font-semibold mb-1">Kode BPS</label><input type="text" name="kode" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                <div><label class="block font-semibold mb-1">Nama Kecamatan</label><input type="text" name="nama" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openAdd = false" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="editKec" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="editKec = null" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4" x-if="editKec">
            <h3 class="text-sm font-bold text-slate-800">Edit Kecamatan</h3>
            <form method="POST" :action="'/master/kecamatan/' + editKec.id" class="space-y-3 text-xs">
                @csrf @method('PUT')
                <div><label class="block font-semibold mb-1">Kode BPS</label><input type="text" name="kode" x-model="editKec.kode" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                <div><label class="block font-semibold mb-1">Nama Kecamatan</label><input type="text" name="nama" x-model="editKec.nama" required class="w-full border border-slate-200 rounded-xl px-3 py-2"></div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="editKec = null" class="px-4 py-2 rounded-xl border border-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white font-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
