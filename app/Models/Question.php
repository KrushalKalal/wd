<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['question_text', 'is_active', 'is_count'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_count' => 'boolean',
        'is_breakage' => 'boolean',   // appended virtual attribute
    ];

    protected $appends = ['is_breakage'];

    /**
     * Virtual attribute — true when this is the breakage count question.
     * Identified by: is_count = true AND question_text contains "breakage" (case-insensitive).
     * No DB column needed.
     */
    public function getIsBreakageAttribute(): bool
    {
        return $this->is_count &&
            stripos($this->question_text ?? '', 'breakage') !== false;
    }

    /**
     * Helper method — same logic, callable anywhere.
     */
    public function isBreakage(): bool
    {
        return $this->is_breakage;
    }

    public function answers()
    {
        return $this->hasMany(QuestionAnswer::class, 'question_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}