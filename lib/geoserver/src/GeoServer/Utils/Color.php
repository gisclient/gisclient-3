<?php

namespace GisClient\GeoServer\Utils;

class Color
{

    /**
     * Convert a color string spaced-separated to a #RRGGBB string
     * @param type $msColorText
     * @return string
     * @throws \Exception
     */
    static public function convert($csColorText)
    {
        $array = explode(' ', trim($csColorText));
        if (count($array) <> 3) {
            throw new \Exception("Invalid color \"{$csColorText}\"");
        }
        return '#'.
            str_pad(dechex(trim($array[0])), 2, '0', STR_PAD_LEFT).
            str_pad(dechex(trim($array[1])), 2, '0', STR_PAD_LEFT).
            str_pad(dechex(trim($array[2])), 2, '0', STR_PAD_LEFT);
    }
}