<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContactModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_contact';

    public function getContacts()
    {
        // Lấy tất cả liên hệ, sắp xếp: chưa phản hồi trước, sau đó mới đến đã phản hồi
        return DB::table($this->table)
            ->orderByRaw("CASE WHEN isReply = 'n' THEN 0 ELSE 1 END")
            ->orderBy('contactId', 'desc')
            ->get();
    }

    public function updateContact($contactId, $data)
    {
        return DB::table($this->table)
            ->where('contactId', $contactId)
            ->update($data);
    }

    public function countContactsUnread()
    {
        $contacts = DB::table($this->table)
            ->where('isReply', 'n')
            ->orderBy('contactId', 'desc')
            ->get();

        $countUnread = $contacts->count(); // Đếm số lượng thư chưa trả lời

        return [
            'countUnread' => $countUnread,
            'contacts' => $contacts
        ];
    }


}
