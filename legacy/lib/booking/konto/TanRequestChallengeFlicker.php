<?php

namespace booking\konto;

use Fhp\Syntax\Bin;

class TanRequestChallengeFlicker
{
    // https://6xq.net/flickercodes/

    public function __construct(Bin $bin)
    {
        $data = $bin->getData();

        $lengthAfter = ord($data[0]);

        var_dump([
            'data' => $data,
            'bytes' => strlen($data),
            'length' => $lengthAfter,
        ]);
    }
}
