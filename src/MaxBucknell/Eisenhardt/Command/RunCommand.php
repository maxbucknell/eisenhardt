<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use MaxBucknell\Eisenhardt\Project;
use MaxBucknell\Eisenhardt\ProjectFactory;

/**
 * Eisenhardt run command.
 *
 * Run an administrative command inside a Eisenhardt environment.
 */
class RunCommand extends Command
{
    /**
     * PHP Version to use for Magento 2.
     */
    const PHP_VERSION = '7.0';

    /**
     * @var Project
     */
    private $project;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Run a command inside a Eisenhardt environment')
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
                    new InputOption(
                        'dry-run',
                        '',
                        null,
                        'Outputs the command, but will not execute anything'
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
networks in the current Eisenhardt project, and executes the given
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
        $this->project = ProjectFactory::findFromWorkingDirectory();

        $command = implode(' ', $input->getArgument('container_command'));
        $relativeDirectory = $this->project->getRelativeDirectory(getcwd());
        $workingDirectory = "/mnt/magento/{$relativeDirectory}";
        $projectName = $this->project->getProjectName();
        $networkName = $this->project->getNetworkName();

        $projectDirectory = $this->project->getInstallationDirectory();
        $output->writeln(
            "Found project in `{$projectDirectory}`.",
            OutputInterface::VERBOSITY_VERBOSE
        );

        $isDryRun = $input->getOption('dry-run');

        $tag = $this->getTag(
            $input->getOption('debug'),
            $input->getOption('use-debian')
        );

        $image = "redboxdigital/docker-console:{$tag}";

        $ipAddress = trim(`hostname -I | cut -d" " -f1`);

        $command = <<<CMD
docker run                                           \
    -it                                               \
    --rm                                               \
    --volumes-from="{$projectName}_magento_appserver_1" \
    --net="{$networkName}"                               \
    -u "\$(id -u):10118"                                  \
    -v "/etc/passwd:/etc/passwd"                           \
    -v "\$HOME/.ssh/known_hosts:\$HOME/.ssh/known_hosts"    \
    -v "\$HOME/.composer:\$HOME/.composer"                   \
    -v "\$HOME/.npm:\$HOME/.npm"                              \
    -v "\$HOME/.gitconfig:\$HOME/.gitconfig"                   \
    -e COMPOSER_HOME="\$HOME/.composer"                         \
    -e XDEBUG_CONFIG="remote_host={$ipAddress} remote_connect_back=0 xdebug.remote_mode=req xdebug.remote_port=9000"                  \
    -e PHP_IDE_CONFIG="serverName=rd"                             \
    -v "\$SSH_AUTH_SOCK:\$SSH_AUTH_SOCK"                           \
    -e SSH_AUTH_SOCK="\$SSH_AUTH_SOCK"                              \
    -w "{$workingDirectory}"                                         \
    {$image}                                                         \
    {$command}
CMD
        ;

        if ($isDryRun) {
            $output->writeln($command);
        } else {
            $output->writeln(
                "Running: {$command}",
                OutputInterface::VERBOSITY_VERBOSE
            );
            passthru($command);
        }
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
        $v = static::PHP_VERSION;

        if ($debug && $debian) {
            return "{$v}-xdebug-debian";
        }

        if ($debug) {
            return "{$v}-xdebug";
        }

        if ($debian) {
            return "{$v}-debian";
        }

        return $v;
    }
}
