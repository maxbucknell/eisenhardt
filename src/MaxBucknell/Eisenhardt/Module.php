<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt;

/**
 * Object Representing a Magento module.
 *
 * Encapsulates knowledge about its location.
 */
class Module
{
    /**
     * @var string
     */
    private $moduleDirectory;

    public function __construct(
        string $moduleDirectory
    ) {
        $this->moduleDirectory = $moduleDirectory;
    }

    /**
     * Return the root module directory.
     *
     * @return string
     */
    public function getModuleDirectory()
    {
        return $this->moduleDirectory;
    }
}
