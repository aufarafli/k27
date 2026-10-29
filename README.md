# Sistem Absensi Keamanan Informasi K2

Aplikasi web untuk mencatat kehadiran mahasiswa kelas Keamanan Informasi K2. Dibangun menggunakan PHP dan MySQL dengan tampilan bertema pendidikan (putih, biru navy, dan emas). Aplikasi ini dilengkapi fitur keamanan untuk mengunci absensi agar mahasiswa tidak bisa mengisi di luar jam kuliah.

## Prasyarat

Sebelum memulai, pastikan laptop kalian sudah terinstal:
- XAMPP (dengan Apache dan MySQL)
- Git
- VSCode (opsional, untuk edit kode)
- Browser (Chrome/Edge/Firefox)

## Panduan Instalasi
Ikuti langkah-langkah berikut untuk menjalankan aplikasi di laptop kalian.

### 1. Clone Repositori
### 2. Pindahkan ke Folder XAMPP
Setelah proses clone selesai, pindahkan folder k27 ke dalam folder htdocs di direktori XAMPP kalian. Biasanya letaknya di C:\xampp\htdocs.

### 3. Setup Database
1. Nyalakan Apache dan MySQL di XAMPP Control Panel.
2. Buka browser dan akses localhost/phpmyadmin.
3. Buat database baru dengan nama absensi_siswa.
4. Klik database tersebut, lalu masuk ke tab Import.
5. Pilih file database.sql yang ada di dalam folder k27/database/.
6. Klik tombol Import atau Go dan tunggu sampai selesai.

### 4. Konfigurasi Koneksi
Buka folder k27/config/ di VSCode, lalu buka file database.php.
Pastikan pengaturan host, username, password, dan nama database sudah sesuai dengan yang ada di phpMyAdmin kalian. Biasanya username-nya root dan password-nya kosong.


### 5. Jalankan Aplikasi
Buka browser dan ketik alamat ini:
localhost/k27/admin/

Login sebagai Admin:
Username: admin
Password: admin123

Login sebagai Mahasiswa:
Buka alamat ini:
localhost/k27/siswa/

Gunakan akun contoh mahasiswa di bawah ini untuk mencoba masuk:
NIM: M0403241133
Password: siswa_M0403241133

## Alur Kerja Git untuk Kelompok
Karena kita bekerja secara berkelompok, kita harus rajin melakukan pull dan push agar kode tidak bentrok.

### Sebelum Mulai Ngoding
Selalu tarik kode terbaru dari GitHub agar kalian mendapatkan update terakhir dari teman kalian.
git pull origin main


### Setelah Selesai Ngoding
Simpan semua perubahan yang kalian lakukan.
git add .
git commit -m "Tulis pesan perubahan kalian di sini"
git push origin main

## Fitur Utama Saat Ini

### Admin
-Bisa membuka dan menutup absensi untuk mencegah mahasiswa absen di luar jam kuliah.
-Bisa mengisi kehadiran (Hadir, Alpha, Izin, Sakit) secara manual.
-Bisa mengatur tanggal dan metode perkuliahan (Luring, Daring, Asynchronous).
-Bisa mengunduh laporan kehadiran dalam bentuk PDF dan Excel.
-Melihat rekap kehadiran seluruh mahasiswa dalam bentuk lingkaran status.
-Bisa mengedit data kehadiran kapan saja, meskipun absensi sudah dikunci.

### Mahasiswa
-Bisa melihat daftar absensi yang sedang dibuka oleh admin.
-Hanya bisa mengisi status Hadir.
-Bisa melihat riwayat kehadiran diri sendiri.
-Bisa mengubah profil dan password.


## Catatan Penting
-Pastikan XAMPP (Apache dan MySQL) selalu menyala saat kalian sedang mengerjakan proyek ini.
-Jangan lupa melakukan git pull setiap kali mau mulai ngoding, dan git push setelah selesai ngoding.
-Jangan pernah meng-commit file config/database.php yang berisi password asli server production. Gunakan konfigurasi lokal saja.