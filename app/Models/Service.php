<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
         'category_name', 'image', 'category_type'
    ];

    public static $rules = [
        'category_name' => 'required|unique:services',
        'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
    ];
}
