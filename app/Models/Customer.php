<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['nik', 'name', 'birth_date', 'marital_status', 'spouse_name', 'phone', 'address'];
}
