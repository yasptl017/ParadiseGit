<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Homepage;
class Category extends Model
{
    use HasFactory;

    protected $casts = [
        'show_on_place_order_receipt' => 'boolean',
    ];

    public function products(){
        return $this->hasMany(Product::class);
    }

    public function menu_products(){
        return $this->hasMany(Product::class);
    }

}
