# Product Manager

Aplikasi web manajemen produk berbasis PHP dan MySQL yang menerapkan arsitektur Client-Server, operasi CRUD penuh, serta antarmuka yang fungsional, industrial, dan responsif.

## Fitur Utama
- **Create**: Menambah produk baru lengkap dengan validasi server-side dan unggah (upload) gambar.
- **Read**: Menampilkan daftar produk menggunakan antarmuka grid, dilengkapi fitur unggulan:
  - **Pencarian (Search)**: Berdasarkan nama atau kategori produk.
  - **Navigasi Kategori**: Filter cepat menggunakan tombol kategori yang dimuat dinamis dari database.
  - **Pagination**: Pembagian halaman daftar produk secara rapi.
  - **Sistem Status (Rhythm Break)**: Produk yang kehabisan stok (Stok = 0) akan disorot secara visual dengan peringatan khusus.
- **Update**: Form pengeditan yang terisi dengan data lama secara otomatis beserta dukungan penggantian gambar produk.
- **Delete**: Penghapusan produk melalui POST request, lengkap dengan logika penghapusan *file* gambar dari media penyimpanan server.

## Fitur UI/UX & Desain
Aplikasi ini didesain dengan konsep "Alat Pergudangan / Industrial Tool" yang mengutamakan fungsi dan kecepatan:
- **Desain Brutalist & High-Contrast**: Mengandalkan *hard shadow*, garis tegas, dan warna aksen utama *Safety Orange*.
- **Dark Mode**: Mendukung mode terang dan gelap dengan sebuah *toggle switch* (berupa ikon SVG khusus) di sudut kanan atas header. Menyimpan preferensi menggunakan *Local Storage*.
- **Keyboard Accessibility**: Indikator fokus tebal berwarna oranye untuk aksesibilitas *keyboard navigation* yang ramah (WCAG).

## Keamanan
- **PDO Prepared Statement**: Segala aktivitas modifikasi database terlindungi dari ancaman *SQL Injection*.
- **Anti XSS**: Data dari form dikonversi menjadi entitas HTML dengan mekanisme `htmlspecialchars`.
- **Proteksi CSRF**: Khusus untuk *action delete*, sebuah *token* CSRF akan diproduksi oleh server dan divalidasi dengan `hash_equals`.
- **PRG (Post-Redirect-Get)**: Mencegah efek submit ganda secara tidak sengaja ketika pengguna me-refresh halaman usai form diproses.
- **Validasi Unggahan Gambar**: Membatasi format khusus file gambar (JPG, PNG, WEBP, GIF) dan ukurannya tidak boleh melampaui limit 2MB.

## Struktur Direktori
- `config/db.php`: Skrip konfigurasi PDO.
- `index.php`: Antarmuka utama (Grid Produk, Filter, Pencarian, Pagination).
- `create.php` & `edit.php`: Form data entri & unggah gambar.
- `delete.php`: Kontroler hapus barang & eksekusi hapus file fisik gambar.
- `assets/style.css`: File *styling* khusus.
- `assets/uploads/`: Folder direktori penyimpanan file gambar.
- `database/store_db.sql`: Skrip DDL/DML untuk *dump database*.
- `DESIGN.md`: Dokumentasi landasan desain dan tata letak UI aplikasi ini.

## Cara Menjalankan Aplikasi
1. Pindahkan (salin) keseluruhan folder aplikasi ini ke direktori server lokal Anda (misal `htdocs` untuk pengguna XAMPP, atau direktori root pada Laragon).
2. Hidupkan modul **Apache** dan **MySQL** Anda.
3. Buat database baru bernama `store_db` menggunakan phpMyAdmin atau terminal MySQL.
4. Impor susunan tabel beserta seluruh sampel data (*dummy data*) dengan menggunakan berkas `database/store_db.sql`.
5. (Opsional) Sesuaikan konfigurasi *username* & *password* akses pada file `config/db.php` jika server lokal Anda tidak memakai konfigurasi bawaan standar (root tanpa password).
6. Akses aplikasi Anda via browser, di alamat semisal `http://localhost/mini_project2/` atau URL yang relevan berdasarkan nama penempatan foldernya.
