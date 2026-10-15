<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    #[OA\Post(
        path: '/checkout',
        summary: 'UC-03: Membeli Produk / Checkout',
        tags: ['Order'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['seller_id', 'items'],
                properties: [
                    new OA\Property(property: 'seller_id', type: 'integer', example: 1),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'book_id', type: 'integer', example: 10),
                                new OA\Property(property: 'qty', type: 'integer', example: 2)
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Checkout Berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Checkout berhasil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'order_id', type: 'integer', example: 105),
                                new OA\Property(property: 'total_price', type: 'number', example: 170000),
                                new OA\Property(property: 'status', type: 'string', example: 'pending_payment')
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'       => 'required|exists:sellers,id',
            'items'           => 'required|array|min:1',
            'items.*.book_id' => 'required|exists:books,id',
            'items.*.qty'     => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $totalPrice = 0;
            $orderItemsData = [];

            foreach ($request->items as $item) {
                $book = Book::find($item['book_id']);

                if ($book->stock < $item['qty']) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Stok buku '{$book->title}' tidak mencukupi"
                    ], 400);
                }

                $subtotal = $book->price * $item['qty'];
                $totalPrice += $subtotal;

                $orderItemsData[] = [
                    'book_id'    => $book->id,
                    'qty'        => $item['qty'],
                    'price'      => $book->price,
                    'book_model' => $book
                ];
            }

            $order = Order::create([
                'user_id'     => $request->user()->id,
                'seller_id'   => $request->seller_id,
                'total_price' => $totalPrice,
                'status'      => 'pending_payment',
            ]);

            foreach ($orderItemsData as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id'  => $itemData['book_id'],
                    'qty'      => $itemData['qty'],
                    'price'    => $itemData['price'],
                ]);

                $itemData['book_model']->decrement('stock', $itemData['qty']);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Checkout berhasil',
                'data'    => [
                    'order_id'    => $order->id,
                    'total_price' => $order->total_price,
                    'status'      => $order->status,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat checkout: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: '/payments/upload',
        summary: 'UC-04: Mengunggah Bukti Pembayaran',
        tags: ['Order'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['order_id', 'payment_proof'],
                    properties: [
                        new OA\Property(property: 'order_id', type: 'integer', example: 105),
                        new OA\Property(property: 'payment_proof', type: 'string', format: 'binary')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Upload Berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Bukti pembayaran berhasil diunggah'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'order_id', type: 'integer', example: 105),
                                new OA\Property(property: 'status', type: 'string', example: 'waiting_verification'),
                                new OA\Property(property: 'payment_proof_url', type: 'string', example: 'http://127.0.0.1:8000/storage/payments/proof_105.jpg')
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function uploadPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id'      => 'required|exists:orders,id',
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::where('id', $request->order_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }

        $proofPath = $request->file('payment_proof')->store('payments', 'public');

        Payment::create([
            'order_id'      => $order->id,
            'payment_proof' => Storage::url($proofPath),
            'status'        => 'pending',
            'uploaded_at'   => now(),
        ]);

        $order->update(['status' => 'waiting_verification']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Bukti pembayaran berhasil diunggah',
            'data'    => [
                'order_id'          => $order->id,
                'status'            => $order->status,
                'payment_proof_url' => url(Storage::url($proofPath)),
            ]
        ], 200);
    }

    #[OA\Get(
        path: '/orders/my-orders',
        summary: 'UC-05: Melihat Status Pesanan',
        tags: ['Order'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar Riwayat Pesanan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 105),
                                    new OA\Property(property: 'total price', type: 'number', example: 170000),
                                    new OA\Property(property: 'status', type: 'string', example: 'waiting_verification'),
                                    new OA\Property(
                                        property: 'order items',
                                        type: 'array',
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: 'book_id', type: 'integer', example: 10),
                                                new OA\Property(property: 'qty', type: 'integer', example: 2),
                                                new OA\Property(property: 'price', type: 'number', example: 85000)
                                            ]
                                        )
                                    )
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function myOrders(Request $request)
    {
        $orders = Order::with('orderItems')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'id'          => $order->id,
                    'total price' => $order->total_price,
                    'status'      => $order->status,
                    'order items' => $order->orderItems->map(function ($item) {
                        return [
                            'book_id' => $item->book_id,
                            'qty'     => $item->qty,
                            'price'   => $item->price,
                        ];
                    })
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $orders
        ], 200);
    }
}
