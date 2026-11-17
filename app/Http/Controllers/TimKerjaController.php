<?php

namespace App\Http\Controllers;

use App\Models\TimKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimKerjaController extends Controller
{
    public function index(): View
    {
        $timKerjas = TimKerja::withCount(['users', 'masterKegiatans'])->orderBy('nama')->get();

        return view('master.tim.index', compact('timKerjas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:tim_kerjas,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'ketua_tim' => ['nullable', 'string', 'max:255'],
        ]);

        TimKerja::create($data);

        return back()->with('success', 'Tim Kerja berhasil ditambahkan.');
    }

    public function update(Request $request, TimKerja $tim): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:tim_kerjas,kode,' . $tim->id],
            'nama' => ['required', 'string', 'max:255'],
            'ketua_tim' => ['nullable', 'string', 'max:255'],
        ]);

        $tim->update($data);

        return back()->with('success', 'Tim Kerja berhasil diperbarui.');
    }

    public function destroy(TimKerja $tim): RedirectResponse
    {
        $tim->delete();

        return back()->with('success', 'Tim Kerja berhasil dihapus.');
    }
}
