<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Perikanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PerikananController extends Controller implements FromCollection, WithHeadings
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $kecamatan = $request->query('kecamatan');
        if ($kecamatan) {
            $perikanan = Perikanan::where('kecamatan', $kecamatan)->get();
            return response()->json($perikanan);
        }
        $perikanan = Perikanan::all();
        return response()->json($perikanan);
    }

    public function indexByUser() {
        $user = JWTAuth::parseToken()->authenticate();
        $perikanan = Perikanan::where('kecamatan', $user->kecamatan)->get();
        return response()->json($perikanan);
    }

    public function indexByKecamatan($komoditi) {
        $data = Perikanan::where('komoditi', $komoditi)
                        ->select('kecamatan',
                        DB::raw('SUM(volume) as total_volume'),
                        DB::raw('SUM(nilai_produksi) as total_nilai_produksi')
                    )
                        ->groupBy('kecamatan')
                        ->get();

        return response()->json($data);
    }

    public function indexByYear($year) {
        $data = Perikanan::whereYear('updated_at', $year)
                        ->select('komoditi','kecamatan',
                        DB::raw('SUM(volume) as total_volume'),
                        DB::raw('SUM(nilai_produksi) as total_nilai_produksi')
                    )
                        ->groupBy('komoditi', 'kecamatan')
                        ->get();

        return response()->json($data);
    }

    protected $komoditi;

    public function downloadExcel($komoditi) {
        $filename = $komoditi . '_data.xlsx';
        return Excel::download($this, $filename);
    }

    public function collection() {
        return Perikanan::where('komoditi', $this->komoditi)
        ->select('kecamatan',
        DB::raw('SUM(volume) as total_volume'),
        DB::raw('SUM(nilai_produksi) as total_nilai_produksi')
    )
        ->groupBy('kecamatan')
        ->get();
    }

    public function headings(): array {
        return [
            'Kecamatan',
            'Volume',
            'Nilai Produksi'
        ];
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'komoditi' => 'required|string|max:50',
            'volume' => 'required|numeric|between:0,999999999.99',
            'nilai_produksi' => 'required|integer|min:0'
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        $perikanan = new Perikanan();
        $perikanan->id = uniqid();
        $perikanan->user_id = $user->id;
        $perikanan->kecamatan = $user->kecamatan;
        $perikanan->komoditi = $validateData['komoditi'];
        $perikanan->volume = $validateData['volume'];
        $perikanan->nilai_produksi = $validateData['nilai_produksi'];
        $perikanan->save();

        return response()->json([
            'message' => 'Data perikanan berhasil ditambahkan',
            'perikanan' => $perikanan
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $perikanan = Perikanan::find($id);
        if ($perikanan) {
            return response()->json([
                'success' => true,
                'perikanan' => $perikanan
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'Data Perikanan tidak ditemukan'
        ], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'komoditi' => 'required|string|max:50',
            'volume' => 'required|numeric|between:0,999999999.99',
            'nilai_produksi' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }

        $perikanan = Perikanan::findOrFail($id);
        $perikanan->komoditi = $request->input('komoditi');
        $perikanan->volume = $request->input('volume');
        $perikanan->nilai_produksi = $request->input('nilai_produksi');
        $perikanan->save();

        return response()->json([
            'message' => 'Data perikanan berhasil diubah',
            'perikanan' => $perikanan
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $perikanan = Perikanan::findOrFail($id);
        $perikanan->delete();

        return response()->json([
            'message' => 'Data perikanan berhasil dihapus'
        ]);
    }
}
