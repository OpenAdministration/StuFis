<?php

namespace App\States\Project;

use App\Rules\ExactlyOneZeroMoneyRule;
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

    #[\Override]
    public function rules(): array
    {
        // default but nothing is required but the name
        return [
            'name' => 'required|string|max:128',
            'responsible' => 'sometimes|string|max:128|email',
            'org' => 'sometimes|string|max:64',
            'protocol' => 'sometimes|nullable|string|url',
            // 'recht' => 'required|string|in:...',
            // 'recht-additional' => 'sometimes|nullable|string',
            'date_start' => 'sometimes|nullable|date',
            'date_end' => 'sometimes|nullable|date',
            'beschreibung' => ['sometimes', 'string', new FluxEditorRule],
            'posts' => 'sometimes|array|min:1',
            'posts.*.id' => 'sometimes|integer',
            // 'posts.*.titel_id' => 'sometimes|integer|exists:App\Models\Legacy\LegacyBudgetItem,id',
            'posts.*.name' => 'sometimes|string|max:128|min:1',
            'posts.*.einnahmen' => 'sometimes|money:EUR',
            'posts.*.ausgaben' => 'sometimes|money:EUR',
            'posts.*.position' => 'sometimes|integer',
            'posts.*.bemerkung' => 'sometimes|string|max:256',
        ];
    }
}
