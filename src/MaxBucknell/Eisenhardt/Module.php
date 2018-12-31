<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt;

use MaxBucknell\Eisenhardt\Util\MagentoInstallation;
use Psr\Log\LoggerInterface;

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

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        string $moduleDirectory,
        LoggerInterface $logger
    ) {
        $this->moduleDirectory = $moduleDirectory;
        $this->logger = $logger;
    }

    /**
     * Return the root module directory.
     *
     * @return string
     */
    public function getModuleDirectory(): string
    {
        return $this->moduleDirectory;
    }

    /**
     * Return the directory in which to place stood up Magento installations.
     *
     * @return string
     */
    public function getStandupDirectory(): string
    {
        $moduleDirectory = $this->getModuleDirectory();

        return "{$moduleDirectory}/" . static::STANDUP_DIRECTORY_NAME;
    }

    public function getModuleName(): string
    {
        return $this->getComposerManifest()['name'];
    }

    /**
     * @return array
     */
    public function getComposerManifest(): array
    {
        return \json_decode(
            \file_get_contents("{$this->getModuleDirectory()}/composer.json"),
            true
         );
    }

    public function standUpMagentoInstance(StandupParams $params): string
    {
        $standupName = $this->getStandupName($params);

        $directory = "{$this->getStandupDirectory()}/{$standupName}";

        MagentoInstallation::create(
            $directory,
            $params,
            $this->logger
        );

        return $standupName;
    }

    private function getStandupName(StandupParams $params)
    {
        $data = \hash('sha256', (string)mt_rand(), false);

        return 'eps' . \substr($data, 0, 7);
    }
}
