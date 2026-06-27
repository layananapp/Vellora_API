<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'jenis_laporan',
        'judul',
        'deskripsi',
        'foto_bukti',
        'status',
    ];

    protected $casts = [
        'foto_bukti' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}