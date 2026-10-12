<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogActivity extends Model
{
    use HasFactory;

    protected $table = 'log_activities';

    protected $fillable = [
        'user_id',
        'table',
        'action',
    ];

    // Relasi: Log dicatat dari aksi 1 User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}