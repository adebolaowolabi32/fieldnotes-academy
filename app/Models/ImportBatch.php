<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'user_id', 'path', 'status', 'cursor', 'total', 'errors'];

    protected function casts(): array
    {
        return ['errors' => 'array'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
