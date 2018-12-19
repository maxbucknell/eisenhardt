<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt;
use MaxBucknell\Eisenhardt\Util\Finder;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Create Modules based on current state.
 *
 * @see Module
 */
class ModuleFactory
{
    /**
     * Get a Module based on the current working directory.
     *
     * By searching inside parents successively for etc/module.xml, we can
     * locate a module. If we do not find one, we give up and throw an
     * exception.
     *
     * @throws \Exception
     * @return Module
     */
    public static function findFromWorkingDirectory()
    {
        $workingDirectory = getcwd();

        try {
            $moduleDirectory = Finder::findInParent('composer.json', $workingDirectory);
        } catch (FileNotFoundException $e) {
            throw new \Exception('This does not appear to be a Magento 2 module');
        }

        return new Module($moduleDirectory);
    }
}
