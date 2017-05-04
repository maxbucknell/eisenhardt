<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use MaxBucknell\Eisenhardt\Command\InitCommand;
use MaxBucknell\Eisenhardt\Command\RunCommand;
use MaxBucknell\Eisenhardt\Command\StartCommand;
use MaxBucknell\Eisenhardt\Command\StopCommand;
use MaxBucknell\Eisenhardt\Command\InfoCommand;
use MaxBucknell\Eisenhardt\Command\FixPermissionsCommand;

/**
 * Main Application.
 */
class Application extends ConsoleApplication
{
    public function __construct()
    {
        parent::__construct(
            'Eisenhardt',
            '1.0.0-dev'
        );

        $this->add(new InitCommand());
        $this->add(new RunCommand());
        $this->add(new StartCommand());
        $this->add(new StopCommand());
        $this->add(new InfoCommand());
        $this->add(new FixPermissionsCommand());
    }
}
