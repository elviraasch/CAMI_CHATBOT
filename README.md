# Chatbot Cami

Cami adalah chatbot Rasa untuk CAMAR. Chatbot membaca tipe akun buyer dari Laravel,
menjalankan form emisi yang sesuai, lalu mengembalikan data aktivitas ke Laravel.
Laravel menghitung dan menyimpan hasil serta menyiapkan rekomendasi proyek carbon
offset. Semua pemrosesan berjalan lokal tanpa API AI.

Cami juga bisa menampilkan rekomendasi proyek tanpa menjalankan kalkulator lagi.
Ketik **"carikan proyek sesuai emisi saya saat ini"** atau klik tombol
**Rekomendasi proyek** di widget. Tombol dan kalimat rekomendasi yang umum
diproses langsung oleh Laravel, sehingga tetap bekerja bila model Rasa lama
masih aktif. Untuk permintaan ini cukup server Laravel yang aktif; perhitungan
dan percakapan lain tetap memerlukan Rasa. Jika akun memiliki hasil emisi, Laravel memakai
perhitungan terakhir yang tersimpan. Jika belum, Cami menampilkan pilihan proyek
awal yang tersedia; ini belum disesuaikan dengan besar emisi. Permintaan
rekomendasi tidak menambah data pada tabel `emission_calculations`.

## Riwayat chat dan refresh

Setelah Cami dibuka, panel tetap terbuka jika halaman di-refresh. Panel baru
tertutup saat tombol **X** pada header ditekan. Selama panel masih terbuka,
pesan dan kartu hasil dipulihkan setelah refresh. Menekan **X** mengarsipkan
seluruh percakapan aktif sebagai **satu sesi**. Ketika Cami dibuka lagi,
halaman chat dimulai dari sapaan awal dengan ID percakapan Rasa yang baru.
Tombol **Riwayat** menampilkan satu kartu per sesi yang sudah ditutup;
klik kartu untuk membaca transkripnya tanpa mengirim pesan baru ke Rasa.

Riwayat ini disimpan di browser, bukan pada database CAMAR. Untuk buyer yang
login, penyimpanan browser dipisahkan menurut akun dan berisi maksimal 30
sesi terakhir, masing-masing maksimal 100 pesan. Riwayat tamu hanya berlaku
selama sesi tab browser. Riwayat
tidak otomatis tersedia di perangkat lain dan hilang jika data situs pada
browser dihapus. Jika form Rasa yang lama sudah kedaluwarsa atau server Rasa
di-restart, mulai kembali dari tombol **Mulai kalkulator akun saya**.

## Pertanyaan tentang katalog proyek

Ketik pertanyaan seperti berikut di widget Cami:

- "Proyek apa yang paling murah?"
- "Proyek mana yang paling mahal?"
- "Proyek dengan stok terbanyak?"
- "Proyek apa yang paling banyak dibeli?"
- "Proyek apa yang paling sering dibeli?"
- "Ada berapa proyek yang tersedia?"
- "Daftar proyek yang tersedia"

Laravel membaca harga, stok, dan jumlah pembelian terbaru dari database, lalu menampilkan proyek
yang sudah disetujui dan masih memiliki stok. Pertanyaan katalog umum ini dapat
dipakai tanpa login dan tidak memerlukan model Rasa baru atau API AI. Jawaban
harga memakai satuan **Rp per ton kredit karbon**. Tiga proyek teratas
untuk pertanyaan harga, stok, penjualan, atau daftar ditampilkan sebagai kartu dengan
tautan ke halaman detail. Kode pemilihan
pertanyaan dan kueri ada di `camarweb/app/Services/ChatbotProjectQueryService.php`.
Untuk jenis pertanyaan baru, tambahkan pola dan cara mengambil datanya di sana.
Pertanyaan yang belum punya pola atau data pendukung tetap ditangani Rasa dan
bisa menghasilkan pesan bahwa Cami belum memahami pertanyaan.

Buyer yang sudah menghitung emisi juga dapat bertanya:

> "Rekomendasi produk termurah yang bisa offset emisi saya di dashboard"

Cari termurah **sesuai dashboard** memerlukan login sebagai buyer.
Cami membaca **perhitungan emisi terakhir** yang tampil di dashboard, lalu
mengurangi ton offset dari pesanan buyer berstatus `paid`, `verified`, atau
`completed`, sama seperti ringkasan dashboard. Sisa emisi dibulatkan ke atas
menjadi kebutuhan kredit dalam **ton utuh**, sesuai jumlah pembelian di checkout.
Cami mencari proyek yang disetujui dengan stok cukup untuk seluruh kebutuhan
itu, mengurutkannya dari harga per ton terendah, dan menampilkan perkiraan
biaya kredit **sebelum pajak dan biaya checkout**. Kartu proyek juga menampilkan
skor kecocokan dari mesin rekomendasi CAMAR berdasarkan profil emisi; urutan
utama tetap berdasarkan harga. Jika belum ada perhitungan,
sisa emisi sudah nol, atau tidak ada satu proyek dengan stok cukup, Cami
menjelaskan kondisi tersebut. Pertanyaan ini tidak menyimpan kalkulasi atau
membuat pesanan baru.

