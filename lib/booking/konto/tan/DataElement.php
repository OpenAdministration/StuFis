<?php

namespace booking\konto\tan;


use InvalidArgumentException;

class DataElement
{
    public const ENC_ASCII = '1';
    public const ENC_ASC = self::ENC_ASCII;
    public const ENC_BCD = '0';

    protected string $enc;
    protected string $data;
    protected string $headerHighBit;

    public static function parseNextBlock($challenge) : array
    {
        if(empty($challenge)){
            return [$challenge, new self('')];
        }
        $length = (int) substr($challenge, 0,2);
        $data = substr($challenge, 2, $length);
        if(strlen($data) !== $length){
            throw new InvalidArgumentException("Parsing went wromg");
        }
        $rest = substr($challenge, 2 + $length);
        return [$rest, new self($data)];
    }

    public function __construct(string $data)
    {
        $this->data = $data;
        $this->headerHighBit = 0;
        if(is_numeric($this->data) || empty($this->data)){
            $this->enc = self::ENC_BCD;
        }else{
            $this->enc = self::ENC_ASC;
        }
    }

    /**
     * @return int amount of bytes in data
     */
    protected function getLength() : int
    {
        if($this->enc === self::ENC_BCD){
            return ceil(strlen($this->data)/2);
        }
        return strlen($this->data);
    }

    public function getHeaderHex() : string
    {
        $lengthBin = str_pad(base_convert($this->getLength(),10, 2), 6, '0', STR_PAD_LEFT);
        $headerHex = base_convert($this->headerHighBit . $this->enc . $lengthBin,2,16);
        return str_pad($headerHex, 2, '0', STR_PAD_LEFT);
    }

    public function getDataHex() : string
    {
        if($this->enc === self::ENC_BCD){
            // base 10 and hex BCD encoded numbers are the same in range 0 to 9
            $hexData = $this->data;
            // Pad on Byte lenght
            if(strlen($hexData) % 2 === 1){
                $hexData .= "F";
            }
            return $hexData;
        }
        // ASCII encoding
        $hexData = '';
        foreach (str_split($this->data) as $char){
            $hexData .= base_convert(ord($char), 10,16);
        }
        return $hexData;
    }

    public function toHex() : string
    {
        if(empty($this->data)){
            return '';
        }
        return $this->getHeaderHex() . $this->getDataHex();
    }

    public static function hexToByte(string $hex, int $length = 8) : string
    {
        $byte = base_convert($hex, 16,2);
        return str_pad($byte, $length, '0', STR_PAD_LEFT);
    }

    public function getLuhnChecksum() : int
    {
        return $this->calcLuhn($this->getDataHex());
    }

    protected function calcLuhn(string $hex) : int
    {
        $sum = 0;
        $doubleIt = false;
        foreach (str_split($hex) as $char){
            $number = (int) base_convert($char, 16,10);
            if($doubleIt){
                $number *= 2;
                $decRep = str_split($number);
                foreach ($decRep as $value){
                    $sum += (int) $value;
                }
            }else{
                $sum += $number;
            }
            $doubleIt = !$doubleIt;
        }
        return $sum;
    }

    public function __debugInfo() :  ?array
    {
        return [
            'header' => $this->getHeaderHex(),
            'data' => $this->data,
            'hex-data' => $this->getDataHex(),
            'hex' => $this->toHex(),
            'luhn' => $this->getLuhnChecksum(),
        ];
    }



}