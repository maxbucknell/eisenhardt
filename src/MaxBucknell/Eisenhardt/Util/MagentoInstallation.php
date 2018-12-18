<?php

declare(strict_types=1);

namespace MaxBucknell\Eisenhardt\Util;

use MaxBucknell\Eisenhardt\Project;

/**
 * @author Max Bucknell <max@wearejh.com>
 */
class MagentoInstallation
{
    /**
     * Initialise a Magento 2 Composer project in the given directory.
     *
     * @param string $directory
     * @param string $versionNumber
     * @param string $edition
     * @return string
     *
     * @throws \Exception
     */
    public static function createProject(string $directory, string $versionNumber, string $edition): string
    {
        $originalDirectory = \getcwd();

        \mkdir($directory, 0744, true);
        \chdir($directory);

        \ob_start();
        $command = <<<CMD
composer create-project \
    --repository-url=https://repo.magento.com/ \
    magento/project-{$edition}-edition:{$versionNumber} \
    --no-install \
    . 2>&1
CMD;
        $returnVal = 0;
        \passthru($command, $returnVal);
        $output = \ob_get_clean();

        if ($returnVal !== 0) {
            throw new \Exception("Command {$command} in {$directory} failed with: {$output}");
        }

        \chdir($originalDirectory);

        return $output;
    }
}
