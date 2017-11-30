<?php

class Helper{
    static function output($msg, $lvl = 0){
        if ($lvl == 0){
            echo "\033[1;32m[+]\033[0m " . $msg . "\n";
        }else{
            echo "\033[1;32m[+] =>\033[0m " . $msg . "\n";
        }

    }

    static function cleanName($name){

        $name = preg_replace(
            '/' .
            '\(.+\)|' .     //remove every content that inside round brackets
            '\[.+\]|' .     //remove every content that inside square brackets
            '\s|' .         //remove any whitespace
            'the|' .        //remove the leading "the"
            '\W' .          //remove any char that is not a-z and not 0-9
            '/', '', $name);

        return strtolower($name);
    }

}
