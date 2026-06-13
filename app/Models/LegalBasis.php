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
        'is_active',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    protected function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasAdditionalField(): bool
    {
        return ! empty($this->label_additional);
    }

    public function hasHintText(): bool
    {
        return ! empty($this->hint_text);
    }
}
