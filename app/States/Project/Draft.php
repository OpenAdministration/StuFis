<?php

namespace App\States\Project;

use App\Models\Legacy\LegacyBudgetItem;
use App\Models\LegalBasis;
use App\Rules\FluxEditorRule;
use Illuminate\Validation\Rule;

class Draft extends ProjectState
{
    public static string $name = 'draft';

    #[\Override]
    public function iconName(): string
    {
        return 'fas-file-pen';

    }

    #[\Override]
    public function color(): string
    {
        return 'zinc';
    }

    #[\Override]
    public function basicRules(): array
    {
        return [
            'name' => 'required|string|max:128',
            'responsible' => 'sometimes|string|max:128|email:rfc,dns',
            'org' => 'sometimes|string|max:64',
            'protocol' => 'sometimes|nullable|string|url',
            'date_start' => 'sometimes|nullable|date',
            'date_end' => 'sometimes|nullable|date',
            'beschreibung' => ['sometimes', 'string', new FluxEditorRule],
            'posts' => 'sometimes|array|min:1',
            'posts.*.id' => 'sometimes|integer',
            'posts.*.name' => 'sometimes|string|max:128|min:1',
            'posts.*.einnahmen' => 'sometimes|money:EUR',
            'posts.*.ausgaben' => 'sometimes|money:EUR',
            'posts.*.position' => 'sometimes|integer',
            'posts.*.bemerkung' => 'sometimes|string|max:256',
        ];
    }

    #[\Override]
    public function budgetRules(): array
    {
        return [
            'posts.*.titel_id' => ['sometimes', 'nullable', 'integer', Rule::exists(LegacyBudgetItem::class, 'id')],
        ];
    }

    #[\Override]
    public function approvalRules(): array
    {
        return [
            'recht' => ['sometimes', 'nullable', 'string', Rule::exists(LegalBasis::class, 'slug')],
            'recht_additional' => 'sometimes|nullable|string',
        ];
    }
}
