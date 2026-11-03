
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pasien Klinik Hewan</title>
</head>
<body>

    <h1>Sistem Pasien Klinik Hewan</h1>
    <p>Aplikasi Pendataan Pasien Klinik Hewan</p>

    {{-- Pesan berhasil --}}
    @if(session('success'))
        <p>{{ session('success') }}</p>
    @endif

    {{-- Pesan gagal --}}
    @if(session('error'))
        <p>{{ session('error') }}</p>
    @endif

    {{-- Pesan validasi --}}
    @if($errors->any())
        <strong>Terjadi kesalahan:</strong>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <hr>

    {{-- Form tambah atau edit --}}
    @php
        $isEdit = $mode === 'edit';
    @endphp

    <h2>
        {{ $isEdit ? 'Edit Data Pasien' : 'Tambah Data Pasien' }}
    </h2>

    <form action="{{ $isEdit ? '/pasien-hewan/'.$pasienEdit->id : '/pasien-hewan' }}"
          method="POST">

        @csrf

        @if($isEdit)
            @method('PUT')
        @endif

        <p>
            <label>Kode Pasien:</label><br>
            <input type="text" name="kode_pasien"
                value="{{ old('kode_pasien', $isEdit ? $pasienEdit->kode_pasien : '') }}"
                maxlength="10" required>
        </p>

        <p>
            <label>Nama Hewan:</label><br>
            <input type="text" name="nama_hewan"
                value="{{ old('nama_hewan', $isEdit ? $pasienEdit->nama_hewan : '') }}"
                maxlength="100" required>
        </p>

        <p>
            <label>Nomor Rekam Medis:</label><br>
            <input type="text" name="nomor_rekam"
                value="{{ old('nomor_rekam', $isEdit ? $pasienEdit->nomor_rekam : '') }}"
                maxlength="30" required>
        </p>

        <p>
            <label>Usia Hewan (Tahun):</label><br>
            <input type="number" name="usia_tahun"
                value="{{ old('usia_tahun', $isEdit ? $pasienEdit->usia_tahun : '') }}"
                min="0" max="30" required>
        </p>

        <p>
            <label>Jenis Hewan:</label><br>
            <select name="jenis_hewan_id" required>
                <option value="">-- Pilih Jenis Hewan --</option>

                @foreach($jenis as $j)
                    <option value="{{ $j->id }}"
                        {{ (string) old('jenis_hewan_id', $isEdit ? $pasienEdit->jenis_hewan_id : '') === (string) $j->id ? 'selected' : '' }}>
                        {{ $j->nama_jenis }}
                    </option>
                @endforeach
            </select>
        </p>

        <button type="submit">
            {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Pasien' }}
        </button>

        @if($isEdit)
            <a href="/pasien-hewan">Batal</a>
        @endif

    </form>

    <hr>

    {{-- Pencarian dan filter --}}
    <h2>Daftar Pasien Hewan</h2>

    <form action="/pasien-hewan" method="GET">

        <p>
            <label>Pencarian:</label><br>
            <input type="text" name="cari"
                value="{{ request('cari') }}"
                placeholder="Cari kode, nama, atau nomor rekam">
        </p>

        <p>
            <label>Filter Jenis Hewan:</label><br>
            <select name="jenis_hewan_id">

                <option value="">Semua Jenis Hewan</option>

                @foreach($jenis as $j)
                    <option value="{{ $j->id }}"
                        {{ request('jenis_hewan_id') == $j->id ? 'selected' : '' }}>
                        {{ $j->nama_jenis }}
                    </option>
                @endforeach

            </select>
        </p>

        <button type="submit">Cari / Filter</button>
        <a href="/pasien-hewan">Reset</a>

    </form>

    <hr>

    {{-- Tabel pasien --}}
    <table border="1" cellpadding="8" cellspacing="0">

        <thead>
            <tr>
                <th>No.</th>
                <th>Kode Pasien</th>
                <th>Nama Hewan</th>
                <th>Nomor Rekam</th>
                <th>Usia</th>
                <th>Jenis Hewan</th>
                <th>Status Usia</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>

            @forelse($pasien as $index => $data)

                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $data->kode_pasien }}</td>
                    <td>{{ $data->nama_hewan }}</td>
                    <td>{{ $data->nomor_rekam }}</td>
                    <td>{{ $data->usia_tahun }} tahun</td>
                    <td>{{ $data->nama_jenis }}</td>

                    <td>
                        {{ statusUsiaHewan($data->usia_tahun) }}
                    </td>

                    <td>
                        <a href="/pasien-hewan/{{ $data->id }}/edit">
                            Edit
                        </a>

                        |

                        <form action="/pasien-hewan/{{ $data->id }}"
                              method="POST" style="display:inline">

                            @csrf
                            @method('DELETE')

                            <button type="submit">Hapus</button>

                        </form>
                    </td>
                </tr>

            @empty

                <tr>
                    <td colspan="8">Belum ada data pasien.</td>
                </tr>

            @endforelse

        </tbody>

    </table>

</body>
</html>
