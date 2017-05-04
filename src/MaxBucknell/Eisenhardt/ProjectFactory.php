<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Factory for Eisenhardt projects.
 */
class ProjectFactory
{
    /**
     * Get a Project object based on the current worknig directory.
     *
     * By searching inside parents successively for a `.eisenhardt` directory,
     * we can locate a project root. If we do not find one, then we just
     * give up and throw an exception.
     *
     * @throws FileNotFoundException
     * @return Project
     */
    public static function findFromWorkingDirectory()
    {
        $workingDirectory = getcwd();
        $installationDirectory = static::findInParent(Project::DIRECTORY_NAME, $workingDirectory);

        return new Project($installationDirectory);
    }

    /**
     * Find a given filename in parent directories.
     *
     * @param string $filename Name of file to find.
     * @param string $dir starting directory.
     * @throws FileNotFoundException
     * @return string Directory containing $filename
     */
    private static function findInParent($filename, $dir)
    {
        if (file_exists("{$dir}/{$filename}")) {
            return $dir;
        }

        if ($dir === '/') {
            throw new FileNotFoundException(
                "File {$filename} not found in any parent directory"
            );
        }

        return static::findInParent($filename, dirname($dir));
    }
}
