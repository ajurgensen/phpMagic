<?php 

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use ajurgensen\phpMagic\pageMagic;

echo pageMagic::staticTest();
