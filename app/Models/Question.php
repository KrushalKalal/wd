<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['question_text', 'is_active', 'is_count'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_count' => 'boolean',
    ];


    public function answers()
    {
        return $this->hasMany(QuestionAnswer::class, 'question_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
