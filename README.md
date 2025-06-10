Collection 3: Booking Service API (http://localhost:8002)
Deskripsi Collection:

Layanan Pemesanan (Booking Service) adalah mesin transaksional dari platform ini. Ia bertanggung jawab untuk mengelola seluruh alur pemesanan jadwal pemotretan.
Layanan ini bertindak sebagai orkestrator:
Berkomunikasi secara sinkron dengan User Service & Catalog Service untuk validasi data saat pemesanan dibuat.
Berkomunikasi secara asinkron dengan memicu event ke Message Broker (Redis) setelah pesanan berhasil dibuat, untuk ditangani oleh layanan lain seperti Notification Service.

Deskripsi Setiap Request:
POST /api/bookings - Deskripsi: Membuat jadwal pemesanan baru untuk sebuah paket fotografi. Endpoint ini akan memvalidasi user_id dan service_id ke layanan terkait dan memicu event notifikasi.
GET /api/bookings - Deskripsi: Mengambil daftar riwayat pemesanan. Dapat difilter berdasarkan pengguna dengan menambahkan parameter query ?user_id={id}.
GET /api/bookings/{id} - Deskripsi: Mengambil detail spesifik dari satu data pemesanan.
PUT /api/bookings/{id} - Deskripsi: Memperbarui status sebuah pemesanan. Endpoint ini dapat digunakan untuk mengonfirmasi (confirmed) atau membatalkan (cancelled) jadwal pemotretan.
DELETE /api/bookings/{id} - Deskripsi: [Admin] Menghapus data riwayat pemesanan.
