<?php

use booking\konto\FintsController;
use Fhp\Model\StatementOfAccount\Statement;

/**
 * FintsController::convertToCent() turns the amounts of a bank statement into signed
 * cents. It is private and the controller cannot be constructed without a FinTS session,
 * so it is exercised through reflection on an uninitialised instance - the method touches
 * no state of its own.
 */
function convertToCent(string|float $amount, ?string $creditDebit = null): int
{
    static $method;
    static $controller;

    if ($method === null) {
        $method = new ReflectionMethod(FintsController::class, 'convertToCent');
        $controller = new ReflectionClass(FintsController::class)->newInstanceWithoutConstructor();
    }

    return $method->invokeArgs($controller, [$amount, $creditDebit]);
}

it('keeps the sign of an already signed amount when no credit/debit mark is given', function (string $amount, int $expected): void {
    // Regression: the sign was applied twice, so every negative amount came back positive.
    expect(convertToCent($amount))->toBe($expected);
})->with([
    'negative' => ['-123.45', -12345],
    'positive' => ['123.45', 12345],
    'zero' => ['0.00', 0],
    'smallest negative' => ['-0.01', -1],
    'large negative' => ['-10000.99', -1000099],
]);

it('takes the sign from the credit/debit mark of a bank statement', function (string $amount, string $creditDebit, int $expected): void {
    expect(convertToCent($amount, $creditDebit))->toBe($expected);
})->with([
    'credit' => ['123.45', Statement::CD_CREDIT, 12345],
    'debit' => ['123.45', Statement::CD_DEBIT, -12345],
    'credit zero' => ['0.00', Statement::CD_CREDIT, 0],
    // Banks send unsigned magnitudes; if a signed one ever arrives, the mark still wins
    // instead of cancelling out against the sign.
    'signed amount does not cancel the mark' => ['-123.45', Statement::CD_DEBIT, -12345],
]);

it('converts two-decimal amounts exactly, without float drift', function (string $amount, int $expected): void {
    // 8.20 * 100 is 819.9999... in binary floating point, so this only holds because the
    // conversion rounds instead of casting the product straight to int.
    expect(convertToCent($amount))->toBe($expected);
})->with([
    ['8.20', 820],
    ['-8.20', -820],
    ['0.07', 7],
    ['0.29', 29],
    ['1234567.89', 123456789],
]);
