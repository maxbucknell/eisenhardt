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
     * @param bool $mapPorts
     * @param bool $includeContrib
     */
    public function __construct(
        bool $mapPorts,
        bool $includeContrib
    ) {
        $this->mapPorts = $mapPorts;
        $this->includeContrib = $includeContrib;
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
}
