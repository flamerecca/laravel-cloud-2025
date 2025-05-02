<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DomainTag extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function shortUrls(): BelongsToMany
    {
        return $this->belongsToMany(ShortUrl::class);
    }
}
