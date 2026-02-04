<?php

if (!function_exists('format_rupiah')) {
    function format_rupiah($number, $decimal = 2) {
        if (!is_numeric($number)) {
            return $number;
        }
        
        return 'Rp ' . number_format($number, $decimal, ',', '.');
    }
}