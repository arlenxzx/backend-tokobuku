<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BookController extends Controller
{
    #[OA\Get(
        path: '/books',
        summary: 'UC-02: Melihat Katalog Buku (Ringkas)',
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Cari Judul / Penulis', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', description: 'Filter ID Kategori', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar Buku',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 10),
                                    new OA\Property(property: 'title', type: 'string', example: 'Pemrograman Web Laravel'),
                                    new OA\Property(property: 'author', type: 'string', example: 'Arlen'),
                                    new OA\Property(property: 'price', type: 'number', example: 85000),
                                    new OA\Property(property: 'cover_img', type: 'string', example: 'http://127.0.0.1:8000/storage/books/cover1.jpg')
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $query = Book::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $books = $query->latest()->get()->map(function ($book) {
            return [
                'id'        => $book->id,
                'title'     => $book->title,
                'author'    => $book->author,
                'price'     => $book->price,
                'cover_img' => $book->cover_img,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $books
        ], 200);
    }

    #[OA\Get(
        path: '/books/{id}',
        summary: 'UC-02: Detail Buku (Lengkap)',
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail Buku',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 10),
                                new OA\Property(property: 'title', type: 'string', example: 'Pemrograman Web Laravel'),
                                new OA\Property(property: 'author', type: 'string', example: 'Arlen'),
                                new OA\Property(property: 'price', type: 'number', example: 85000),
                                new OA\Property(property: 'stock', type: 'integer', example: 5),
                                new OA\Property(property: 'description', type: 'string', example: 'Buku latihan Laravel'),
                                new OA\Property(property: 'cover_img', type: 'string', example: 'http://127.0.0.1:8000/storage/books/cover1.jpg'),
                                new OA\Property(property: 'category', type: 'string', example: 'Informatika'),
                                new OA\Property(
                                    property: 'seller',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'Toko Buku Ilmiah Malang')
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Buku Tidak Ditemukan')
        ]
    )]
    public function show($id)
    {
        $book = Book::with(['category', 'seller'])->find($id);

        if (!$book) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'          => $book->id,
                'title'       => $book->title,
                'author'      => $book->author,
                'price'       => $book->price,
                'stock'       => $book->stock,
                'description' => $book->description,
                'cover_img'   => $book->cover_img,
                'category'    => $book->category ? $book->category->name : null,
                'seller'      => $book->seller ? [
                    'id'   => $book->seller->id,
                    'name' => $book->seller->name,
                ] : null,
            ]
        ], 200);
    }
}
