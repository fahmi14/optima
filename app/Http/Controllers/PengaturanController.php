<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function index(): View
    {
        $pengaturans = Pengaturan::orderBy('id')->get();

        return view('pengaturan.index', compact('pengaturans'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sbml_mitra_per_bulan' => ['required', 'numeric', 'min:0'],
            'jam_kerja_per_hari' => ['required', 'numeric', 'min:1', 'max:24'],
        ]);

        foreach ($data as $key => $val) {
            Pengaturan::set($key, $val);
        }

        return back()->with('success', 'Standar Biaya Masukan (SBML) dan aturan jam kerja berhasil diperbarui.');
    }
}
