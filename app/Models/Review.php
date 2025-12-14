<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\clients\Tours;
use App\Models\clients\User;

class Review extends Model
{
    use HasFactory;

    protected $table = 'tbl_reviews';

    protected $primaryKey = 'id';

    protected $fillable = [
        'tourId',
        'userId',
        'rating',
        'comment',
        'helpful_count',
        'is_visible',
        'timestamp',
    ];

    protected $casts = [
        'rating' => 'integer',
        'helpful_count' => 'integer',
        'is_visible' => 'boolean',
    ];

    /**
     * Quan hệ với Tour
     */
    public function tour()
    {
        return $this->belongsTo(Tours::class, 'tourId', 'tourId');
    }

    /**
     * Quan hệ với User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'userId');
    }
}

