<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt;

/**
 * @author Max Bucknell <max@wearejh.com>
 */
class StandupParams
{
    /**
     * @var bool
     */
    private $installSampleData;
    /**
     * @var string
     */
    private $magentoVersion;
    /**
     * @var string
     */
    private $magentoEdition;
    /**
     * @var string
     */
    private $phpVersion;

    public function __construct(
        bool $installSampleData,
        string $magentoVersion,
        string $magentoEdition,
        string $phpVersion
    ) {
        $this->installSampleData = $installSampleData;
        $this->magentoVersion = $magentoVersion;
        $this->magentoEdition = $magentoEdition;
        $this->phpVersion = $phpVersion;
    }

    /**
     * @return bool
     */
    public function isInstallSampleData(): bool
    {
        return $this->installSampleData;
    }

    /**
     * @return string
     */
    public function getMagentoVersion(): string
    {
        return $this->magentoVersion;
    }

    /**
     * @return string
     */
    public function getMagentoEdition(): string
    {
        return $this->magentoEdition;
    }

    /**
     * @return string
     */
    public function getPhpVersion(): string
    {
        return $this->phpVersion;
    }
}
