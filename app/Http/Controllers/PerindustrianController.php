<?php

namespace App\Http\Controllers;

use App\Models\Perindustrian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PerindustrianController extends Controller implements FromCollection, WithHeadings
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $kecamatan = $request->query('kecamatan');
        if ($kecamatan) {
            $perindustrian = Perindustrian::where('kecamatan', $kecamatan)->get();
            return response()->json($perindustrian);
        }
        $perindustrian = Perindustrian::all();
        return response()->json($perindustrian);
    }

    public function indexByUser() {
        $user = JWTAuth::parseToken()->authenticate();
        $perindustrian = Perindustrian::where('kecamatan', $user->kecamatan)->get();
        return response()->json($perindustrian);
    }

    public function indexByKecamatan($komoditi) {
        $data = Perindustrian::where('komoditi', $komoditi)
                        ->select('kecamatan',
                        DB::raw('SUM(potensi_kandungan) as total_potensi_kandungan'),
                    )
                        ->groupBy('kecamatan')
                        ->get();

        return response()->json($data);
    }

    public function indexByYear($year) {
        $data = Perindustrian::whereYear('updated_at', $year)
                        ->select('komoditi','kecamatan',
                        DB::raw('SUM(potensi_kandungan) as total_potensi_kandungan'),
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
        return Perindustrian::where('komoditi', $this->komoditi)
        ->select('kecamatan',
        DB::raw('SUM(potensi_kandungan) as total_potensi_kandungan'),
    )
        ->groupBy('kecamatan')
        ->get();
    }

    public function headings():array {
        return [
            'Kecamatan',
            'Potensi Kandungan'
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'komoditi' => 'required|string|max:50',
            'potensi_kandungan' => 'required|integer|min:0'
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        $perindustrian = new Perindustrian();
        $perindustrian->id = uniqid();
        $perindustrian->user_id = $user->id;
        $perindustrian->kecamatan = $user->kecamatan;
        $perindustrian->komoditi = $validateData['komoditi'];
        $perindustrian->potensi_kandungan = $validateData['potensi_kandungan'];
        $perindustrian->save();

        return response()->json([
            'message' => 'Data perindustrian berhasil ditambahkan',
            'perindustrian' => $perindustrian
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $perindustrian = Perindustrian::find($id);
        if ($perindustrian) {
            return response()->json([
                'success' => true,
                'perindustrian' => $perindustrian
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'Data Perindustrian tidak ditemukan'
        ], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'komoditi' => 'required|string|max:50',
            'potensi_kandungan' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }

        $perindustrian = Perindustrian::findOrFail($id);
        $perindustrian->komoditi = $request->input('komoditi');
        $perindustrian->potensi_kandungan = $request->input('potensi_kandungan');
        $perindustrian->save();

        return response()->json([
            'message' => 'Data perindustrian berhasil diubah',
            'perindustrian' => $perindustrian
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $perindustrian = Perindustrian::findOrFail($id);
        $perindustrian->delete();

        return response()->json([
            'message' => 'Data perindustrian berhasil dihapus'
        ]);
    }
}
