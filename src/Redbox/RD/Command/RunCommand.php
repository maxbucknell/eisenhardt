<?php
/**
 * This file is part of the redbox/rd package.
 *
 * @copyright Copyright 2017 Redbox Digital. All rights reserved.
 */

namespace Redbox\RD\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Redbox\RD\Project;
use Redbox\RD\ProjectFactory;

/**
 * Redbox Docker run command.
 *
 * Run an administrative command inside a Redbox Docker environment.
 */
class RunCommand extends Command
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->project = ProjectFactory::findFromWorkingDirectory();

        $this
            ->setName('run')
            ->setDescription('Run a command inside a Redbox Docker environment')
            ->addArgument(
                'container_command',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Command to run in container',
                [ 'bash' ]
            )
            ->setHelp(<<<EOT
The <info>run</> command creates an ephemeral container based on the
redbox-digital/docker-console image, mounts the volumes and joins the
networks in the current Redbox Docker project, and executes the given
command.
EOT
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $command = implode(' ', $input->getArgument('container_command'));
        $workingDirectory = $this->project->getRelativeDirectory(getcwd());

        passthru(<<<CMD
docker run \
    -it \
    --rm \
    --volumes-from="rd_magento_appserver_1" \
    --volumes-from="rd_frontend_appdata_1" \
    -u "\$(id -u):82" \
    -v "/etc/passwd:/etc/passwd" \
    -v "\$HOME/.ssh/known_hosts:\$HOME/.ssh/known_hosts" \
    -v "\$HOME/.composer:\$HOME/.composer" \
    -v "\$HOME/.npm:\$HOME/.npm" \
    -e COMPOSER_HOME="\$HOME/.composer" \
    -v "\$SSH_AUTH_SOCK:\$SSH_AUTH_SOCK" \
    -e SSH_AUTH_SOCK="\$SSH_AUTH_SOCK" \
    -w "{$workingDirectory}" \
    redboxdigital/docker-console:7.0 \
    {$command}
CMD
        );
    }
}
