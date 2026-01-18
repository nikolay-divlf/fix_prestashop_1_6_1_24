<?php

class Tools extends ToolsCore
{
    public static function toCamelCase($str, $catapitalise_first_char = false)
    {
        $str = Tools::strtolower($str);
        if ($catapitalise_first_char) {
            $str = Tools::ucfirst($str);
        }
        //return preg_replace_callback('/_+([a-z])/', create_function('$c', 'return strtoupper($c[1]);'), $str);
        return preg_replace_callback(
            '/_+([a-z])/',
            function ($c) {
                return strtoupper($c[1]);
            },
            $str
        );
    }
}