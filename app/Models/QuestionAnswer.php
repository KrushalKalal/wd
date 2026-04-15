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
        'count',
        'remark',
        'admin_status',
        'admin_remark',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'count' => 'integer',
        'is_breakage' => 'boolean',    // appended virtual attribute
    ];

    protected $appends = ['is_breakage'];

    /**
     * Virtual attribute — true when this answer is for the breakage question.
     * Delegates to Question::is_breakage so the logic lives in one place.
     * Requires the 'question' relation to be eager-loaded; returns false safely if not.
     */
    public function getIsBreakageAttribute(): bool
    {
        return $this->relationLoaded('question')
            ? (bool) ($this->question?->is_breakage)
            : false;
    }

    // ── Relations ────────────────────────────────────────────────────────────

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

    // ── Scopes ───────────────────────────────────────────────────────────────

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