Untuk pertanyaan **paling banyak dibeli**, Cami mengurutkan total **ton kredit**
yang terjual. Untuk **paling sering dibeli**, Cami mengurutkan **jumlah pesanan**.
Keduanya hanya menghitung pesanan berstatus `paid`, `verified`, atau `completed`
pada proyek yang disetujui. Proyek yang stoknya sudah habis tetap dapat muncul
sebagai data penjualan historis; Cami menyebutkan jika proyek teratas sedang
kehabisan stok. Angka pembelian diambil dari tabel `orders` dan dapat berubah
setelah pesanan baru berhasil.

## Alur akun

- Buyer individu: energi rumah tangga, kendaraan pribadi, listrik, transportasi
  umum, makanan, air, dan sampah.
- Buyer perusahaan: Scope 1 (pembakaran stasioner dan kendaraan operasional),
  Scope 2 (listrik), serta Scope 3 (pesawat, hotel, dan kereta).

Untuk penerbangan dinas perusahaan, Cami meminta kelas kabin, jumlah penumpang,
bandara asal, dan bandara tujuan. Pengguna dapat menulis kode IATA (`CGK`), nama
kota/daerah (`Jakarta`), atau nama bandara (`Bandara Soekarno-Hatta`). Laravel
menyelesaikan input tersebut menjadi bandara pada tabel `airports_data`, lalu
menghitung jarak dengan rumus Haversine yang sama dengan halaman `/kalkulator`.
Rumus emisinya adalah `jumlah penumpang ? jarak penerbangan ? faktor kelas kabin`.
Faktor kelas kabin: Ekonomi 0,133; Bisnis 0,266; dan First Class 0,399 kg
CO2e/penumpang-km.

Wilayah listrik perusahaan dapat dipilih berdasarkan jaringan atau ditulis sebagai
nama kota/provinsi. Contohnya, `Jakarta` dan `Bandung` dipetakan ke Jawa-Bali,
sedangkan `Palembang` dipetakan ke Sumatra. Tombol **Lainnya** meminta nama daerah.
Daerah yang belum dikenali harus dipetakan pengguna ke Jawa-Bali, Sumatra,
Kalimantan, atau Sulawesi karena hanya empat faktor itulah yang tersedia di
`/kalkulator`.
Tipe akun selalu dibaca ulang dari database Laravel. Nilai tipe akun dari browser
tidak digunakan sebagai penentu perhitungan.

## Menjalankan aplikasi

Jalankan setiap perintah pada terminal PowerShell yang berbeda.

### 1. Laravel

```powershell
cd C:\Users\USER\Downloads\TA\camarweb
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

### 2. Rasa action server

```powershell
cd C:\Users\USER\Downloads\TA\chatbot
.\venv\Scripts\python.exe -m rasa run actions --port 5055
```

Jika terminal menampilkan `only one usage of each socket address`, port 5055 sudah
dipakai. Hentikan action server lama dengan `Ctrl+C` pada terminal asalnya, lalu
jalankan kembali perintah di atas. Setelah `actions/actions.py` berubah, server
lama harus di-restart agar memakai kode baru.

### 3. Rasa server

```powershell
cd C:\Users\USER\Downloads\TA\chatbot
.\venv\Scripts\python.exe -m rasa run --enable-api --cors "*" --port 5005
```

Setelah ketiga server aktif, buka `http://127.0.0.1:8000`, masuk sebagai buyer,
buka widget Cami, lalu pilih **Mulai kalkulator akun saya** atau
**Rekomendasi proyek**. Kartu rekomendasi memiliki tautan **Lihat proyek**.

Jika jawaban terakhir masih menampilkan pesan **"Tipe akun berubah saat pengisian"**,
periksa model yang benar-benar aktif:

```powershell
(Invoke-RestMethod http://127.0.0.1:5005/status).model_file
```

Jika hasilnya bukan `20260918-182743-sweet-fortress.tar.gz`, hentikan Rasa lama
dengan `Ctrl+C` pada terminal asalnya dan jalankan kembali perintah server Rasa
di atas. Hentikan dan jalankan ulang action server pada port 5055 juga bila
`actions/actions.py` berubah. Perhitungan yang telanjur gagal perlu dimulai
lagi dari tombol **Mulai kalkulator akun saya**.

## Setelah mengubah data Rasa

