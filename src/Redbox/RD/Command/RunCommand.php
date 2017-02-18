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
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
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
            ->setDefinition(
                new InputDefinition([
                    new InputOption(
                        'debug',
                        'x',
                        null,
                        'Run container with Xdebug configured'
                    ),
                    new InputOption(
                        'use-debian',
                        'd',
                        null,
                        'Run container as Debian rather than Alpine'
                    ),
                    new InputArgument(
                        'container_command',
                        InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                        'Command to run in container',
                        [ 'bash' ]
                    )
                ])
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
        $relativeDirectory = $this->project->getRelativeDirectory(getcwd());
        $workingDirectory = "/mnt/magento/{$relativeDirectory}";
        $projectName = $this->project->getProjectName();
        $networkName = $this->project->getNetworkName();

        $tag = $this->getTag(
            $input->getOption('debug'),
            $input->getOption('use-debian')
        );
        $image = "redboxdigital/docker-console:{$tag}";

        passthru(<<<CMD
docker run                                           \
    -it                                               \
    --rm                                               \
    --volumes-from="{$projectName}_magento_appserver_1" \
    --network="{$networkName}"                           \
    -u "\$(id -u):82"                                     \
    -v "/etc/passwd:/etc/passwd"                           \
    -v "\$HOME/.ssh/known_hosts:\$HOME/.ssh/known_hosts"    \
    -v "\$HOME/.composer:\$HOME/.composer"                   \
    -v "\$HOME/.npm:\$HOME/.npm"                              \
    -e COMPOSER_HOME="\$HOME/.composer"                        \
    -e XDEBUG_CONFIG="idekey=docker"                            \
    -v "\$SSH_AUTH_SOCK:\$SSH_AUTH_SOCK"                         \
    -e SSH_AUTH_SOCK="\$SSH_AUTH_SOCK"                            \
    -w "{$workingDirectory}"                                       \
    {$image}                                                        \
    {$command}
CMD
        );
    }

    /**
     * Get Console Container Tag.
     *
     * Depending on the circumstances, a different container version may
     * be used.
     *
     * @param bool $debug Use Xdebug.
     * @param bool $debian Use Debian.
     * @return string
     */
    private function getTag(
        bool $debug,
        bool $debian
    ) {
        if ($debug && $debian) {
            return 'redboxdigital/docker-console:7.0-xdebug-debian';
        }

        if ($debug) {
            return 'redboxdigital/docker-console:7.0-xdebug';
        }

        if ($debian) {
            return 'redboxdigital/docker-console:7.0-debian';
        }

        return 'redboxdigital/docker-console:7.0';
    }
}
