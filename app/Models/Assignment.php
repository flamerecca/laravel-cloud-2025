<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = [
        'name',
        'email',
        'file_path',
        'original_name',
    ];
}
