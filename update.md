# Log Pembaruan Projek - Manajemen Keuangan

## 🚀 Update Terbaru (24 Mei 2026)

### 🧮 Fitur Kalkulator Tabungan (Smart Planner)
- **Implementasi Kalkulator**: Penambahan alat bantu perencanaan keuangan untuk menghitung target tabungan secara otomatis.
- **Kalkulasi Harian & Bulanan**: Sistem menghitung berapa yang harus disisihkan setiap hari dan setiap bulan berdasarkan target tanggal yang ditentukan.
- **Dukungan Sisa Waktu**: Menampilkan sisa waktu dalam hitungan hari dan bulan untuk mencapai target.
- **Smart Tips**: Penambahan fitur saran cerdas (tips) yang menyesuaikan dengan besarnya target tabungan harian pengguna.
- **UI Integrasi**: Penambahan tombol akses cepat di Dashboard dan menu "Kalkulator" di Sidebar.

## 🚀 Update Sebelumnya (21 Mei 2026)

### 🧠 Supercharged AI Analysis (XGBoost Integration)
- **Upgrade Algoritma ML**: Migrasi dari Regresi Linear sederhana ke **XGBoost (Extreme Gradient Boosting)** untuk akurasi prediksi yang jauh lebih tinggi.
- **Fitur Training Mandiri**: Penambahan tombol **"Latih Model (Train)"** di dashboard analisis yang memungkinkan pengguna memperbarui otak AI secara instan dengan data terbaru.
- **Transparansi Akurasi**: Menampilkan metrik performa model secara real-time (Akurasi %, RMSE, dan jumlah data points) setelah proses training selesai.
- **Analisis Kondisi & Risiko**: AI kini mampu mengklasifikasikan kondisi keuangan (*Healthy, Warning, Critical*) dan tingkat risiko berdasarkan rasio pengeluaran, hutang, dan tabungan.
- **Deteksi Anomali Otomatis**: Implementasi algoritma *Outlier Detection* untuk mendeteksi transaksi yang tidak wajar atau jauh di atas rata-rata kebiasaan pengguna.
- **Analisis Pola Spending**: AI mendeteksi hari-hari dengan pengeluaran tertinggi untuk memberikan saran penghematan yang lebih spesifik.
- **Prediksi Cashflow**: Menghitung target saldo akhir bulan berdasarkan proyeksi pengeluaran cerdas.

### 🧠 Integrasi Machine Learning (Python Microservice) - *Legacy Note*
- **Arsitektur Microservice**: Memisahkan logika AI ke layanan terpisah menggunakan **Python FastAPI** yang berjalan di port 8001.
- **Algoritma XGBoost**: Implementasi model *Supervised Learning* menggunakan **XGBoost Regressor** untuk prediksi pengeluaran yang lebih akurat dibanding regresi linear biasa.
- **Feature Engineering**: Model AI kini mempertimbangkan data histori, rata-rata pergerakan (rolling mean), dan pola musiman (seasonality).
- **Deep AI Insights**: Fitur baru untuk mendeteksi anomali pengeluaran per kategori. Sistem otomatis membandingkan data bulan ini vs bulan lalu dan memberikan peringatan jika ada kenaikan drastis (>20%).
- **Hybrid Fallback**: Sistem PHP memiliki mekanisme *fallback* ke Regresi Linear jika server Python sedang tidak aktif, memastikan aplikasi tetap berjalan normal.

### 🎨 Fitur Dark Mode & Redesain UI Menyeluruh
- **Sistem Tema Dinamis**: Implementasi variabel CSS (`:root`) untuk mendukung perpindahan tema Light/Dark secara instan tanpa reload.
- **Persistensi Tema**: Pilihan tema pengguna disimpan menggunakan `localStorage` agar tidak berubah saat navigasi halaman.
- **Toggle UI**: Penambahan tombol switch tema di sidebar (desktop) dan ikon bulan/matahari di header (mobile).
- **Pembersihan Kode Visual**: Migrasi dari inline styles yang berantakan ke sistem CSS terpusat di `assets/css/style.css`.
- **Optimalisasi Render**: Menghapus efek `backdrop-filter` yang menyebabkan glitching visual pada browser Windows/Chrome.

### 📊 Peningkatan Visual Dashboard
- **Tabel Transaksi Terbaru**:
    - Perubahan desain menjadi *Separated Rows* dengan border-radius dan shadow yang halus.
    - Penambahan ikon dinamis pada kolom **Akun** (Bank, Wallet, Cash).
    - Penambahan ikon tag pada kolom **Kategori**.
    - Penambahan ikon indikator arah (`plus-circle` / `minus-circle`) pada nominal transaksi.
- **Kategori Pengeluaran Teratas**: 
    - Penambahan kembali indikator warna (color dots) yang sinkron dengan diagram pie.
    - Perbaikan layout daftar kategori agar lebih rapi.

### 🚥 Standarisasi Pewarnaan (Color-Coding)
- **Halaman Transaksi**:
    - Implementasi badge berwarna untuk membedakan Pemasukan (Hijau), Pengeluaran (Merah), dan Transfer (Indigo).
    - Redesain tombol Filter (Terapkan & Reset) agar lebih mencolok dan ramah pengguna.
- **Halaman Kategori**:
    - Pemberian warna pada header kategori.
    - Setiap item kategori sekarang memiliki badge berwarna sesuai tipenya.
- **Modal Detail Transaksi**:
    - Perombakan total UI modal detail dengan header gradien yang berubah warna sesuai tipe transaksi.
    - Struktur informasi yang lebih bersih dan profesional.

### 🏦 Perbaikan Manajemen Akun
- **Kartu Akun**: Restorasi desain kartu yang hilang, memastikan setiap rekening/dompet tampil sebagai kartu solid yang elegan.
- **Ringkasan Saldo**: Redesain kartu total saldo dengan gaya yang lebih modern dan aman (*consolidated balance*).

---
*Catatan: Semua perubahan ini bertujuan untuk meningkatkan pengalaman pengguna (UX) dan memberikan kesan aplikasi yang lebih "hidup" dan modern.*
