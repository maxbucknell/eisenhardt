<?php
/**
 * This file is part of the redbox/rd package.
 *
 * @copyright Copyright 2017 Redbox Digital. All rights reserved.
 */

namespace Redbox\RD;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Redbox\RD\Command\InitCommand;
use Redbox\RD\Command\RunCommand;

/**
 * Main Application.
 */
class Application extends ConsoleApplication
{
    public function __construct()
    {
        parent::__construct(
            'Redbox Docker',
            '1.0.0-dev'
        );

        $this->add(new InitCommand());
        $this->add(new RunCommand());
    }
}
