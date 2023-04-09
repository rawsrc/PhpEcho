<?php

/**
 * TESTS ARE WRITTEN FOR EXACODIS PHP TEST ENGINE
 * AVAILABLE AT https://github.com/rawsrc/exacodis
 *
 * To run the tests, you must only define a db user granted with all privileges
 */

declare(strict_types=1);

//region setup test environment
include_once '../vendor/exacodis/Pilot.php';
include_once '../PhpEcho.php';

use Exacodis\Pilot;

$pilot = new Pilot('PhpEcho - A native PHP template engine - v.5.3.1');
$pilot->injectStandardHelpers();

include 'filepath.php';
include 'params.php';
include 'helpers.php';
include 'stdHelpers.php';
include 'core.php';
include 'view.php';
include 'heredoc.php';

$pilot->createReport();