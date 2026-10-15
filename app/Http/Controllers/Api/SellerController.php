<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Order;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class SellerController extends Controller
{
    #[OA\Post(
        path: '/seller/register',
        summary: 'UC-01b: Buka Toko / Registrasi Seller',
        tags: ['Seller'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Toko Buku Ilmiah Malang')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Berhasil Terdaftar',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Berhasil terdaftar sebagai seller'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Toko Buku Ilmiah Malang')
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function registerSeller(Request $request)
    {
        $user = $request->user();

        if ($user->seller) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda sudah terdaftar sebagai Seller'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $seller = Seller::create([
            'user_id' => $user->id,
            'name'    => $request->name,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil terdaftar sebagai seller',
            'data'    => [
                'id'      => $seller->id,
                'user_id' => $seller->user_id,
                'name'    => $seller->name,
            ]
        ], 201);
    }

    #[OA\Post(
        path: '/seller/books',
        summary: 'UC-06: Mengelola Produk - Tambah Buku',
        tags: ['Seller'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['category_id', 'title', 'author', 'price', 'stock'],
                    properties: [
                        new OA\Property(property: 'category_id', type: 'integer', example: 1),
                        new OA\Property(property: 'title', type: 'string', example: 'Algoritma Pemrograman'),
                        new OA\Property(property: 'author', type: 'string', example: 'Budi'),
                        new OA\Property(property: 'price', type: 'number', example: 95000),
                        new OA\Property(property: 'stock', type: 'integer', example: 10),
                        new OA\Property(property: 'description', type: 'string', example: 'Buku algoritma'),
                        new OA\Property(property: 'cover_img', type: 'string', format: 'binary')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Sukses Tambah Buku',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Data produk buku berhasil diperbarui')
                    ]
                )
            )
        ]
    )]
    public function storeBook(Request $request)
    {
        $seller = $request->user()->seller;

        if (!$seller) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda belum terdaftar sebagai Seller'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'description' => 'nullable|string',
            'cover_img'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $coverPath = null;
        if ($request->hasFile('cover_img')) {
            $coverPath = $request->file('cover_img')->store('books', 'public');
        }

        Book::create([
            'seller_id'   => $seller->id,
            'category_id' => $request->category_id,
            'title'       => $request->title,
            'author'      => $request->author,
            'price'       => $request->price,
            'stock'       => $request->stock,
            'description' => $request->description,
            'cover_img'   => $coverPath ? Storage::url($coverPath) : null,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data produk buku berhasil diperbarui'
        ], 201);
    }

    #[OA\Put(
        path: '/seller/books/{id}',
        summary: 'UC-06: Mengelola Produk - Edit Buku',
        tags: ['Seller'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: '_method', type: 'string', example: 'PUT'),
                        new OA\Property(property: 'category_id', type: 'integer', example: 1),
                        new OA\Property(property: 'title', type: 'string', example: 'Algoritma Pemrograman Ed. 2'),
                        new OA\Property(property: 'author', type: 'string', example: 'Budi'),
                        new OA\Property(property: 'price', type: 'number', example: 95000),
                        new OA\Property(property: 'stock', type: 'integer', example: 12),
                        new OA\Property(property: 'cover_img', type: 'string', format: 'binary')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Sukses Edit Buku')
        ]
    )]
    public function updateBook(Request $request, $id)
    {
        $seller = $request->user()->seller;

        if (!$seller) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda belum terdaftar sebagai Seller'
            ], 403);
        }

        $book = Book::where('id', $id)->where('seller_id', $seller->id)->first();

        if (!$book) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Buku tidak ditemukan atau bukan milik Anda'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|required|exists:categories,id',
            'title'       => 'sometimes|required|string|max:255',
            'author'      => 'sometimes|required|string|max:255',
            'price'       => 'sometimes|required|numeric|min:0',
            'stock'       => 'sometimes|required|integer|min:0',
            'description' => 'nullable|string',
            'cover_img'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('cover_img')) {
            if ($book->cover_img) {
                $oldPath = str_replace('/storage/', '', $book->cover_img);
                Storage::disk('public')->delete($oldPath);
            }
            $coverPath = $request->file('cover_img')->store('books', 'public');
            $book->cover_img = Storage::url($coverPath);
        }

        $book->update($request->only([
            'category_id',
            'title',
            'author',
            'price',
            'stock',
            'description'
        ]));

        return response()->json([
            'status'  => 'success',
            'message' => 'Data produk buku berhasil diperbarui'
        ], 200);
    }

    #[OA\Delete(
        path: '/seller/books/{id}',
        summary: 'UC-06: Mengelola Produk - Hapus Buku',
        tags: ['Seller'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sukses Hapus Buku')
        ]
    )]
    public function destroyBook(Request $request, $id)
    {
        $seller = $request->user()->seller;

        if (!$seller) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda belum terdaftar sebagai Seller'
            ], 403);
        }

        $book = Book::where('id', $id)->where('seller_id', $seller->id)->first();

        if (!$book) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Buku tidak ditemukan atau bukan milik Anda'
            ], 404);
        }

        if ($book->cover_img) {
            $oldPath = str_replace('/storage/', '', $book->cover_img);
            Storage::disk('public')->delete($oldPath);
        }

        $book->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data produk buku berhasil diperbarui'
        ], 200);
    }

    #[OA\Get(
        path: '/seller/books',
        summary: 'Lihat Daftar Buku Milik Seller',
        tags: ['Seller'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Berhasil')]
    )]
    public function myBooks(Request $request)
    {
        $seller = $request->user()->seller;

        if (!$seller) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda belum terdaftar sebagai Seller'
            ], 403);
        }

        $books = Book::with('category')->where('seller_id', $seller->id)->get()->map(function ($book) {
            return [
                'id'          => $book->id,
                'title'       => $book->title,
                'author'      => $book->author,
                'price'       => $book->price,
                'stock'       => $book->stock,
                'description' => $book->description,
                'cover_img'   => $book->cover_img,
                'category'    => $book->category ? $book->category->name : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $books
        ], 200);
    }

    #[OA\Get(
        path: '/seller/orders',
        summary: 'UC-07: Mengelola Pesanan - Lihat Pesanan Masuk',
        tags: ['Seller'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar Pesanan Masuk',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 105),
                                    new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'total_price', type: 'number', example: 170000),
                                    new OA\Property(property: 'status', type: 'string', example: 'paid')
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function incomingOrders(Request $request)
    {
        $seller = $request->user()->seller;

        if (!$seller) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda belum terdaftar sebagai Seller'
            ], 403);
        }

        $orders = Order::where('seller_id', $seller->id)
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'id'          => $order->id,
                    'user_id'     => $order->user_id,
                    'total_price' => $order->total_price,
                    'status'      => $order->status,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $orders
        ], 200);
    }
}
