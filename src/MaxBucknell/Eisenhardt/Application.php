<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt;

use MaxBucknell\Eisenhardt\Command\StandupCommand;
use MaxBucknell\Eisenhardt\Command\SyncCommand;
use Symfony\Component\Console\Application as ConsoleApplication;

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

        $this->addCommands([
            new InitCommand(),
            new RunCommand(),
            new StartCommand(),
            new StopCommand(),
            new InfoCommand(),
            new FixPermissionsCommand(),
            new StandupCommand(),
            new SyncCommand()
        ]);
    }
}
