<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewayConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'mode',
        'api_key',
        'api_secret',
        'is_active',
    ];
}