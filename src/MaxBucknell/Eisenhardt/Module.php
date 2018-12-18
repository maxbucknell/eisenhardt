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
    const STANDUP_DIRECTORY_NAME = Project::DIRECTORY_NAME . '/standup';

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

    /**
     * Return the directory in which to place stood up Magento installations.
     *
     * @return string
     */
    public function getStandupDirectory()
    {
        $moduleDirectory = $this->getModuleDirectory();

        return "{$moduleDirectory}/" . static::STANDUP_DIRECTORY_NAME;
    }
}
