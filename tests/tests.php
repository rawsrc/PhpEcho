<?php declare(strict_types=1);

/**
 * TESTS ARE WRITTEN FOR EXACODIS PHP TEST ENGINE
 * AVAILABLE AT https://github.com/rawsrc/exacodis
 */

//region setup test environment
include_once '../vendor/exacodis/Pilot.php';
include_once '../vendor/exacodis/Report.php';
include_once '../vendor/exacodis/Runner.php';
include_once '../PhpEcho.php';
include_once '../ViewBuilder.php';

use Exacodis\Pilot;

$pilot = new Pilot('PhpEcho - A native PHP template engine - v.6.1.0');
$pilot->injectStandardHelpers();

include 'filepath.php';
include 'params.php';
include 'helpers.php';
include 'stdHelpers.php';
include 'core.php';
include 'options.php';
include 'autowire.php';
include 'view.php';
include 'heredoc.php';
include 'viewBuilder.php';
include 'infinite_loop.php';

$pilot->createReport();