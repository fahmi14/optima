<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KecamatanController extends Controller
{
    public function index(): View
    {
        $kecamatans = Kecamatan::withCount(['users', 'penugasans'])->orderBy('kode')->get();

        return view('master.kecamatan.index', compact('kecamatans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:kecamatans,kode'],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        Kecamatan::create($data);

        return back()->with('success', 'Kecamatan berhasil ditambahkan.');
    }

    public function update(Request $request, Kecamatan $kecamatan): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:kecamatans,kode,' . $kecamatan->id],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $kecamatan->update($data);

        return back()->with('success', 'Kecamatan berhasil diperbarui.');
    }

    public function destroy(Kecamatan $kecamatan): RedirectResponse
    {
        $kecamatan->delete();

        return back()->with('success', 'Kecamatan berhasil dihapus.');
    }
}
