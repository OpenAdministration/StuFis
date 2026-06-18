<?php

use App\Models\Legacy\Expense;

it('should transfer the state in auslagenerstattung', function (): void {
    // create an expense in stated paid
    $expense = Expense::factory(2)
        ->state('payed')
        ->make();
    // create matching payment

    // create booking instruction

    // confirm booking instruction

    // have a look if state is shifted to booked
})->todo();
