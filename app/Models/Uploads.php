<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Uploads extends Model {
    protected $fillable = [
        'users_id',
        'file_path',
        'status',
        'created_at',
    ];

}
