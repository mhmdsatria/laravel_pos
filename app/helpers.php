<?php

if (! function_exists('to_float')) {
    function to_float(mixed $val): float
    {
        if ($val === null || $val === '') {
            return 0.0;
        }
        $clean = str_replace(',', '.', (string) $val);
        return (float) $clean;
    }
}

if (! function_exists('format_qty')) {
    function format_qty(float|int|string|null $qty): string
    {
        $val = to_float($qty);
        if (floor($val) == $val) {
            return number_format($val, 0, ',', '.');
        }
        return rtrim(rtrim(number_format($val, 3, ',', '.'), '0'), ',');
    }
}
