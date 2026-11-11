<?php
require_once __DIR__ . '/DataMahasiswa.php'; // Definisi class sebelum session_start
session_start();

function aman(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$repo = new DataMahasiswa($_SESSION['mahasiswa'] ?? []);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $aksi = (string)($_POST['aksi'] ?? 'tambah');

        if ($aksi === 'hapus') {
            // Penghapusan: POST, validasi NIM, panggil hapus(), perbarui session
            $nimHapus = trim((string)($_POST['nim'] ?? ''));
            if (!$repo->hapus($nimHapus)) {
                throw new InvalidArgumentException('Data yang dihapus tidak ditemukan');
            }
            $_SESSION['mahasiswa'] = $repo->semua();
            header('Location: index.php'); exit;
        }

        $nim      = trim((string)($_POST['nim'] ?? ''));
        $nama     = trim((string)($_POST['nama'] ?? ''));
        $prodi    = trim((string)($_POST['prodi'] ?? ''));
        $semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT);
        $ukt      = filter_var($_POST['ukt'] ?? null, FILTER_VALIDATE_INT);

        if ($semester === false || $ukt === false) {
            throw new InvalidArgumentException('Semester dan UKT harus bilangan bulat');
        }

        $jenis = (string)($_POST['jenis'] ?? '');
        if ($jenis === 'reguler') {
            $m = new MahasiswaReguler($nim, $nama, $prodi, $semester, $ukt);
        } elseif ($jenis === 'beasiswa') {
            $potongan = filter_var($_POST['potongan'] ?? null, FILTER_VALIDATE_INT);
            if ($potongan === false) throw new InvalidArgumentException('Potongan tidak valid');
            $m = new MahasiswaBeasiswa($nim, $nama, $prodi, $semester, $ukt, $potongan);
        } else {
            throw new InvalidArgumentException('Jenis mahasiswa tidak dikenal');
        }

        $ok = $aksi === 'ubah'
            ? $repo->ubah((string)($_POST['nim_lama'] ?? ''), $m)
            : $repo->tambah($m);

        if (!$ok) throw new InvalidArgumentException('NIM duplikat atau data edit tidak cocok');

        $_SESSION['mahasiswa'] = $repo->semua();
        header('Location: index.php'); exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
}

// Mode edit: isi form dari data yang dipilih (NIM tidak bisa diubah)
$edit = null;
if (isset($_GET['edit'])) {
    $edit = $repo->cari((string)$_GET['edit']);
}

// Pencarian berdasarkan NIM atau nama
$q = trim((string)($_GET['q'] ?? ''));
$daftar = array_filter($repo->semua(), function (Mahasiswa $m) use ($q): bool {
    if ($q === '') return true;
    return stripos($m->getNim(), $q) !== false || stripos($m->getNama(), $q) !== false;
});

