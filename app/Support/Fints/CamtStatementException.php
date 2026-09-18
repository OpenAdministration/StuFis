<?php

declare(strict_types=1);

namespace App\Support\Fints;

use RuntimeException;

/**
 * The camt documents of an HKCAZ response could not be turned into a usable
 * StatementOfAccount. Recoverable by design: the caller answers it by repeating the request
 * in the older MT940 format.
 */
class CamtStatementException extends RuntimeException {}
