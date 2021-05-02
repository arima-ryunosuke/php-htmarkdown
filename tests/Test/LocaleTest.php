<?php
namespace ryunosuke\Test;

class LocaleTest extends \ryunosuke\Test\AbstractTestCase
{
    function test_()
    {
        $files = glob(__DIR__ . '/../../src/locale/*.php');
        $file = require array_shift($files);
        foreach ($files as $locale) {
            that(array_keys(require $locale))->isSame(array_keys($file));
        }
    }
}
