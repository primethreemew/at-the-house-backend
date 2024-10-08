<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    //protected $table = 'referrals';

    // Define the fillable attributes if necessary
    protected $fillable = ['referrer_id', 'agent_service_id', 'status'];

}
