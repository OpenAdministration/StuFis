<?php

namespace App\States\Project;

use App\Rules\FluxEditorRule;

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

    public function budgetRules(): array
    {
        return [
            'posts.*.titel_id' => 'sometimes|nullable|integer|exists:App\Models\Legacy\LegacyBudgetItem,id',
        ];
    }

    public function approvalRules(): array
    {
        return [
            'recht' => 'sometimes|nullable|string|exists:App\Models\Legacy\LegalBase,slug',
            'recht-additional' => 'sometimes|nullable|string',
        ];
    }
}
