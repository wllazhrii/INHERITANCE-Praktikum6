<?php
require_once __DIR__ . '/DataMahasiswa.php';

$daftar = [
    new MahasiswaReguler('23001', 'Andi', 'Informatika', 4, 5000000),
    new MahasiswaBeasiswa('23002', 'Siti', 'Informatika', 4, 5000000, 60)
];
foreach ($daftar as $m) {
    echo $m->getNama() . ' / ' . $m->getJenis()
        . ' / Rp' . number_format($m->hitungBiaya(), 0, ',', '.') . PHP_EOL;
}
