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
            "magento/project-{$params->getMagentoEdition()}-edition:{$params->getMagentoVersion()}",
            '.'
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

    /**
     * Run the setup:install command against a Magento installation.
     *
     * @param string $directory
     * @param array $arguments
     * @param LoggerInterface $logger
     */
    public static function installMagento(
        string $directory,
        array $arguments,
        LoggerInterface $logger
    ) {
        $executable = "{$directory}/bin/magento";

        $command = [
            $executable,
            'setup:install'
        ];

        foreach ($arguments as $key => $value) {
            $command[] = "--{$key}=$value";
        }

        $implodedCommand = \implode(" \\\n    ", $command);
        $logger->info("Running command:\n{$implodedCommand}");

        $printedCommand = \print_r($command, true);
        $logger->debug("Actual command:\n[$printedCommand}");

        $process = new Process(
            $command,
            $directory
        );

        $process->mustRun();

        $logger->info("Command stdout:\n{$process->getOutput()}\n");
        $logger->debug("Command stderr:\n{$process->getErrorOutput()}\n");
    }
}
