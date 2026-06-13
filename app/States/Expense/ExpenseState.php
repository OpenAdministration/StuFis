<?php

namespace App\States\Expense;

/**
 * Display states of a legacy expense (Auslage).
 *
 * The raw cases mirror AuslagenHandler2::$states and are the only values
 * written to the `auslagen.state` column. Payed is a synthetic display
 * state: the "payed" substate lives under "instructed", so it never appears
 * in the column, but Expense::displayState() surfaces it for the badge.
 * Labels live in the lang files.
 */
enum ExpenseState: string
{
    case Draft = 'draft';
    case Wip = 'wip';
    case Ok = 'ok';
    case Instructed = 'instructed';
    case Payed = 'payed';
    case Booked = 'booked';
    case Revocation = 'revocation';

    public function label(): string
    {
        return __('project.view.expenses.states.'.$this->value);
    }

    /**
     * Flux/Tailwind color name used for the status badge.
     */
    public function color(): string
    {
        return match ($this) {
            // not yet accepted
            self::Draft, self::Wip => 'zinc',
            // accepted (approved → instructed → paid)
            self::Ok, self::Instructed, self::Payed => 'sky',
            // complete
            self::Booked => 'green',
            // void
            self::Revocation => 'rose',
        };
    }
}
