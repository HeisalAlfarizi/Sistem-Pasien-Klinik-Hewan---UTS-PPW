
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

// Fungsi menentukan status usia hewan
if (!function_exists('statusUsiaHewan')) {
    function statusUsiaHewan($usia)
    {
        return $usia >= 3 ? 'Dewasa' : 'Muda';
    }
}

// Mengambil koneksi PDO dari Laravel
$getPdo = function () {
    $pdo = DB::connection()->getPdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
};

// Validasi, sanitasi, dan pengecekan data
$validasiPasien = function (Request $request, PDO $pdo, $id = null) {

    $data = [
        'kode_pasien' => trim(strip_tags($request->input('kode_pasien', ''))),
        'nama_hewan' => trim(strip_tags($request->input('nama_hewan', ''))),
        'nomor_rekam' => trim(strip_tags($request->input('nomor_rekam', ''))),
        'usia_tahun' => $request->input('usia_tahun'),
        'jenis_hewan_id' => $request->input('jenis_hewan_id')
    ];

    $validator = Validator::make($data, [
        'kode_pasien' => ['required', 'regex:/^[A-Za-z0-9]{10}$/'],
        'nama_hewan' => ['required', 'string', 'min:2', 'max:100'],
        'nomor_rekam' => ['required', 'string', 'min:5', 'max:30'],
        'usia_tahun' => ['required', 'integer', 'between:0,30'],
        'jenis_hewan_id' => ['required', 'integer']
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    // Memastikan jenis hewan tersedia
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM jenis_hewan WHERE id = ?"
    );
    $stmt->execute([(int) $data['jenis_hewan_id']]);

    if ($stmt->fetchColumn() == 0) {
        return back()->withErrors([
            'jenis_hewan_id' => 'Jenis hewan tidak tersedia.'
        ])->withInput();
    }

    // Memeriksa kode pasien yang sudah digunakan
    $sql = "SELECT COUNT(*) FROM pasien_hewan WHERE kode_pasien = ?";
    $params = [$data['kode_pasien']];

    if ($id !== null) {
        $sql .= " AND id <> ?";
        $params[] = $id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->fetchColumn() > 0) {
        return back()->withErrors([
            'kode_pasien' => 'Kode pasien sudah digunakan.'
        ])->withInput();
    }

    // Memeriksa nomor rekam medis yang sudah digunakan
    $sql = "SELECT COUNT(*) FROM pasien_hewan WHERE nomor_rekam = ?";
    $params = [$data['nomor_rekam']];

    if ($id !== null) {
        $sql .= " AND id <> ?";
        $params[] = $id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->fetchColumn() > 0) {
        return back()->withErrors([
            'nomor_rekam' => 'Nomor rekam medis sudah digunakan.'
        ])->withInput();
    }

    return $data;
};

// ROUTE 1: Menampilkan data pasien, pencarian, dan filter
Route::get('/pasien-hewan', function (Request $request) use ($getPdo) {

    try {
        $pdo = $getPdo();

        $sql = "SELECT p.*, j.nama_jenis
                FROM pasien_hewan p
                JOIN jenis_hewan j ON p.jenis_hewan_id = j.id";

        $conditions = [];
        $params = [];

        // Pencarian berdasarkan tiga kolom
        if ($request->filled('cari')) {
            $conditions[] = "(p.kode_pasien LIKE ?
                OR p.nama_hewan LIKE ?
                OR p.nomor_rekam LIKE ?)";

            $keyword = '%' . trim(strip_tags($request->cari)) . '%';

            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        // Filter berdasarkan jenis hewan
        if ($request->filled('jenis_hewan_id')) {
            $conditions[] = "p.jenis_hewan_id = ?";
            $params[] = (int) $request->jenis_hewan_id;
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY p.id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pasien = $stmt->fetchAll(PDO::FETCH_OBJ);

        $stmt = $pdo->prepare(
            "SELECT * FROM jenis_hewan ORDER BY nama_jenis"
        );
        $stmt->execute();
        $jenis = $stmt->fetchAll(PDO::FETCH_OBJ);

        return view('pasien-hewan', [
            'pasien' => $pasien,
            'jenis' => $jenis,
            'mode' => 'tambah'
        ]);

    } catch (Throwable $e) {
        report($e);

        return response(
            'Data pasien gagal ditampilkan. Silakan coba kembali.',
            500
        );
    }
});

// ROUTE 2: Menambahkan pasien
Route::post('/pasien-hewan', function (Request $request) use ($getPdo, $validasiPasien) {

    try {
        $pdo = $getPdo();

        $data = $validasiPasien($request, $pdo);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO pasien_hewan
            (kode_pasien, nama_hewan, nomor_rekam,
            usia_tahun, jenis_hewan_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
        );

        $stmt->execute([
            $data['kode_pasien'],
            $data['nama_hewan'],
            $data['nomor_rekam'],
            $data['usia_tahun'],
            $data['jenis_hewan_id']
        ]);

        return redirect('/pasien-hewan')
            ->with('success', 'Data pasien berhasil ditambahkan.');

    } catch (Throwable $e) {
        report($e);

        return back()->withInput()
            ->with('error', 'Data pasien gagal ditambahkan.');
    }
});

// ROUTE 3: Menampilkan form edit
Route::get('/pasien-hewan/{id}/edit', function ($id) use ($getPdo) {

    try {
        $pdo = $getPdo();

        $stmt = $pdo->prepare(
            "SELECT * FROM pasien_hewan WHERE id = ?"
        );
        $stmt->execute([$id]);

        $pasienEdit = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$pasienEdit) {
            abort(404);
        }

        $stmt = $pdo->prepare(
            "SELECT * FROM pasien_hewan p
            JOIN jenis_hewan j ON p.jenis_hewan_id = j.id
            ORDER BY p.id ASC"
        );
        $stmt->execute();
        $pasien = $stmt->fetchAll(PDO::FETCH_OBJ);

        $stmt = $pdo->prepare(
            "SELECT * FROM jenis_hewan ORDER BY nama_jenis"
        );
        $stmt->execute();
        $jenis = $stmt->fetchAll(PDO::FETCH_OBJ);

        return view('pasien-hewan', [
            'pasien' => $pasien,
            'jenis' => $jenis,
            'pasienEdit' => $pasienEdit,
            'mode' => 'edit'
        ]);

    } catch (Throwable $e) {
        report($e);

        return redirect('/pasien-hewan')
            ->with('error', 'Form edit gagal ditampilkan.');
    }
});

// ROUTE 4: Memperbarui data pasien
Route::put('/pasien-hewan/{id}', function (Request $request, $id) use ($getPdo, $validasiPasien) {

    try {
        $pdo = $getPdo();

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM pasien_hewan WHERE id = ?"
        );
        $stmt->execute([$id]);

        if ($stmt->fetchColumn() == 0) {
            abort(404);
        }

        $data = $validasiPasien($request, $pdo, $id);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $stmt = $pdo->prepare(
            "UPDATE pasien_hewan
            SET kode_pasien = ?, nama_hewan = ?,
            nomor_rekam = ?, usia_tahun = ?,
            jenis_hewan_id = ?, updated_at = NOW()
            WHERE id = ?"
        );

        $stmt->execute([
            $data['kode_pasien'],
            $data['nama_hewan'],
            $data['nomor_rekam'],
            $data['usia_tahun'],
            $data['jenis_hewan_id'],
            $id
        ]);

        return redirect('/pasien-hewan')
            ->with('success', 'Data pasien berhasil diperbarui.');

    } catch (Throwable $e) {
        report($e);

        return back()->withInput()
            ->with('error', 'Data pasien gagal diperbarui.');
    }
});

// ROUTE 5: Menghapus data pasien
Route::delete('/pasien-hewan/{id}', function ($id) use ($getPdo) {

    try {
        $pdo = $getPdo();

        $stmt = $pdo->prepare(
            "DELETE FROM pasien_hewan WHERE id = ?"
        );

        $stmt->execute([$id]);

        if ($stmt->rowCount() == 0) {
            return redirect('/pasien-hewan')
                ->with('error', 'Data pasien tidak ditemukan.');
        }

        return redirect('/pasien-hewan')
            ->with('success', 'Data pasien berhasil dihapus.');

    } catch (Throwable $e) {
        report($e);

        return redirect('/pasien-hewan')
            ->with('error', 'Data pasien gagal dihapus.');
    }
});
