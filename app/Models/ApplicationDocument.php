<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    protected $fillable = ['credit_application_id', 'uploaded_by', 'type', 'original_name', 'path', 'mime_type'];
}
