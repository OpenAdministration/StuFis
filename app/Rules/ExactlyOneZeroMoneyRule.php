<?php

namespace App\Rules;

use App\Support\Money\MoneyInput;
use Cknow\Money\Money;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Validates that exactly one of two paired money fields (a Posten's Einnahme and
 * Ausgabe) is zero — a Posten is either income or expense, never both or neither.
 *
 * $otherField is the sibling field's rule identifier and may contain `*` wildcard
 * segments (e.g. `posts.*.einnahmen`). Each `*` is resolved against the corresponding
 * segment of the concrete $attribute under validation (e.g. `posts.0.ausgaben`) to
 * build the sibling's actual data path (`posts.0.einnahmen`), which is then looked
 * up in the data captured via setData().
 */
class ExactlyOneZeroMoneyRule implements DataAwareRule, ValidationRule
{
    private array $data = [];

    public function __construct(
        protected string $otherField,
    ) {}

    /**
     * @return $this
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // pairs every attribute field with the other field, pair[0] has the actual field, pair[1] can have *'s
        $otherPath = Str::of($attribute)->explode('.')
            ->zip(Str::of($this->otherField)->explode('.'))
            ->map(fn (Collection $pair) => $pair[1] === '*' ? $pair[0] : $pair[1])
            ->implode('.');
        $otherMoney = data_get($this->data, $otherPath);

        // Both sides can arrive as the raw string of a money input (see MoneyInput). Only a value
        // that is no money value at all (null, an array) is skipped; an unparsable string reads as
        // 0 here, and either way the `money` rule on each of the two fields reports it.
        $value = MoneyInput::parse($value);
        $otherMoney = MoneyInput::parse($otherMoney);
        if (! $value instanceof Money || ! $otherMoney instanceof Money) {
            return;
        }

        $oneIsZero = ($value->isZero() xor $otherMoney->isZero());
        if (! $oneIsZero) {
            // :position is filled in by the validator from the attribute's row index (1-based),
            // so the message can name the Posten without this rule computing anything.
            $fail(__('errors.one-money-has-to-be-zero'));
        }
    }
}
