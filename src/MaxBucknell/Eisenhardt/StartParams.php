<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt;

class StartParams
{
    /**
     * @var bool
     */
    private $mapPorts;

    /**
     * @var bool
     */
    private $includeContrib;

    /**
     * @var bool
     */
    private $includePlatform;

    /**
     * @var bool
     */
    private $includeDatabase;

    /**
     * @param bool $mapPorts
     * @param bool $includeContrib
     */
    public function __construct(
        bool $mapPorts = false,
        bool $includeContrib = true,
        bool $includePlatform = true,
        bool $includeDatabase = true
    ) {
        $this->mapPorts = $mapPorts;
        $this->includeContrib = $includeContrib;
        $this->includePlatform = $includePlatform;
        $this->includeDatabase = $includeDatabase;
    }

    /**
     * @return bool
     */
    public function isMapPorts(): bool
    {
        return $this->mapPorts;
    }

    /**
     * @return bool
     */
    public function isIncludeContrib(): bool
    {
        return $this->includeContrib;
    }

    /**
     * @return bool
     */
    public function isIncludePlatform(): bool
    {
        return $this->includePlatform;
    }

    /**
     * @return bool
     */
    public function isIncludeDatabase(): bool
    {
        return $this->includeDatabase;
    }
}
