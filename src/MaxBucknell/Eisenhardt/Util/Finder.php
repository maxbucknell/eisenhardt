<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt\Util;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class Finder
{
    /**
     * Find a given filename in parent directories.
     *
     * @param string $filename Name of file to find.
     * @param string $dir starting directory.
     * @throws FileNotFoundException
     * @return string Directory containing $filename
     */
    public static function findInParent($filename, $dir)
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
