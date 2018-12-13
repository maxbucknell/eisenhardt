<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt;

use MaxBucknell\Eisenhardt\Util\Finder;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Create Projects based on current state.
 *
 * @see Project
 */
class ProjectFactory
{
    /**
     * Get a Project object based on the current working directory.
     *
     * By searching inside parents successively for a `.eisenhardt` directory,
     * we can locate a project root. If we do not find one, then we just
     * give up and throw an exception.
     *
     * @throws \Exception
     * @return Project
     */
    public static function findFromWorkingDirectory()
    {
        $workingDirectory = getcwd();

        try {
            $installationDirectory = Finder::findInParent(Project::DIRECTORY_NAME, $workingDirectory);
        } catch (FileNotFoundException $e) {
            throw new \Exception('Not an eisenhardt project. Please run eisenhardt init.');
        }

        return new Project($installationDirectory);
    }
}
