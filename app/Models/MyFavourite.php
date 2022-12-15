<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MyFavourite extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['user_request_id', 'user_id', 'status'];

    public function userRequest()
    {
        return $this->belongsTo(UserRequest::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
