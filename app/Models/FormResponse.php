<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormResponse extends Model
{
    protected $fillable = [
        'form_id',
        'submitted_by_ip',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function values()
    {
        return $this->hasMany(FormResponseValue::class);
    }
}