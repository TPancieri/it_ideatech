<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    //Nome e Email
    protected $fillable = ['nome', 'email'];
}
