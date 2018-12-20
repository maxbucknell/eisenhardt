<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt;

use MaxBucknell\Eisenhardt\Util\Finder;
use Psr\Log\LoggerInterface;
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
     * @throws \Exception
     * @return Project
     */
    public static function findFromWorkingDirectory(LoggerInterface $logger): Project
    {
        return static::findFromDirectory(\getcwd(), $logger);
    }

    /**
     * Get a Project object based on a given directory.
     *
     * By searching inside parents successively for a `.eisenhardt` directory,
     * we can locate a project root. If we do not find one, then we just
     * give up and throw an exception.
     *
     * @param string $directory Directory in which to start looking for a project.
     * @throws \Exception
     * @return Project
     */
    public static function findFromDirectory(string $directory, LoggerInterface $logger): Project
    {
        try {
            $installationDirectory = Finder::findInParent(Project::DIRECTORY_NAME, $directory);
        } catch (FileNotFoundException $e) {
            throw new \Exception('Not an eisenhardt project. Please run eisenhardt init.');
        }

        return new Project($installationDirectory, $logger);
    }

    /**
     * Initialise an Eisenhardt project in the given directory.
     *
     * @param string $directory
     * @param string $phpVersion
     * @param string|null $hostname
     * @return Project
     * @throws \Exception
     */
    public static function createInDirectory(
        string $directory,
        string $phpVersion,
        LoggerInterface $logger,
        string $hostname = null
    ): Project {
        $eisenhardtDirectory = "{$directory}/.eisenhardt";

        static::copyEisenhardtFiles($eisenhardtDirectory);
        static::templateEisenhardtFiles(
            $eisenhardtDirectory,
            $phpVersion
        );

        $project = static::findFromDirectory($directory, $logger);

        static::initialiseTls(
            $eisenhardtDirectory,
            $hostname ?? $project->getProjectName() . '.loc'
        );

        return $project;
    }

    /**
     * Copy template Eisenhardt files to project directory.
     *
     * @param string $destination
     */
    private static function copyEisenhardtFiles(string $destination) {
        $src = __DIR__ . '/../../../project-template';
        $command = "cp -r {$src} {$destination}";

        \shell_exec($command);
    }

    /**
     * Replace the template with the correct PHP version.
     *
     * @param string $directory
     * @param string $phpVersion
     */
    private static function templateEisenhardtFiles(
        string $directory,
        string $phpVersion
    ) {
        $templateCommand = "find {$directory} -type f -exec sed -i 's/{{version}}/{$phpVersion}/' {} \;";
        \shell_exec($templateCommand);
    }

    /**
     * Initialise TLS certificates for a project.
     *
     * @param string $hostname
     * @param string $eisenhardtDirectory
     * @return string
     */
    private static function initialiseTls(
        string $eisenhardtDirectory,
        string $hostname
    ): string {
        $cwd = \getcwd();
        \chdir($eisenhardtDirectory);

        \mkdir('tls');
        \chdir('tls');

        $mkcertCommand = "mkcert {$hostname} *.{$hostname} 2>&1";
        $mkcertOutput = \shell_exec($mkcertCommand);

        var_dump($mkcertOutput);

        shell_exec("mv {$hostname}+1.pem crt.pem");
        shell_exec("mv {$hostname}+1-key.pem key.pem");

        \chdir($cwd);

        return $mkcertOutput;
    }
}
