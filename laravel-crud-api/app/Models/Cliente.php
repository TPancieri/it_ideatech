<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    // add Info para signatarios 
    protected $fillable = ['name', 'email', 'role', 'sector', 'status'];
}
