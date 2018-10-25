<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use MaxBucknell\Eisenhardt\Project;
use MaxBucknell\Eisenhardt\ProjectFactory;

/**
 * Get information about the Eisenhardt project.
 */
class InfoCommand extends Command
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
            ->setName('info')
            ->setDescription('Get information about Eisenhardt installation')
            ->setHelp(<<<EOT
The <info>info</> command tells you IP addresses, port mappings, and
enabled configuration inside the current Eisenhardt installation.
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

        $projectName = $this->project->getProjectName();
        chdir($workingDirectory);

        // Docker compose has a helpful table formatter which divines
        // the width of the terminal using stty. If this value is small
        // enough, it will wrap the table.
        //
        // This is unfortunate, since we are parsing these thing
        // ourselves, we trick it into thinking the width is bigger than
        // it actually is.
        //
        // Yawn.
        $columnSize = explode(' ', shell_exec('stty size'))[1];

        $output->writeln(
            "Collected terminal width as {$columnSize}",
            OutputInterface::VERBOSITY_VERBOSE
        );

        // Set width to something adequate.
        $output->writeln(
            "Setting terminal width to 3000",
            OutputInterface::VERBOSITY_VERBOSE
        );
        shell_exec('stty columns 3000');

        $command = <<<CMD
docker-compose           \
  -f .eisenhardt/base.yml \
  -f .eisenhardt/dev.yml   \
  -p {$projectName}         \
  ps
CMD
        ;

        $output->writeln(
            "Running: {$command}",
            OutputInterface::VERBOSITY_VERBOSE
        );

        $result = shell_exec($command);

        // Restore old width
        $output->writeln(
            "Setting terminal width to {$columnSize}",
            OutputInterface::VERBOSITY_VERBOSE
        );
        shell_exec("stty columns {$columnSize}");

        $rows = explode("\n", $result);
        $containers = array_slice($rows, 2, -1);
        $rows = array_map(
            function ($container) {
                $row = [];
                $isUp = strpos($container, 'Up') !== false;

                $row['name'] = explode(" ", $container)[0];
                $row['status'] = $isUp ? '<bg=green;fg=black> UP </>' : '<bg=red;fg=white;options=bold>DOWN</>';
                $row['ip'] = $isUp ? $this->getIPAddress($row['name']) : '<not running>';

                return $row;
            },
            $containers
        );

        $infoTable = new Table($output);
        $infoTable
            ->setHeaders(['Container', 'Status', 'IP Address'])
            ->setRows(array_map(
                function ($row) {
                    return [
                        $row['name'],
                        $row['status'],
                        $row['ip']
                    ];
                },
                $rows
            ))
            ->render();
    }

    private function getIPAddress($containerName)
    {
        $networkName = $this->project->getNetworkName();
        $infoJson = shell_exec(<<<CMD
docker inspect {$containerName}
CMD
        );

        $info = json_decode($infoJson, true);

        return $info[0]['NetworkSettings']['Networks'][$networkName]['IPAddress'];
    }
}
