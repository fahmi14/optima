<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use App\Models\Tim;
use App\Models\User;
use App\Models\Kegiatan;
use App\Models\MatrikTugas;

class DashboardController extends Controller
{
    public function index()
    {
        $totalWilayah = Wilayah::count();
        $totalTim = Tim::count();
        $totalUser = User::count();
        $totalOrganik = User::where('tipe', 'organik')->count();
        $totalMitra = User::where('tipe', 'mitra')->count();
        $totalKegiatan = Kegiatan::count();
        $totalMatrik = MatrikTugas::count();

        $recentKegiatan = Kegiatan::with('tim')->latest()->take(5)->get();
        $recentMatrik = MatrikTugas::with(['kegiatan', 'user', 'wilayah'])->latest()->take(5)->get();

        return view('dashboard', compact(
            'totalWilayah',
            'totalTim',
            'totalUser',
            'totalOrganik',
            'totalMitra',
            'totalKegiatan',
            'totalMatrik',
            'recentKegiatan',
            'recentMatrik'
        ));
    }
}
