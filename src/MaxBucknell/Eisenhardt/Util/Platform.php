<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt\Util;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class Platform
{
    const MACOS = 'macos';

    const NATIVE = 'native';

    public static function getOperatingSystem(LoggerInterface $logger)
    {
        $command = [
            'docker',
            'info',
            '--format',
            '{{.OperatingSystem}}'
        ];

        $process = new Process($command);

        $printedCommand = \print_r($command, true);
        $logger->debug("Actual command:\n[$printedCommand}");

        $process->mustRun();

        $output = $process->getOutput();

        $logger->debug("Command stdout:\n{$process->getOutput()}\n");
        $logger->debug("Command stderr:\n{$process->getErrorOutput()}\n");

        if (\trim($output) === 'Docker for Mac') {
            return static::MACOS;
        } else {
            return static::NATIVE;
        }
    }
}
