# 📸 Booking Service API

**Base URL:** `http://localhost:8002`

## Deskripsi Layanan

Booking Service adalah *mesin transaksional* dari platform ini yang bertugas untuk menangani seluruh proses pemesanan jadwal pemotretan. Layanan ini berperan sebagai **orkestrator** yang melakukan hal-hal berikut:

* Berkomunikasi **secara sinkron** dengan:

  * **User Service** untuk validasi `user_id`
  * **Catalog Service** untuk validasi `service_id`
* Berkomunikasi **secara asinkron** dengan:

  * **Message Broker (Redis)** untuk memicu event notifikasi setelah pemesanan berhasil dilakukan. Event ini akan diteruskan dan ditangani oleh layanan lain seperti **Notification Service**.

---

## 🛠️ Daftar Endpoint

### `POST /api/bookings`

**Deskripsi:**
Membuat jadwal pemesanan baru untuk sebuah paket fotografi.
Endpoint ini akan:

* Memvalidasi `user_id` dan `service_id` ke layanan terkait.
* Memicu event notifikasi jika pemesanan berhasil.

**Contoh Request Body:**

```json
{
  "user_id": "123",
  "service_id": "456",
  "schedule_date": "2025-06-15T14:00:00Z"
}
```

---

### `GET /api/bookings`

**Deskripsi:**
Mengambil daftar riwayat pemesanan.
Dapat ditambahkan query parameter `?user_id={id}` untuk filter berdasarkan pengguna.

**Contoh:**

```
GET /api/bookings?user_id=123
```

---

### `GET /api/bookings/{id}`

**Deskripsi:**
Mengambil detail spesifik dari satu data pemesanan berdasarkan ID.

---

### `PUT /api/bookings/{id}`

**Deskripsi:**
Memperbarui status pemesanan.
Dapat digunakan untuk:

* Mengonfirmasi (`confirmed`)
* Membatalkan (`cancelled`) jadwal pemotretan.

**Contoh Request Body:**

```json
{
  "status": "confirmed"
}
```

---

### `DELETE /api/bookings/{id}`

**Deskripsi:**
Hanya untuk **Admin**.
Digunakan untuk menghapus data riwayat pemesanan secara permanen.

---

## 📡 Arsitektur Komunikasi

```plaintext
User → Booking Service → (Validasi) → User Service
                                  → (Validasi) → Catalog Service
                                  → (Event Async) → Redis → Notification Service
```

---

## 📌 Catatan

* Seluruh endpoint menggunakan format JSON.
* Gunakan autentikasi dan otorisasi yang sesuai jika diperlukan.
* Status pemesanan biasanya berupa: `pending`, `confirmed`, `cancelled`.

