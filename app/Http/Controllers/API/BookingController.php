<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /**
     * Menampilkan semua booking, bisa difilter by user_id.
     */
    public function index(Request $request)
    {
        $query = Booking::query();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully retrieved bookings.',
            'data' => $query->paginate(15)
        ]);
    }

        /**
     * Menyimpan data booking baru.
     * POST /api/bookings
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'service_id' => 'required|integer',
            'booking_time' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        // --- Panggil dan Validasi ke Catalog Service ---
        $catalogServiceUrl = config('services.catalog.base_uri');
        $serviceResponse = Http::get("{$catalogServiceUrl}/api/services/{$request->service_id}");

        // Cek jika request GAGAL atau TIDAK SUKSES (misal: 404 Not Found)
        if (!$serviceResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan (service) dengan ID yang diberikan tidak dapat ditemukan.'
            ], 404);
        }
        // Jika aman, baru kita proses datanya
        $serviceData = $serviceResponse->json()['data'];


        // --- Panggil dan Validasi ke User Service ---
        $userServiceUrl = config('services.user.base_uri');
        $userResponse = Http::get("{$userServiceUrl}/api/users/{$request->user_id}");

        if (!$userResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna (user) dengan ID yang diberikan tidak dapat ditemukan.'
            ], 404);
        }
        $userData = $userResponse->json()['data'];


        // --- Lanjutkan Proses Booking Jika Semua Valid ---
        $booking = Booking::create([
            'user_id' => $request->user_id,
            'service_id' => $request->service_id,
            'booking_time' => $request->booking_time,
            'total_price' => $serviceData['price'],
            'status' => 'pending',
        ]);

        // Mengirim event ke Redis
        $eventPayload = json_encode([
            'event' => 'booking_created',
            'data' => [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'user_email' => $userData['email'],
                'service_name' => $serviceData['name'],
            ]
        ]);
        Redis::publish('booking_events', $eventPayload);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => $booking
        ], 201);
    }
    public function show(Booking $booking)
    {
        return response()->json([
            'success' => true,
            'message' => 'Booking retrieved successfully.',
            'data' => $booking
        ]);
    }

    /**
     * Memperbarui status booking.
     */
    public function update(Request $request, Booking $booking)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['confirmed', 'cancelled', 'completed'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $booking->update($validator->validated());

        $eventPayload = json_encode([
            'event' => 'booking_status_updated',
            'data' => $booking->toArray(),
        ]);
        Redis::publish('booking_events', $eventPayload);

        return response()->json([
            'success' => true,
            'message' => 'Booking status updated successfully.',
            'data' => $booking
        ]);
    }

    /**
     * Menghapus data booking.
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();

        return response()->json([
            'success' => true,
            'message' => 'Booking deleted successfully.'
        ]);
    }
}
