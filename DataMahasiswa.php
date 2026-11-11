<?php
require_once __DIR__ . '/MahasiswaReguler.php';
require_once __DIR__ . '/MahasiswaBeasiswa.php';

class DataMahasiswa {
    /** @var array<string, Mahasiswa> */
    private array $data = [];

    public function __construct(array $awal = []) {
        foreach ($awal as $m) {
            if ($m instanceof Mahasiswa) $this->data[$m->getNim()] = $m;
        }
    }

    public function tambah(Mahasiswa $m): bool {
        if (isset($this->data[$m->getNim()])) return false;
        $this->data[$m->getNim()] = $m;
        return true;
    }

    public function cari(string $nim): ?Mahasiswa {
        return $this->data[$nim] ?? null;
    }

    /** @return array<string, Mahasiswa> */
    public function semua(): array { return $this->data; }

    public function ubah(string $nimLama, Mahasiswa $m): bool {
        if (!isset($this->data[$nimLama]) || $m->getNim() !== $nimLama) return false;
        $this->data[$nimLama] = $m;
        return true;
    }

    public function hapus(string $nim): bool {
        if (!isset($this->data[$nim])) return false;
        unset($this->data[$nim]);
        return true;
    }
}
