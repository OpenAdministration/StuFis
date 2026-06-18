<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
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

    #[Scope]
    protected function ordered($query)
    {
        return $query->orderBy('sort_order');
    }

    #[Scope]
    protected function active($query)
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
