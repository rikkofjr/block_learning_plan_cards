# Moodle Block: Learning Plan Cards

Plugin block kustom Moodle yang memvisualisasikan Rencana Belajar (*Learning Plans*) pengguna beserta kursus terkait dalam bentuk slider kartu (*card slider*) horizontal yang modern dan interaktif. Block ini dirancang untuk meningkatkan pengalaman pengguna pada halaman Dashboard dengan memberikan ringkasan kemajuan kompetensi yang bersih, estetik, dan mudah dipantau.

## Fitur Utama
- **Slider Horizontal:** Desain modern berjejer menyamping untuk menghemat ruang halaman Dashboard.
- **Klik Langsung (Clickable Card):** Seluruh area kartu kursus berfungsi sebagai tautan aktif untuk memudahkan pengguna masuk ke ruang kelas.
- **Indikator Progress:** Bilah kemajuan (*progress bar*) waktu nyata (*real-time*) berdasarkan penyelesaian aktivitas di dalam kursus.
- **Berbasis Kompetensi:** Menampilkan judul kompetensi beserta ID kode unik secara dinamis dan rapi.
- **Integrasi Moodle Core:** Menggunakan API standar Moodle untuk penarikan data kompetensi dan penyelesaian kursus (*completion*).

## Persyaratan Sistem
- Moodle 4.x atau versi yang lebih tinggi.
- Fitur *Competency Framework* (Kerangka Kerja Kompetensi) sudah diaktifkan di situs Moodle Anda.

## Cara Instalasi
1. Download plugin berikut dalam bentuk file zip
2. Lakukan upload plugin di moodle
3. Instalasi sesuai dengan instruksi moodle

## Catatan
Sebelum menggunakan block ini Pastikan admin / manager LMS anda telah 
- men-setup competency framework 
- memastikan course sudah memiliki competency
- telah membuat learning plan untuk setiap user

## Cara Menampilkan Block Visual Learning Plan
1. Buka halaman **Dashboard** utama Anda.
2. Masuk kedalam **Edit mode** (Mode Ubah).
3. Klik tombol **Add a block** (Tambahkan blok) dan pilih **Learning Plan Cards** dari daftar yang muncul.
4. Blok akan langsung tampil di Dashboard dan otomatis memetakan kompetensi aktif beserta kartu kursus yang sesuai dengan akun pengguna yang sedang login.
