<?php

if (! function_exists('format_rupiah')) {
    function format_rupiah($value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}

