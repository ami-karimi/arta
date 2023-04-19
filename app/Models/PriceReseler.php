<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceReseler extends Model
{
    use HasFactory;

    protected $table = 'price_for_reseler';
    protected $guarded = ['id'];
}
