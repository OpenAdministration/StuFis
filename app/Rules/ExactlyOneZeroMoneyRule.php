<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExactlyOneZeroMoneyRule implements DataAwareRule, ValidationRule
{
    private array $data = [];

    public function __construct(
        protected string $otherField,
    ) {}

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // pairs every attribute field with the other field, pair[0] has the actual field, pair[1] can have *'s
        $otherAccessors = Str::of($attribute)->explode('.')
            ->zip(Str::of($this->otherField)->explode('.'))
            ->map(fn (Collection $pair) => $pair[1] === '*' ? $pair[0] : $pair[1]);
        // dump($otherAccessors);
        $otherMoney = $this->data;
        while (($idx = $otherAccessors->shift()) !== null) {
            // dump($otherMoney, $idx, $otherAccessors);
            $otherMoney = $otherMoney[$idx];
        }
        // dd($otherMoney);
        $oneIsZero = (($value->getAmount() === '0') xor ($otherMoney->getAmount() === '0'));
        // dd($value, $otherMoney, ($value->getAmount() === "0"),($otherMoney->getAmount() === "0"), $oneIsZero);
        if (! $oneIsZero) {
            $fail(__('errors.one-money-has-to-be-zero'));
        }
    }
}
