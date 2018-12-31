<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt;

class RunParams
{
    /**
     * @var array
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

    /**
     * @var bool
     */
    private $asRoot;

    /**
     * @var bool
     */
    private $interactive;

    public function __construct(
        array $command = ['bash'],
        string $workingDirectory = '/mnt/magento',
        bool $debug = false,
        string $phpVersion = null,
        bool $asRoot = false,
        bool $interactive = true
    ) {
        $this->command = $command;
        $this->phpVersion = $phpVersion;
        $this->debug = $debug;
        $this->workingDirectory = $workingDirectory;
        $this->asRoot = $asRoot;
        $this->interactive = $interactive;
    }

    /**
     * @return string
     */
    public function getCommand(): array
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
    public function getPhpVersion(): ?string
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

    /**
     * @return bool
     */
    public function isAsRoot(): bool
    {
        return $this->asRoot;
    }

    /**
     * @return bool
     */
    public function isInteractive(): bool
    {
        return $this->interactive;
    }
}
