<?php

namespace App\Models;

use Database\Factories\AnnouncementCommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnouncementComment extends Model
{
    /** @use HasFactory<AnnouncementCommentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'announcement_id',
        'user_id',
        'content',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(AnnouncementComment::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AnnouncementComment::class, 'parent_id');
    }
}
