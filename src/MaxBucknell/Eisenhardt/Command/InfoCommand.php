<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt\Command;

use Symfony\Component\Console\Logger\ConsoleLogger;
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
        $logger = new ConsoleLogger($output);
        $project = ProjectFactory::findFromWorkingDirectory($logger);

        $infoTable = new Table($output);
        $infoTable
            ->setHeaders(['Container', 'Status', 'IP Address'])
            ->setRows(array_map(
                function ($row) {
                    return [
                        $row['name'],
                        $row['is_running'] ? '<bg=green;fg=black> UP </>' : '<bg=red;fg=white;options=bold>DOWN</>',
                        $row['ip_address'] ?? '<not running>'
                    ];
                },
                $project->getInfo()
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
