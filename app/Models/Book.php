<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'category_id',
        'title',
        'author',
        'price',
        'stock',
        'description',
        'cover_img',
    ];

    // Relasi: Buku dimiliki oleh 1 Seller (Toko)
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    // Relasi: Buku masuk ke 1 Kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}