<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentService extends Model
{
    use HasFactory;

     protected $fillable = [
        'user_id',
        'service_type',
        'service_name',
        'short_description',
        'address',
        'city',
        'state',
        'zipcode',
        'message_number',   
        'phone_number',
        'featured_image',
        'website',
        'category_id',
        'hours',
        'latitude',
        'longitude'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
