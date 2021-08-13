<?php

namespace framework;

use PHPUnit\Framework\TestCase;

class NewValidatorTest extends TestCase
{
    public function testV_float()
    {
        $values = [1.0, 1.4, 1/4, "5.8"];
        $filteredValues = [];
        $v = new NewValidator();
        foreach ($values as $value){
            [, $filtered] = $v->_validate($value, 'float');
            $filteredValues[] = $filtered;
        }
        $this->assertEquals([1.0, 1.4, 1/4, 5.8], $filteredValues);
    }

    public function testV_text()
    {

    }

    public function testV_mail()
    {

    }

    public function testV_date()
    {

    }

    public function testV_domain()
    {

    }

    public function testV_phone()
    {

    }

    public function testV_regex()
    {

    }

    public function testV_password()
    {

    }

    public function testV_id()
    {

    }

    public function testV_integer()
    {

    }

    public function testV_ip()
    {

    }

    public function testV_arraymap()
    {

    }

    public function testV_url()
    {

    }

    public function testV_array()
    {

    }

    public function testV_iban()
    {

    }
}
