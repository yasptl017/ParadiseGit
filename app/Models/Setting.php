<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $casts = [
        'order_delete_password' => 'encrypted',
    ];

    public function getPrintAgentKeyAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            return $value;
        }
    }

    public function setPrintAgentKeyAttribute($value)
    {
        $this->attributes['print_agent_key'] = empty($value) ? $value : Crypt::encryptString($value);
    }
}
