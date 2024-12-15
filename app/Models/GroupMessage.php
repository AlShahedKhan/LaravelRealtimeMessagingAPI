<?php

namespace App\Models;

use App\Models\User;
use App\Models\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GroupMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'sender_id',
        'message',
        'file_path',
    ];

    // Group message belongs to group
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    // Group message belongs to user
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
