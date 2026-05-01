<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Processo extends Model
{
    use HasFactory;
    // add Info para signatarios 
    protected $fillable = ['title','description','status','responsible_user_id','category',];
}
