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
                        'php-version',
                        'p',
                        InputOption::VALUE_OPTIONAL,
                        'PHP version to run task as',
                        'project'
                    ),
                    new InputOption(
                        'debug',
                        'x',
                        null,
                        'Run container with Xdebug configured'
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
maxbucknell/php:*-console image, mounts the volumes and joins the
networks in the current Eisenhardt project, and executes the given
command.

If no command is supplied, an interactive terminal is opened.
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
            $projectName,
            $input->getOption('php-version'),
            $input->getOption('debug'),
            $output
        );

        $image = "maxbucknell/php:{$tag}";

        $ipAddress = trim(`hostname -I | cut -d" " -f1`);
        $width = trim(`tput cols`);

        $xdebugString = "remote_host={$ipAddress} remote_connect_back=0 xdebug.remote_mode=req xdebug.remote_port=9000";

        $command = <<<CMD
docker run                                           \
    -it                                               \
    --rm                                               \
    --volumes-from="{$projectName}_appserver_1"         \
    --net="{$networkName}"                               \
    -u "\$(id -u):10118"                                  \
    -v "/etc/passwd:/etc/passwd"                           \
    -v "\$HOME/.ssh/known_hosts:\$HOME/.ssh/known_hosts"    \
    -v "\$HOME/.config/composer:\$HOME/.composer"            \
    -v "\$HOME/.npm:\$HOME/.npm"                              \
    -v "\$HOME/.gitconfig:\$HOME/.gitconfig"                   \
    -e COMPOSER_HOME="\$HOME/.composer"                         \
    -e XDEBUG_CONFIG="{$xdebugString}"                           \
    -e PHP_IDE_CONFIG="serverName='eisenhardt'"                   \
    -v "\$SSH_AUTH_SOCK:\$SSH_AUTH_SOCK"                           \
    -e SSH_AUTH_SOCK="\$SSH_AUTH_SOCK"                              \
    -e COLUMNS={$width}                                              \
    -w "{$workingDirectory}"                                          \
    {$image}                                                           \
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
     * @param string $projectName
     * @param string $v
     * @param bool $debug Use Xdebug.
     * @param OutputInterface $output
     * @return string
     */
    private function getTag(
        string $projectName,
        string $v,
        bool $debug,
        OutputInterface $output
    ) {
        if ($v === 'project') {
            $output->writeln(
                "Collecting version information",
                OutputInterface::VERBOSITY_VERBOSE
            );

            $versionCommand = <<<CMD
docker-compose \
    -f .eisenhardt/base.yml \
    -p "{$projectName}" \
    exec \
    appserver \
    php --version | \
    head -1 | \
    cut -d" " -f 2
CMD
            ;

            $output->writeln(
                "Version command: {$versionCommand}",
                OutputInterface::VERBOSITY_VERBOSE
            );

            $fullVersion = shell_exec($versionCommand);

            $output->writeln(
                "Found version: {$fullVersion}",
                OutputInterface::VERBOSITY_VERBOSE
            );

            $versionComponents = explode('.', $fullVersion);

            $v = "{$versionComponents[0]}.{$versionComponents[1]}";
        }

        if ($debug) {
            return "{$v}-console-xdebug";
        }

        return "{$v}-console";
    }
}
