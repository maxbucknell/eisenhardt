<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt\Util;

use MaxBucknell\Eisenhardt\StandupParams;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * @author Max Bucknell <max@wearejh.com>
 */
class MagentoInstallation
{
    /**
     * Initialise a Magento 2 Composer project in the given directory.
     *
     * @param string $directory
     * @param StandupParams $params
     * @param LoggerInterface $logger
     * @return string
     *
     */
    public static function create(
        string $directory,
        StandupParams $params,
        LoggerInterface $logger
    ) {
        \mkdir($directory, 0744, true);

        $command = [
            'composer',
            'create-project',
            '--repository-url=https://repo.magento.com/',
            '--no-install',
            "magento/project-{$params->getMagentoEdition()}:{$params->getMagentoVersion()}"
        ];

        $implodedCommand = \implode(" \\\n    ", $command);
        $logger->info("Running command:\n{$implodedCommand}");

        $printedCommand = \print_r($command, true);
        $logger->debug("Actual command:\n[$printedCommand}");

        $process = new Process($command, $directory);

        $process->mustRun();

        $logger->info("Command stdout:\n{$process->getOutput()}\n");
        $logger->debug("Command stderr:\n{$process->getErrorOutput()}\n");
    }
}
