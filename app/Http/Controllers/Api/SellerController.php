<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SellerController extends Controller
{
    // UC-01b: Registrasi / Buka Toko Seller
    public function registerSeller(Request $request)
    {
        $user = $request->user();

        // Cek jika user sudah punya toko
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
            'message' => 'Berhasil mendaftar sebagai Seller',
            'data'    => $seller
        ], 201);
    }

    // UC-06: Tambah Produk Buku Baru
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

        $book = Book::create([
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
            'message' => 'Buku berhasil ditambahkan',
            'data'    => $book
        ], 201);
    }

    // UC-06: Melihat Daftar Buku Milik Seller Ini
    public function myBooks(Request $request)
    {
        $seller = $request->user()->seller;

        if (!$seller) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda belum terdaftar sebagai Seller'
            ], 403);
        }

        $books = Book::with('category')->where('seller_id', $seller->id)->get();

        return response()->json([
            'status' => 'success',
            'data'   => $books
        ], 200);
    }
}
