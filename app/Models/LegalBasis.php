<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalBasis extends Model
{
    protected $fillable = [
        'slug',
        'label',
        'label_additional',
        'hint_text',
        'placeholder',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