```powershell
cd C:\Users\USER\Downloads\TA\chatbot
.\venv\Scripts\python.exe -m rasa data validate
.\venv\Scripts\python.exe -m rasa train
```

Restart Rasa server setelah model baru selesai dibuat. Restart action server jika
`actions/actions.py` berubah. Jika melatih model baru, ganti nama file pada opsi
`--model` dengan model terbaru di folder `models`.

Chatbot saat ini menerima satu pilihan aktivitas pada setiap kategori dalam satu
sesi. Jika ada beberapa bahan bakar, kendaraan, atau jenis makanan sekaligus,
gunakan kalkulator halaman yang mendukung beberapa baris per kategori.

Setelah jawaban terakhir, Cami menampilkan total emisi, rincian Scope 1-3,
estimasi biaya offset, dan rekomendasi proyek. Hasil langsung tersimpan ke tabel
`emission_calculations`; tidak perlu menekan tombol **Simpan Hasil ke Dashboard**
di kalkulator halaman. Tautan **Lihat Total Emisi Anda** di chat membuka halaman
proyek dengan hasil terbaru. Dashboard buyer juga mengambil hasil terbaru.
Angka ini adalah hasil perhitungan terakhir, bukan penjumlahan semua riwayat.
Jika halaman proyek atau dashboard sudah terbuka sebelumnya, buka ulang halaman
agar rekomendasi di halaman tersebut ikut diperbarui.

## Contoh hasil perhitungan

Jika buyer individu memasukkan LPG 10 kg/bulan, mobil bensin RON 92 sejauh
500 km/bulan, listrik 100 kWh/bulan, kereta 100 km, daging sapi 2 kg/bulan,
air 10 m3/bulan, dan sampah 5 kg/bulan, hasilnya **3.346,06 kg CO2e**.

Jika buyer perusahaan memasukkan diesel stasioner 1.000 liter/tahun,
kendaraan RON 92 sebanyak 100 liter serta diesel 1.000 km, listrik Sumatra
1.000 kWh, penerbangan ekonomi CGK ke DPS untuk 2 penumpang, hotel 2 malam ×
3 kamar, dan kereta ekonomi 1.000 km, jarak penerbangannya **982,61 km** dan
total hasilnya **4.728,28 kg CO2e**.

Jawaban terakhir diproses Laravel setelah Rasa membalas. Karena itu action
server tidak mengirim permintaan balik ke Laravel saat Laravel masih menunggu
Rasa; alur ini menghindari timeout 90 detik pada `php artisan serve`.

## Menjalankan tes

```powershell
cd C:\Users\USER\Downloads\TA\camarweb
php artisan test tests\Unit\CarbonCalculationServiceTest.php tests\Feature\ChatbotCalculationTest.php

cd C:\Users\USER\Downloads\TA\chatbot
.\venv\Scripts\python.exe -m unittest discover -s tests -v
```

Untuk memeriksa alur penuh Rasa → Laravel → database → rekomendasi, jalankan
setelah ketiga server aktif:

```powershell
cd C:\Users\USER\Downloads\TA\camarweb
php tests\smoke_chatbot_result.php
```

Tes ini memakai contoh kedua akun, memeriksa hasil dan rekomendasi, menguji
permintaan rekomendasi tanpa menghitung ulang, lalu
melakukan rollback agar data tes tidak tersimpan di database. Tes ini hanya
boleh dijalankan dengan `APP_ENV=local` atau `APP_ENV=testing`.

Rasa mengenali pertanyaan sesuai contoh intent yang dilatih. Untuk pertanyaan
yang benar-benar di luar topik atau belum didukung, Cami menampilkan menu
bantuan. Menambahkan topik baru memerlukan contoh kalimat, respons atau aksi,
dan pelatihan ulang model; Cami tidak memakai API AI untuk menjawab bebas.

Untuk menguji tombol rekomendasi secara terpisah dari Rasa, jalankan:

```powershell
cd C:\Users\USER\Downloads\TA\camarweb
php tests\smoke_chatbot_recommendation.php
```

Tes ini membuat data sementara dalam transaksi, memeriksa hasil emisi terakhir
dan pilihan awal, lalu melakukan rollback. Hasilnya harus menunjukkan
`rasa_requests: 0` dan `new_calculations: 0`.

Untuk memeriksa jawaban katalog terhadap harga dan stok di database:

```powershell
cd C:\Users\USER\Downloads\TA\camarweb
php tests\smoke_chatbot_project_catalog.php
```

Tes katalog juga memakai transaksi dan melakukan rollback.

Untuk menguji pencarian proyek termurah berdasarkan sisa emisi dashboard:

```powershell
cd C:\Users\USER\Downloads\TA\camarweb
php tests\smoke_chatbot_cheapest_offset.php
```
