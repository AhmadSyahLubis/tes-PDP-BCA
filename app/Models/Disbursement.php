<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disbursement extends Model
{
    protected $fillable = ['credit_application_id', 'processed_by', 'amount', 'disbursed_on', 'reference_no'];
}
