<?php

ini_set('default_charset', 'UTF-8');
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');

require __DIR__ . '/../vendor/autoload.php';

if (isset($argv[1])) {
    $controller = new \ryunosuke\HtMarkdown\Controller($_SERVER + $_ENV, $argv);
    return $controller->handleCli();
}
else {
    $controller = new \ryunosuke\HtMarkdown\Controller($_SERVER + $_ENV, $_GET + $_COOKIE);
    return $controller->handleHttp();
}
