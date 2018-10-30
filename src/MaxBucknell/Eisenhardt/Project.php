<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Object representing a Eisenhardt project.
 *
 * Encapsulates knowledge about its location.
 */
class Project
{
    const DIRECTORY_NAME = '.eisenhardt';

    /**
     * @var string
     */
    private $installationDirectory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param string $installationDirectory
     *     Base directory of Eisenhardt installation.
     */
    public function __construct(
        string $installationDirectory
    ) {
        $this->installationDirectory = $installationDirectory;
        $this->filesystem = new Filesystem();

        if (!file_exists($this->getEisenhardtDirectory())) {
            throw new FileNotFoundException(
                "Could not find `" . static::DIRECTORY_NAME . "/` directory inside {$installationDirectory}"
            );
        }
    }

    /**
     * Return the installation directory of Eisenhardt.
     *
     * @return string
     */
    public function getInstallationDirectory()
    {
        return $this->installationDirectory;
    }

    /**
     * Return the Eisenhardt project configuration directory.
     *
     * @return string
     */
    public function getEisenhardtDirectory()
    {
        $installationDirectory = $this->installationDirectory;

        return "{$installationDirectory}/" . static::DIRECTORY_NAME;
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
        return "{$this->getProjectName()}_eisenhardt";
    }
}
