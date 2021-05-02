<?php

require_once __DIR__ . '/../vendor/autoload.php';

function that($value)
{
    return new \ryunosuke\PHPUnit\Actual($value);
}
