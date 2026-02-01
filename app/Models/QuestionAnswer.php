<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionAnswer extends Model
{
    protected $fillable = [
        'visit_id',
        'question_id',
        'answer_text',
        'answer_image',
        'remark',
        'admin_status',
        'admin_remark',
        'reviewed_by',
        'reviewed_at'
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(StoreVisit::class, 'visit_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('admin_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('admin_status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('admin_status', 'rejected');
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('admin_status', 'needs_review');
    }
}