// Nilai awal form (dari data edit, atau dari input terakhir jika error)
$isEdit = $edit !== null;
$fNim   = $isEdit ? $edit->getNim() : ($_POST['nim'] ?? '');
$fNama  = $isEdit ? $edit->getNama() : ($_POST['nama'] ?? '');
$fProdi = $isEdit ? $edit->getProdi() : ($_POST['prodi'] ?? '');
$fSem   = $isEdit ? $edit->getSemester() : ($_POST['semester'] ?? '');
$fUkt   = $isEdit ? $edit->getUkt() : ($_POST['ukt'] ?? '');
$fJenis = $isEdit ? strtolower($edit->getJenis()) : ($_POST['jenis'] ?? 'reguler');
$fPot   = ($isEdit && $edit instanceof MahasiswaBeasiswa) ? $edit->getPotonganPersen() : ($_POST['potongan'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Mahasiswa — Inheritance PHP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --petrol: #0b5d6b;
            --petrol-dark: #08424d;
            --petrol-soft: #dcecee;
            --gold: #e3a82b;
            --gold-soft: #fbf0d3;
            --ink: #14262b;
            --muted: #62777c;
            --line: #d6e2e4;
            --bg: #eef4f4;
            --surface: #ffffff;
            --danger: #b3261e;
            --danger-soft: #fdecea;
            --radius: 14px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 380px at 100% -10%, rgba(227,168,43,.16), transparent 60%),
                var(--bg);
            line-height: 1.55;
        }

        /* ---------- Header ---------- */
        .top {
            background: var(--petrol-dark);
            color: #fff;
            padding: 34px 20px 78px;
        }
        .top-inner { max-width: 1120px; margin: 0 auto; }
        .top h1 {
            margin: 0 0 6px;
            font-size: clamp(1.6rem, 3.2vw, 2.3rem);
            font-weight: 800;
            letter-spacing: -.02em;
        }
        .top p { margin: 0; color: #b9d4d8; max-width: 60ch; }

        /* ---------- Layout ---------- */
        .wrap {
            max-width: 1120px;
            margin: -52px auto 40px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 370px 1fr;
            gap: 22px;
            align-items: start;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: 0 10px 30px -18px rgba(8,66,77,.45);
        }
        .form-card { padding: 24px; position: sticky; top: 16px; }
        .form-card h2, .table-head h2 { margin: 0; font-size: 1.15rem; font-weight: 700; }
        .form-card .sub { margin: 4px 0 18px; color: var(--muted); font-size: .88rem; }

        /* ---------- Form ---------- */
        .field { margin-bottom: 14px; }
        .field label, .legend {
            display: block;
            margin-bottom: 6px;
            font-size: .85rem;
            font-weight: 600;
        }
        .field input[type=text], .field input[type=number] {
            width: 100%;
            padding: 11px 13px;
            font: inherit;
            color: var(--ink);
            background: #f8fbfb;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .field input::placeholder { color: #9bb0b4; }
        .field input:hover { border-color: #b5cacd; }
        .field input:focus {
            outline: none;
            background: #fff;
            border-color: var(--petrol);
            box-shadow: 0 0 0 4px rgba(11,93,107,.14);
        }
        .field input[readonly] {
            background: var(--petrol-soft);
            color: var(--petrol-dark);
            font-weight: 600;
            cursor: not-allowed;
        }
        .hint { margin: 5px 0 0; color: var(--muted); font-size: .78rem; }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        /* Pilihan jenis (segmented control) */
        .seg {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            padding: 4px;
            background: var(--petrol-soft);
            border-radius: 12px;
        }
        .seg input { position: absolute; opacity: 0; pointer-events: none; }
        .seg label {
            margin: 0;
            padding: 10px 8px;
            text-align: center;
            font-weight: 600;
            font-size: .9rem;
            color: var(--petrol-dark);
            border-radius: 9px;
            cursor: pointer;
            transition: background .15s, color .15s, box-shadow .15s;
        }
        .seg input:checked + label {
            background: var(--petrol);
            color: #fff;
            box-shadow: 0 4px 10px -4px rgba(8,66,77,.6);
        }
        .seg input:focus-visible + label { outline: 3px solid var(--gold); outline-offset: 2px; }

        #box-potongan[hidden] { display: none; }

        /* Perkiraan biaya (elemen utama form) */
        .biaya {
            margin: 6px 0 18px;
            padding: 14px 16px;
            background: var(--gold-soft);
            border: 1.5px dashed var(--gold);
            border-radius: 12px;
        }
        .biaya small { display: block; color: #7a5a0c; font-weight: 600; font-size: .8rem; }
        .biaya strong { font-size: 1.55rem; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
        .biaya span { display: block; margin-top: 2px; color: #7a5a0c; font-size: .78rem; }

        .actions { display: flex; gap: 10px; align-items: center; }
        .btn {
            font: inherit;
            font-weight: 700;
            border: 0;
            border-radius: 10px;
            padding: 12px 18px;
            cursor: pointer;
            transition: transform .1s, background .15s, box-shadow .15s;
        }
        .btn:active { transform: translateY(1px); }
        .btn:focus-visible { outline: 3px solid var(--gold); outline-offset: 2px; }
        .btn-primary { flex: 1; background: var(--petrol); color: #fff; box-shadow: 0 8px 18px -10px var(--petrol); }
        .btn-primary:hover { background: var(--petrol-dark); }
        .btn-ghost { background: transparent; color: var(--muted); text-decoration: none; padding: 12px 8px; }
        .btn-ghost:hover { color: var(--ink); }

        .alert {
            display: flex; gap: 10px;
            margin-bottom: 16px;
            padding: 12px 14px;
            background: var(--danger-soft);
            color: var(--danger);
            border-left: 4px solid var(--danger);
            border-radius: 8px;
            font-size: .9rem;
            font-weight: 500;
        }

        /* ---------- Tabel ---------- */
        .table-card { padding: 22px; }
        .table-head {
            display: flex; flex-wrap: wrap; gap: 12px;
            align-items: center; justify-content: space-between;
            margin-bottom: 16px;
        }
        .count {
            display: inline-block; margin-left: 8px;
            padding: 2px 10px;
            background: var(--petrol-soft); color: var(--petrol-dark);
            border-radius: 999px; font-size: .78rem; font-weight: 700; vertical-align: middle;
        }
        .search { display: flex; gap: 8px; }
        .search input {
            padding: 9px 13px; width: 220px; font: inherit;
            border: 1.5px solid var(--line); border-radius: 10px; background: #f8fbfb;
        }
        .search input:focus { outline: none; border-color: var(--petrol); box-shadow: 0 0 0 4px rgba(11,93,107,.14); background: #fff; }
        .search .btn { padding: 9px 16px; background: var(--petrol-soft); color: var(--petrol-dark); }
        .search .btn:hover { background: #c9e0e3; }

        .scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 640px; }
        th {
            text-align: left; padding: 10px 12px;
            font-size: .8rem; font-weight: 700; color: var(--muted);
            border-bottom: 2px solid var(--line);
        }
        td { padding: 13px 12px; border-bottom: 1px solid var(--line); vertical-align: middle; font-size: .92rem; }
        tbody tr:hover { background: #f5fafa; }
        .nama { font-weight: 700; }
        .nim { color: var(--muted); font-size: .8rem; }
        .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }

        .badge {
            display: inline-block; padding: 3px 11px;
            border-radius: 999px; font-size: .78rem; font-weight: 700;
        }
        .badge.reguler  { background: var(--petrol-soft); color: var(--petrol-dark); }
        .badge.beasiswa { background: var(--gold-soft); color: #7a5a0c; }

        .aksi { display: flex; gap: 6px; justify-content: flex-end; }
        .aksi form { margin: 0; }
        .mini {
            font: inherit; font-size: .8rem; font-weight: 600;
            padding: 6px 12px; border-radius: 8px; border: 1.5px solid var(--line);
            background: #fff; color: var(--ink); cursor: pointer; text-decoration: none; display: inline-block;
        }
        .mini:hover { border-color: var(--petrol); color: var(--petrol); }
        .mini.hapus:hover { border-color: var(--danger); color: var(--danger); background: var(--danger-soft); }

        .kosong { text-align: center; padding: 44px 12px !important; color: var(--muted); }
        .kosong b { display: block; color: var(--ink); font-size: 1rem; margin-bottom: 4px; }

        @media (max-width: 880px) {
            .wrap { grid-template-columns: 1fr; margin-top: -48px; }
            .form-card { position: static; }
            .search input { width: 100%; }
            .search { width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>
<body>

<header class="top">
    <div class="top-inner">
        <h1>Data mahasiswa</h1>
        <p>Praktikum PBO Pertemuan 06: satu parent class Mahasiswa, dua child dengan aturan biaya berbeda.</p>
    </div>
</header>

<main class="wrap">

    <!-- ===== Form ===== -->
    <section class="card form-card">
        <h2><?= $isEdit ? 'Edit mahasiswa' : 'Tambah mahasiswa' ?></h2>
        <p class="sub"><?= $isEdit ? 'NIM dikunci. Jenis dan data lain boleh diubah.' : 'Isi data, lalu pilih jenis mahasiswa.' ?></p>

        <?php if ($error !== ''): ?>
            <div class="alert" role="alert"><?= aman($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="aksi" value="<?= $isEdit ? 'ubah' : 'tambah' ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="nim_lama" value="<?= aman($edit->getNim()) ?>">
            <?php endif; ?>

            <div class="field">
                <label for="nim">NIM</label>
                <input type="text" id="nim" name="nim" placeholder="Contoh: 23001"
                       value="<?= aman($fNim) ?>" <?= $isEdit ? 'readonly' : '' ?>>
            </div>

            <div class="field">
                <label for="nama">Nama lengkap</label>
                <input type="text" id="nama" name="nama" placeholder="Contoh: Siti Aisyah" value="<?= aman($fNama) ?>">
            </div>

            <div class="row2">
                <div class="field">
                    <label for="prodi">Program studi</label>
                    <input type="text" id="prodi" name="prodi" placeholder="Informatika" value="<?= aman($fProdi) ?>">
                </div>
                <div class="field">
                    <label for="semester">Semester</label>
                    <input type="number" id="semester" name="semester" min="1" max="14" placeholder="1–14" value="<?= aman($fSem) ?>">
                </div>
            </div>

            <div class="field">
                <span class="legend">Jenis mahasiswa</span>
                <div class="seg">
                    <input type="radio" name="jenis" id="j-reguler" value="reguler" <?= $fJenis !== 'beasiswa' ? 'checked' : '' ?>>
                    <label for="j-reguler">Reguler</label>
                    <input type="radio" name="jenis" id="j-beasiswa" value="beasiswa" <?= $fJenis === 'beasiswa' ? 'checked' : '' ?>>
                    <label for="j-beasiswa">Beasiswa</label>
                </div>
            </div>

            <div class="field">
                <label for="ukt">UKT (Rp)</label>
                <input type="number" id="ukt" name="ukt" min="0" step="1" placeholder="5000000" value="<?= aman($fUkt) ?>">
            </div>

            <div class="field" id="box-potongan" hidden>
                <label for="potongan">Potongan beasiswa (%)</label>
                <input type="number" id="potongan" name="potongan" min="0" max="100" placeholder="0–100" value="<?= aman($fPot) ?>">
                <p class="hint">Isi 0 sampai 100.</p>
            </div>

            <div class="biaya" aria-live="polite">
                <small>Perkiraan biaya akhir</small>
                <strong id="biaya-angka">Rp0</strong>
                <span id="biaya-ket">Sama dengan UKT penuh.</span>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Simpan perubahan' : 'Tambah mahasiswa' ?></button>
                <?php if ($isEdit): ?><a href="index.php" class="btn btn-ghost">Batal</a><?php endif; ?>
            </div>
        </form>
    </section>

    <!-- ===== Tabel ===== -->
    <section class="card table-card">
        <div class="table-head">
            <h2>Daftar mahasiswa <span class="count"><?= count($daftar) ?></span></h2>
            <form method="get" class="search" role="search">
                <input type="text" name="q" placeholder="Cari NIM atau nama" value="<?= aman($q) ?>" aria-label="Cari NIM atau nama">
                <button type="submit" class="btn">Cari</button>
            </form>
        </div>

        <div class="scroll">
            <table>
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Prodi</th>
                        <th>Sem</th>
                        <th>Jenis</th>
                        <th class="num">Biaya akhir</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($daftar as $m): ?>
                    <tr>
                        <td>
                            <div class="nama"><?= aman($m->getNama()) ?></div>
                            <div class="nim"><?= aman($m->getNim()) ?></div>
                        </td>
                        <td><?= aman($m->getProdi()) ?></td>
                        <td><?= aman($m->getSemester()) ?></td>
                        <td><span class="badge <?= aman(strtolower($m->getJenis())) ?>"><?= aman($m->getJenis()) ?></span></td>
                        <td class="num">Rp<?= aman(number_format($m->hitungBiaya(), 0, ',', '.')) ?></td>
                        <td>
                            <div class="aksi">
                                <a class="mini" href="index.php?edit=<?= urlencode($m->getNim()) ?>">Edit</a>
                                <form method="post" onsubmit="return confirm('Hapus data <?= aman($m->getNama()) ?>?')">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="nim" value="<?= aman($m->getNim()) ?>">
                                    <button type="submit" class="mini hapus">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($daftar) === 0): ?>
                    <tr><td colspan="6" class="kosong">
                        <b><?= $q !== '' ? 'Tidak ada hasil' : 'Belum ada data mahasiswa' ?></b>
                        <?= $q !== '' ? 'Coba kata kunci lain atau hapus pencarian.' : 'Isi form di samping untuk menambah mahasiswa pertama.' ?>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    const rupiah = n => 'Rp' + n.toLocaleString('id-ID');
    const radios  = document.querySelectorAll('input[name="jenis"]');
    const box     = document.getElementById('box-potongan');
    const ukt     = document.getElementById('ukt');
    const pot     = document.getElementById('potongan');
    const angka   = document.getElementById('biaya-angka');
    const ket     = document.getElementById('biaya-ket');

    function hitung() {
        const beasiswa = document.getElementById('j-beasiswa').checked;
        box.hidden = !beasiswa;

        const u = parseInt(ukt.value, 10);
        if (isNaN(u) || u < 0) {
            angka.textContent = 'Rp0';
            ket.textContent = 'Isi UKT untuk melihat perkiraan.';
            return;
        }
        if (!beasiswa) {
            angka.textContent = rupiah(u);
            ket.textContent = 'Sama dengan UKT penuh.';
            return;
        }
        const p = parseInt(pot.value, 10);
        if (isNaN(p) || p < 0 || p > 100) {
            angka.textContent = rupiah(u);
            ket.textContent = 'Isi potongan 0–100% untuk menghitung beasiswa.';
            return;
        }
        angka.textContent = rupiah(Math.floor(u * (100 - p) / 100));
        ket.textContent = 'UKT dipotong ' + p + '%.';
    }

    radios.forEach(r => r.addEventListener('change', hitung));
    ukt.addEventListener('input', hitung);
    pot.addEventListener('input', hitung);
    hitung();
</script>
</body>
</html>
