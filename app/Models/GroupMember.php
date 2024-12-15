<?php

namespace App\Models;

use App\Models\User;
use App\Models\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_id',
    ];

    // Group member belongs to group
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    // Group member belongs to user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
