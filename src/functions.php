<?php

use Carbon\Carbon;

if (! function_exists('today')) {
    function today(): Carbon
    {
        return Carbon::today();
    }
}

if (! function_exists('class_basename')) {
    function class_basename(string|object $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}
