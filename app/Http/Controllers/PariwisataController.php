<?php

namespace App\Http\Controllers;

use App\Models\Pariwisata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PariwisataController extends Controller implements FromCollection, WithHeadings
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $kecamatan = $request->query('kecamatan');
        $jenis_wisata = $request->query('jenis_wisata');
        if ($kecamatan && $jenis_wisata) {
            $pariwisata = Pariwisata::where('kecamatan', $kecamatan)
                                    ->where('jenis_wisata', $jenis_wisata)
                                    ->get();
            return response()->json($pariwisata);
        } elseif ($kecamatan) {
            $pariwisata = Pariwisata::where('kecamatan', $kecamatan)->get();
            return response()->json($pariwisata);
        } elseif ($jenis_wisata) {
            $pariwisata = Pariwisata::where('jenis_wisata', $jenis_wisata)->get();
            return response()->json($pariwisata);
        }

        $pariwisata = Pariwisata::all();
        return response()->json($pariwisata);
    }

    public function indexByUser(Request $request) {
        $user = JWTAuth::parseToken()->authenticate();
        $jenis_wisata = $request->query('jenis_wisata');
        if ($jenis_wisata) {
            $pariwisata = Pariwisata::where('kecamatan', $user->kecamatan)
                                    ->where('jenis_wisata', $jenis_wisata)
                                    ->get();
            return response()->json($pariwisata);
        }
        $pariwisata = Pariwisata::where('kecamatan', $user->kecamatan)->get();
        return response()->json($pariwisata);
    }

    public function indexByNamaWisata($nama_wisata) {
        $data = Pariwisata::where('nama_wisata', $nama_wisata)
                        ->select('nama_wisata',
                        DB::raw('SUM(wisatawan) as total_wisatawan'),
                    )
                        ->groupBy('nama_wisata')
                        ->get();

        return response()->json($data);
    }

    public function indexByYear($year) {
        $data = Pariwisata::whereYear('updated_at', $year)
                        ->select('nama_wisata', 'jenis_wisata', 'kecamatan', 'desa',
                        DB::raw('SUM(wisatawan) as total_wisatawan'),
                    )
                        ->groupBy('nama_wisata', 'jenis_wisata','kecamatan', 'desa')
                        ->get();

        return response()->json($data);
    }

    protected $nama_wisata;

    public function downloadExcel($nama_wisata) {
        $filename = $nama_wisata . '_data.xlsx';
        return Excel::download($this, $filename);
    }

    public function collection() {
        return Pariwisata::where('nama_wisata', $this->nama_wisata)
        ->select('nama_wisata', 'kecamatan', 'desa',
        DB::raw('SUM(wisatawan) as total_wisatawan'),
    )
        ->groupBy('nama_wisata', 'kecamatan', 'desa')
        ->get();
    }

    public function headings():array {
        return [
            'Nama Wisata',
            'Kecamatan',
            'Desa',
            'Wisatawan'
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'nama_wisata' => 'required|string|max:100',
            'jenis_wisata' => 'required|string|max:50',
            'desa' => 'required|string|max:50',
            'wisatawan' => 'required|integer|min:0',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        $pariwisata = new Pariwisata();
        $pariwisata->id = uniqid();
        $pariwisata->user_id = $user->id;
        $pariwisata->kecamatan = $user->kecamatan;
        $pariwisata->nama_wisata = $validateData['nama_wisata'];
        $pariwisata->jenis_wisata = $validateData['jenis_wisata'];
        $pariwisata->desa = $validateData['desa'];
        $pariwisata->wisatawan = $validateData['wisatawan'];
        $pariwisata->save();

        return response()->json([
            'message' => 'Data pariwisata berhasil ditambahkan',
            'pariwisata' => $pariwisata
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pariwisata = Pariwisata::find($id);
        if ($pariwisata) {
            return response()->json([
                'success' => true,
                'pariwisata' => $pariwisata
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'Data Pariwisata tidak ditemukan'
        ], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'nama_wisata' => 'required|string|max:75',
            'jenis_wisata' => 'required|string|max:50',
            'desa' => 'required|string|max:50',
            'wisatawan' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }

        $pariwisata = Pariwisata::findOrFail($id);
        $pariwisata->nama_wisata = $request->input('nama_wisata');
        $pariwisata->jenis_wisata = $request->input('jenis_wisata');
        $pariwisata->desa = $request->input('desa');
        $pariwisata->wisatawan = $request->input('wisatawan');
        $pariwisata->save();

        return response()->json([
            'message' => 'Data pariwisata berhasil diubah',
            'perikanan' => $pariwisata
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pariwisata = Pariwisata::findOrFail($id);
        $pariwisata->delete();

        return response()->json([
            'message' => 'Data pariwisata berhasil dihapus'
        ]);
    }
}
