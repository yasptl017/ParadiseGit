<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $casts = [
        'order_delete_password' => 'encrypted',
        'print_agent_key' => 'encrypted',
    ];
}
