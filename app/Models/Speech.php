<?php

namespace App\Models;

use Database\Factories\SpeechFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Speech extends Model
{
    /** @use HasFactory<SpeechFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'speaker_id',
        'fee',
        'status',
        'scheduled_at',
        'created_by',
    ];

    public function scopeByStatus($query, $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeByDateRange($query, $start, $end)
    {
        if ($start && $end) {
            return $query->whereBetween('scheduled_at', [$start, $end]);
        }
        return $query;
    }

    public function scopeBySpeaker($query, $speakerId)
    {
        return $speakerId ? $query->where('speaker_id', $speakerId) : $query;
    }

    public function scopeVisibleToUser($query, $user)
    {
        if ($user->hasRole('admin')) {
            return $query;
        }
        return $query->where('created_by', $user->id);
    }
}
