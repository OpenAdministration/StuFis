<?php

namespace framework;

use \PHPUnit\Framework\TestCase;

final class ArrayHelperTest extends TestCase
{
    private array $a1 = [
        'a' => [
            'b' => 1,
            'c' => 1,
            'd' => [
                'e' => 1,
                'f' => 1,
            ]
        ],
        'd' => 1,
    ];

    private array $a2 = [
        'a' => [
            'b' => 1,
            'd' => [
                'e' => 1,
            ]
        ],
    ];



    public function testDiff_recursive(): void
    {
        $diff12 = ArrayHelper::diff_recursive($this->a1, $this->a2);
        $res12 = [
            'a' => [
                'c' => 1,
                'd' => [
                    'f' => 1,
                ]
            ],
            'd' => 1,
        ];
        $this->assertEquals($res12, $diff12);
        $diff21 = ArrayHelper::diff_recursive($this->a2, $this->a1);
        $this->assertEquals([], $diff21);

    }

    public function testConvolve_keys(): void
    {
        $res1 = ArrayHelper::convolve_keys($this->a1);
        $conA1 = [
            'a:b' => 1,
            'a:c' => 1,
            'a:d:e' => 1,
            'a:d:f' => 1,
            'd' => 1,
        ];
        $this->assertEquals($conA1, $res1);
        $res2 = ArrayHelper::convolve_keys($this->a2);
        $conA2 = [
            'a:b' => 1,
            'a:d:e' => 1,
        ];
        $this->assertEquals($conA2, $res2);
    }
}
