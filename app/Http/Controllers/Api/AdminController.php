<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LogActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AdminController extends Controller
{
    #[OA\Get(
        path: '/admin/users',
        summary: 'UC-08: Mengelola Data User & Seller - Lihat Semua User',
        tags: ['Admin'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar Pengguna Berhasil Didapatkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Data pengguna berhasil didapatkan'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'username', type: 'string', example: 'gerrardo'),
                                    new OA\Property(property: 'email', type: 'string', example: 'user@gmail.com'),
                                    new OA\Property(
                                        property: 'seller',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'name', type: 'string', example: 'Toko Buku Ilmiah Malang')
                                        ]
                                    )
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function indexUsers()
    {
        $users = User::with('seller')->get()->map(function ($user) {
            return [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'seller'   => $user->seller ? [
                    'name' => $user->seller->name
                ] : null
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Data pengguna berhasil didapatkan',
            'data'    => $users
        ], 200);
    }

    #[OA\Put(
        path: '/admin/users/{id}',
        summary: 'UC-08: Mengelola Data User & Seller - Update Status User',
        tags: ['Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'active')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status Diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Data pengguna berhasil diperbarui')
                    ]
                )
            )
        ]
    )]
    public function updateUserStatus(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update(['status' => $request->status]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data pengguna berhasil diperbarui',
            'data'    => [
                [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'email'    => $user->email,
                    'seller'   => $user->seller ? [
                        'name' => $user->seller->name
                    ] : null
                ]
            ]
        ], 200);
    }

    #[OA\Get(
        path: '/admin/categories',
        summary: 'UC-09: Get All Categories',
        tags: ['Admin'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar Kategori',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Informatika & Komputer')
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function indexCategories()
    {
        $categories = Category::all()->map(function ($cat) {
            return [
                'id'   => $cat->id,
                'name' => $cat->name,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $categories
        ], 200);
    }

    #[OA\Post(
        path: '/admin/categories',
        summary: 'UC-09: Mengelola Kategori - Tambah Kategori',
        tags: ['Admin'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Informatika & Komputer')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Kategori Berhasil Disimpan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Kategori berhasil disimpan'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Informatika & Komputer')
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::create([
            'name' => $request->name
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Kategori berhasil disimpan',
            'data'    => [
                'id'   => $category->id,
                'name' => $category->name,
            ]
        ], 201);
    }

    #[OA\Put(
        path: '/admin/categories/{id}',
        summary: 'UC-09: Edit Kategori',
        tags: ['Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Sains & Teknologi')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Kategori berhasil diperbarui')
                    ]
                )
            )
        ]
    )]
    public function updateCategory(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kategori tidak ditemukan'
            ], 404);
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

        $category->update(['name' => $request->name]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Kategori berhasil diperbarui',
            'data'    => [
                'id'   => $category->id,
                'name' => $category->name,
            ]
        ], 200);
    }

    #[OA\Delete(
        path: '/admin/categories/{id}',
        summary: 'UC-09: Hapus Kategori',
        tags: ['Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Kategori berhasil dihapus')
                    ]
                )
            )
        ]
    )]
    public function destroyCategory($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kategori tidak ditemukan'
            ], 404);
        }

        $category->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Kategori berhasil dihapus'
        ], 200);
    }

    #[OA\Get(
        path: '/admin/logs',
        summary: 'UC-10: Melihat Log Aktivitas Sistem',
        tags: ['Admin'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar Log Aktivitas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'table', type: 'string', example: 'orders'),
                                    new OA\Property(property: 'action', type: 'string', example: 'CREATE ORDER'),
                                    new OA\Property(property: 'created at', type: 'string', example: '2026-10-12 09:15:00')
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function logs()
    {
        $logs = LogActivity::latest()->get()->map(function ($log) {
            return [
                'id'         => $log->id,
                'user_id'    => $log->user_id,
                'table'      => $log->table_name,
                'action'     => $log->action,
                'created at' => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $logs
        ], 200);
    }
}
