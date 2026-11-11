<?php
require_once __DIR__ . '/Mahasiswa.php';

final class MahasiswaReguler extends Mahasiswa {
    private int $ukt;

    public function __construct(string $nim, string $nama,
                                string $prodi, int $semester, int $ukt) {
        parent::__construct($nim, $nama, $prodi, $semester);
        if ($ukt < 0) throw new InvalidArgumentException('UKT tidak boleh negatif');
        $this->ukt = $ukt;
    }

    public function getUkt(): int { return $this->ukt; }
    public function hitungBiaya(): int { return $this->ukt; }
    public function getJenis(): string { return 'Reguler'; }
}
