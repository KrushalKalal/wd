<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionAnswer extends Model
{
    protected $fillable = ['visit_id', 'question_id', 'answer_text', 'answer_image', 'remark'];

    public function visit()
    {
        return $this->belongsTo(StoreVisit::class, 'visit_id');
    }
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
