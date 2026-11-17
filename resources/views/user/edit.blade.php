@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center justify-between pb-2">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Edit Pengguna #{{ $user->id }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Perbarui profil {{ $user->name }}.</p>
        </div>
        <a href="{{ route('user.index') }}" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition">
            &larr; Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-xs">
        <form action="{{ route('user.update', $user) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="password" name="password" placeholder="Kosongkan jika tak diubah" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Level <span class="text-rose-500">*</span></label>
                    <select name="level" required class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
                        <option value="operator" {{ old('level', $user->level) == 'operator' ? 'selected' : '' }}>Operator</option>
                        <option value="admin" {{ old('level', $user->level) == 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Pengguna <span class="text-rose-500">*</span></label>
                    <select name="tipe" id="tipeSelect" required onchange="toggleTipeFields()" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none font-semibold text-slate-800">
                        <option value="organik" {{ old('tipe', $user->tipe) == 'organik' ? 'selected' : '' }}>Organik (Pegawai)</option>
                        <option value="mitra" {{ old('tipe', $user->tipe) == 'mitra' ? 'selected' : '' }}>Mitra Statistik</option>
                    </select>
                </div>
            </div>

            <!-- Organik Fields -->
            @php
                $organik = $user->organik;
                $userTimIds = is_array($organik?->id_tim) ? $organik->id_tim : [];
            @endphp
            <div id="section-organik" class="p-4 bg-[#f4f7f5] border border-[#d6e3db] rounded-lg space-y-3">
                <span class="text-xs font-bold uppercase tracking-wider text-[#2d5f3e] block">Profil Pegawai Organik</span>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">NIP</label>
                        <input type="text" name="nip" value="{{ old('nip', $organik?->nip) }}" class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Jabatan</label>
                        <input type="text" name="jabatan" value="{{ old('jabatan', $organik?->jabatan) }}" class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Golongan</label>
                        <input type="text" name="golongan" value="{{ old('golongan', $organik?->golongan) }}" class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Keanggotaan Tim (id_tim JSON - bisa lebih dari 1):</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($tims as $tim)
                            <label class="flex items-center gap-2 text-xs bg-white p-2 rounded-lg border border-slate-200/80 cursor-pointer">
                                <input type="checkbox" name="id_tim[]" value="{{ $tim->id }}" {{ in_array($tim->id, $userTimIds) ? 'checked' : '' }} class="rounded text-slate-800">
                                <span class="text-slate-700">{{ $tim->nama }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Mitra Fields -->
            @php $mitra = $user->mitra; @endphp
            <div id="section-mitra" class="p-4 bg-[#faf6f0] border border-[#e8ded0] rounded-lg space-y-3 hidden">
                <span class="text-xs font-bold uppercase tracking-wider text-[#7d4e24] block">Profil Mitra Lapangan</span>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">NIK (16 Digit)</label>
                        <input type="text" name="nik" value="{{ old('nik', $mitra?->nik) }}" class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Domisili Kecamatan</label>
                        <input type="text" name="kecamatan" value="{{ old('kecamatan', $mitra?->kecamatan) }}" class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">No. WhatsApp / HP</label>
                        <input type="text" name="no_hp" value="{{ old('no_hp', $mitra?->no_hp) }}" class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
                <a href="{{ route('user.index') }}" class="px-3.5 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-white rounded-lg shadow-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function toggleTipeFields() {
        const val = document.getElementById('tipeSelect').value;
        const org = document.getElementById('section-organik');
        const mit = document.getElementById('section-mitra');
        if (val === 'organik') {
            org.classList.remove('hidden');
            mit.classList.add('hidden');
        } else {
            org.classList.add('hidden');
            mit.classList.remove('hidden');
        }
    }
    document.addEventListener('DOMContentLoaded', toggleTipeFields);
</script>
@endpush
@endsection
