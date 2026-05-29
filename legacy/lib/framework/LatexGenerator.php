<?php

namespace framework;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use PhpLatexRenderer\LatexRenderer;

/**
 * Wraps lib
 */
class LatexGenerator extends LatexRenderer
{
    public function __construct()
    {
        parent::__construct(
            SYSBASE.'/template/tex/',
            SYSBASE.'/runtime/',
            '/usr/bin/pdflatex',
            DEV
        );
        $logger = new Logger('twig-tex', [new RotatingFileHandler(SYSBASE.'/runtime/logs/tex.log', 5)]);
        $this->setLogger($logger);
    }
}
