<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\clients\Tours;

class Promotion extends Model
{
    use HasFactory;

    protected $table = 'tbl_promotions';

    protected $fillable = [
        'name',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'apply_type',
        'start_date',
        'end_date',
        'usage_limit',
        'per_user_limit',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'discount_value' => 'integer',
        'min_order_amount' => 'integer',
        'max_discount_amount' => 'integer',
        'usage_limit' => 'integer',
        'per_user_limit' => 'integer',
        'usage_count' => 'integer',
    ];

    /**
     * Quan hệ many-to-many với Tours
     */
    public function tours()
    {
        return $this->belongsToMany(Tours::class, 'tbl_promotion_tour', 'promotion_id', 'tour_id', 'id', 'tourId');
    }
}
