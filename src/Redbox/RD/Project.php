<?php
/**
 * This file is part of the redbox/rd package.
 *
 * @copyright Copyright 2017 Redbox Digital. All rights reserved.
 */

namespace Redbox\RD;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Object representing a Redbox Docker project.
 *
 * Encapsulates knowledge about its location.
 */
class Project
{
    /**
     * @var string
     */
    private $installationDirectory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param string $rootDirectory
     *     Base directory of Redbox Docker installation.
     */
    public function __construct(
        string $installationDirectory
    ) {
        $this->installationDirectory = $installationDirectory;
        $this->filesystem = new Filesystem();

        if (!file_exists($this->getRDDirectory())) {
            throw new FileNotFoundException(
                "Could not find `.rd/` directory inside {$installationDirectory}"
            );
        }
    }

    /**
     * Return the installation directory of Redbox Docker.
     *
     * @return string
     */
    public function getInstallationDirectory()
    {
        return $this->installationDirectory;
    }

    /**
     * Return the Redbox Docker project configuration directory.
     *
     * @return string
     */
    public function getRDDirectory()
    {
        $installationDirectory = $this->installationDirectory;

        return "{$installationDirectory}/.rd";
    }

    /**
     * Return given directory relative to root of installation.
     *
     * @param string $dir
     * @return string
     */
    public function getRelativeDirectory($dir)
    {
        return $this->filesystem->makePathRelative(
            $dir,
            $this->getInstallationDirectory()
        );
    }

    /**
     * Return name of project.
     *
     * @return string
     */
    public function getProjectName()
    {
        $directoryName = basename($this->getInstallationDirectory());

        return strtolower(preg_replace('{[^a-zA-Z0-9]}', '', $directoryName));
    }

    /**
     * Return name of Magento network in project.
     *
     * @return string
     */
    public function getNetworkName()
    {
        return "{$this->getProjectName()}_magento";
    }
}
