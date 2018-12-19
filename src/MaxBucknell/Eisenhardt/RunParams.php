<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt;

class RunParams
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var string
     */
    private $phpVersion;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var string
     */
    private $workingDirectory;

    public function __construct(
        string $command = 'bash',
        string $workingDirectory = '/mnt/magento',
        bool $debug = false,
        string $phpVersion = null
    ) {
        $this->command = $command;
        $this->phpVersion = $phpVersion;
        $this->debug = $debug;
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    /**
     * @return string
     */
    public function getPhpVersion(): string
    {
        return $this->phpVersion;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }
}
