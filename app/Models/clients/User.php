<?php

namespace App\Models\clients;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class User extends Model
{
    use HasFactory;

    protected $table = 'tbl_users';

    public function getUserId($username)
    {
        return DB::table($this->table)
            ->select('userId')
            ->where('username', $username)
            ->value('userId');
    }

    public function getUser($id)
    {
        return DB::table($this->table)
            ->where('userId', $id)
            ->first();
    }

    public function updateUser($id, $data)
    {
        return DB::table($this->table)
            ->where('userId', $id)   
            ->update($data);
    }

    public function getMyTours($id)
    {
        /** @var \Illuminate\Support\Collection<int, object> $myTours */
        $myTours = DB::table('tbl_booking')
            ->join('tbl_tours', 'tbl_booking.tourId', '=', 'tbl_tours.tourId')
            ->join('tbl_checkout', 'tbl_booking.bookingId', '=', 'tbl_checkout.bookingId')
            ->where('tbl_booking.userId', $id)
            ->orderByDesc('tbl_booking.bookingDate')
            ->take(3)
            ->get();

        foreach ($myTours as $tour) {
            /** @var object $tour */

            $tour->rating = DB::table('tbl_reviews')
                ->where('tourId', $tour->tourId)
                ->where('userId', $id)
                ->value('rating');

            $tour->images = DB::table('tbl_images')
                ->where('tourId', $tour->tourId)
                ->pluck('imageUrl');
        }

        return $myTours;
    }
}
