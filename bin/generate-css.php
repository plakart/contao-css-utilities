#!/usr/bin/env php
<?php

declare(strict_types=1);

use Plakart\CssUtilitiesBundle\Util\UtilityClasses;

require __DIR__.'/../vendor/autoload.php';

$target = __DIR__.'/../public/css/utilities.css';

if (!is_dir(\dirname($target))) {
    mkdir(\dirname($target), 0775, true);
}

file_put_contents($target, UtilityClasses::generateCss());

echo 'Generated '.$target."\n";
