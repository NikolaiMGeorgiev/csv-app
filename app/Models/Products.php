<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Products extends Model {
    public $timestamps = false;
    protected $fillable = [
        'name',
        'description',
        'price',
        'users_id'
    ];
}
