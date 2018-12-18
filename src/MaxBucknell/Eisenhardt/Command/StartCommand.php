<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use MaxBucknell\Eisenhardt\Project;
use MaxBucknell\Eisenhardt\ProjectFactory;

/**
 * Eisenhardt start command
 *
 * Starts the servers of a Eisenhardt installation.
 */
class StartCommand extends Command
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
            ->setName('start')
            ->setDescription('Start the Eisenhardt project')
            ->setDefinition(
                new InputDefinition([
                    new InputOption(
                        'map-ports',
                        'p',
                        InputOption::VALUE_NONE,
                        'Map important ports to your host'
                    ),
                    new InputOption(
                        'no-contrib',
                        'c',
                        InputOption::VALUE_NONE,
                        'Ignore YML files from .eisenhardt/contrib/*'
                    )
                ])
            )
            ->setHelp(<<<EOT
The <info>start</> command starts the Eisenhardt environment, as if
you turned on your servers.
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

        $workingDirectory = $this->project->getInstallationDirectory();
        $output->writeln(
            "Found project in `{$workingDirectory}`.",
            OutputInterface::VERBOSITY_VERBOSE
        );
        chdir($workingDirectory);

        $p = $input->getOption('map-ports');
        $portInclude = $p ? '-f .eisenhardt/ports.yml' : '';

        $contrib = !$input->getOption('no-contrib');
        $contribInclude = $contrib ? $this->getContribInclude() : '';

        $projectName = $this->project->getProjectName();

        $output->writeln('Starting...');
        $command = <<<CMD
docker-compose                \
  -f .eisenhardt/base.yml      \
  -f .eisenhardt/dev.yml        \
  {$portInclude}                 \
  {$contribInclude}               \
  -p {$projectName}                \
  up -d --force-recreate 2> /dev/null
CMD;

        $output->writeln(
            "Running: {$command}",
            OutputInterface::VERBOSITY_VERBOSE
        );
        passthru($command);
        $output->writeln('All containers started:');
        $output->writeln('<info>Run <fg=yellow>eisenhardt info</> to view IP addresses and container statuses.</>');
    }

    private function getContribInclude()
    {
        $eisenhardtDirectory = $this->project->getEisenhardtDirectory();
        $files = \glob("{$eisenhardtDirectory}/contrib/*.yml");

        return \implode(
            " ",
            \array_map(
                function ($file) {
                    return "-f $file";
                },
                $files
            )
        );
    }
}
