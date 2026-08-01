# QC and Security Hardening Design

## Goal

Menutup defect keamanan dan konsistensi yang ditemukan selama QC tanpa mengubah alur bisnis atau tampilan di luar area yang terdampak.

## Scope

### Authorization

- Hanya Admin yang dapat membuat, mengubah, menghapus, dan memasangkan role.
- Hanya role manajemen yang sudah diizinkan oleh aplikasi yang dapat mengubah data user; input update dibatasi pada field tervalidasi dan tidak menggunakan seluruh request.
- Operasi Shift `store`, `update`, dan `destroy` wajib menggunakan permission yang sama dengan halaman terkait.
- Akses Work Order mengikuti aturan listing: Admin dapat mengakses semua data, leader hanya data yang dia submit, dan staff hanya data yang memasukkan dirinya sebagai anggota.
- Import, upload foto, delete, dan export Work Order dibatasi sesuai kemampuan role dan kepemilikan record.
- Pengelolaan certificate admin hanya dapat dilakukan Admin.

### Optional Work Order Number

- Input `wo_number` tetap opsional; nilai kosong atau whitespace disimpan sebagai `null`.
- Semua tampilan HTML dan PDF menampilkan `-` ketika nomor tidak tersedia.
- Nama file dan referensi dokumen menggunakan fallback `Assignment-{id}` agar tidak menghasilkan nama kosong.
- Migration nullable berjalan pada fresh MySQL/SQLite dan pada database existing yang sudah memakai nama tabel `assignments`.
- Rollback mengubah nilai `null` menjadi string kosong sebelum kolom dikembalikan menjadi non-nullable.

### Error Handling and Transport Security

- Kegagalan import staff dicatat ke log tanpa menampilkan stack trace kepada pengguna.
- Request Flightradar24 tidak menonaktifkan verifikasi TLS. Kegagalan upstream tetap menggunakan fallback database/manual yang sudah ada.

### Dependencies and Build

- Dependency PHP diperbarui ke versi kompatibel terbaru yang menutup advisory critical/high, dengan perhatian khusus pada Laravel, PhpSpreadsheet, Dompdf, Symfony, dan Guzzle.
- Dependency frontend diinstal ulang secara reproducible agar binary Rollup sesuai platform tersedia.
- Perubahan dependency tidak boleh menaikkan major version framework atau mengubah API bisnis.

## Architecture

Authorization ditempatkan pada controller action atau helper private yang dipanggil sebelum query dan mutasi. Work Order memakai satu aturan akses record yang konsisten agar index, show, export, upload, dan delete tidak berbeda perilaku. Input sensitif memakai hasil validasi eksplisit, bukan `$request->all()`.

Perubahan presentasi WO kosong dipusatkan pada accessor model `wo_number_label`. Fallback nama dokumen dibuat dari nomor WO jika ada dan `Assignment-{id}` jika tidak ada. Migration mendeteksi nama tabel yang benar pada saat dijalankan sehingga kompatibel dengan urutan deployment lama dan fresh install.

## Data and Request Flow

1. Middleware `auth` memastikan pengguna login.
2. Controller memeriksa role/permission sebelum membaca atau mengubah resource.
3. Request divalidasi dan hanya field tervalidasi diteruskan ke model.
4. Untuk resource terikat pengguna, query dibatasi terlebih dahulu lalu menggunakan `findOrFail`, sehingga ID di URL tidak dapat melewati scope.
5. Error operasional dicatat secara internal dan pengguna menerima pesan generik yang dapat ditindaklanjuti.

## Testing

- Regression test membuktikan staff menerima 403 saat mengelola role, user lain, certificate admin, dan Shift tanpa permission.
- Test Work Order mencakup akses record milik orang lain, import/delete/upload tanpa hak, WO kosong/whitespace, label `-`, dan fallback filename.
- Migration diuji dengan fresh SQLite serta diperiksa terhadap urutan nama tabel untuk kompatibilitas MySQL.
- Verifikasi akhir menjalankan seluruh PHPUnit suite, PHP syntax check, Pint pada file yang berubah, Composer audit, npm audit, dan Vite production build.

## Non-Goals

- Tidak meredesain UI.
- Tidak memformat seluruh codebase atau menyelesaikan 76 style issue yang tidak terkait.
- Tidak mengganti sistem role berbasis string dengan package authorization baru.
- Tidak menghapus legacy route alias Work Result pada pekerjaan ini.
- Tidak melakukan push atau deployment.

## Acceptance Criteria

- Semua endpoint sensitif menolak pengguna yang tidak berhak dengan HTTP 403.
- Pengguna yang sah tetap dapat menjalankan alur yang sebelumnya tersedia.
- WO tanpa nomor dapat dibuat, ditampilkan, dan diekspor tanpa label atau filename kosong.
- Tidak ada `dd()` atau stack trace yang terlihat pada kegagalan import.
- Tidak ada TLS verification bypass pada integrasi flight.
- Seluruh regression test dan test existing lulus.
- Production frontend build berhasil.
- Tidak ada advisory critical/high yang masih dapat diperbaiki dalam batas versi kompatibel yang terkunci.
- Tidak ada push remote.
