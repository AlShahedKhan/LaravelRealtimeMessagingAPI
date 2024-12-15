<?php

namespace App\Models;

use App\Models\GroupMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'creator_id',
    ];

    // Group have many members
    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }
    // Group have many messages
    public function messages()
    {
        return $this->hasMany(GroupMessage::class);
    }
}
