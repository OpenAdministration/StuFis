<?php


namespace framework;


class ArrayHelper
{
    /**
     * @param array $a1
     * @param array $a2
     * @return array a1 without a2
     */
    public static function diff_recursive(array $a1, array $a2): array
    {
        $result = [];
        foreach ($a1 as $k => $v){
            if(!isset($a2[$k])){
                $result[$k] = $v;
            }
            if(isset($a2[$k]) && $a2[$k] !== $v){
                // if entries differ add
                $diff = self::diff_recursive($v, $a2[$k]);
                if(count($diff) !== 0){
                    $result[$k] = $diff;
                }
            } // else dont add it
        }
        return $result;
    }

    /**
     * @param array $a example: [a => [b => 1], d => 0] to [a:b => 1, d => 0]
     * @param string $delimiter default ':'
     * @return array returns array of depth 1 with convoluted keys, only tree-leaves maintain in this array values
     */
    public static function convolve_keys(array $a, string $delimiter = ":") : array
    {
        $out = [];
        foreach ($a as $k => $v){
            if(is_array($v)){
                foreach ($v as $k2 => $v2){
                    if(is_array($v2)){
                        foreach (self::convolve_keys($v2) as $k3 => $v3){
                            $out[$k . $delimiter . $k2 . $delimiter . $k3] = $v3;
                        }
                    }else{
                        $out[$k . ':' . $k2] = $v2;
                    }
                }
            }else{
                $out[$k] = $v;
            }
        }
        return $out;
    }

    public static function remove(array &$array, int|string $key) : mixed
    {
        if(!isset($array[$key])){
            throw new \InvalidArgumentException('Key not found');
        }
        $el = $array[$key];
        unset($array[$key]);
        return $el;
    }
}