<?php

namespace app\core\Helpers;

class DebugHelper {
    public static function dump($var, $exit = true) {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
        if ($exit) {
            exit;
        }
    }

    public static function debugPrint($var, $exit = true) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
        if ($exit) {
            exit;
        }
    }

    public static function log($var, $exit = true) {
        error_log($var);
        if ($exit) {
            exit;
        }
    }

    public static function logFile($var, $file) {
        file_put_contents($file, $var);
    }

    public static function logArray($var){
        error_log(print_r($var,true));
    }
}