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
        switch (PHP_OS) {
            case "Darwin":
                return static::MACOS;
            default:
                return static::NATIVE;
        }
    }
}
