<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryPostcodeCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'postcode',
        'delivery_fee',
        'status',
    ];
}
