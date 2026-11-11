<?php
declare(strict_types=1);

abstract class Mahasiswa {
    private string $nim;
    private string $nama;
    private string $prodi;
    private int $semester;

    public function __construct(string $nim, string $nama,
                                string $prodi, int $semester) {
        if (trim($nim) === '' || trim($nama) === '' || trim($prodi) === '') {
            throw new InvalidArgumentException('Identitas wajib diisi');
        }
        if ($semester < 1 || $semester > 14) {
            throw new InvalidArgumentException('Semester harus 1–14');
        }
        $this->nim = trim($nim);
        $this->nama = trim($nama);
        $this->prodi = trim($prodi);
        $this->semester = $semester;
    }

    public function getNim(): string { return $this->nim; }
    public function getNama(): string { return $this->nama; }
    public function getProdi(): string { return $this->prodi; }
    public function getSemester(): int { return $this->semester; }

    abstract public function hitungBiaya(): int;
    abstract public function getJenis(): string;
}
