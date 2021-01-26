<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt;

use MaxBucknell\Eisenhardt\Util\Finder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

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
     * @param LoggerInterface $logger
     * @return Project
     * @throws \Exception
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
     * @param LoggerInterface $logger
     * @return Project
     * @throws \Exception
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
     * @param LoggerInterface $logger
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

        $logger->debug("Initialising project in {$eisenhardtDirectory}");

        static::copyEisenhardtFiles($eisenhardtDirectory);

        $logger->debug("Copied project files into {$eisenhardtDirectory}");

        static::templateEisenhardtFiles(
            $eisenhardtDirectory,
            $phpVersion
        );

        $logger->debug("Configured project files to use PHP {$phpVersion}");

        $project = static::findFromDirectory($directory, $logger);

        static::initialiseTls(
            $project,
            $logger
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
     * @param Project $project
     * @param LoggerInterface $logger
     * @return string
     */
    private static function initialiseTls(
        Project $project,
        LoggerInterface $logger
    ) {
        $tlsDirectory = "{$project->getEisenhardtDirectory()}/tls";
        $hostname = "{$project->getProjectName()}.loc";

        $logger->debug("Setting up TLS certificates in {$tlsDirectory} for {$hostname}");

        $logger->debug("Creating directory {$tlsDirectory}");

        $fs = new Filesystem();
        $fs->mkdir($tlsDirectory);

        $command = [
            'mkcert',
            "{$hostname}",
            "*.{$hostname}"
        ];

        $implodedCommand = \implode(" \\\n    ", $command);
        $logger->info("Running command:\n{$implodedCommand}");

        $printedCommand = \print_r($command, true);
        $logger->debug("Actual command:\n[$printedCommand}");

        $process = new Process($command, $tlsDirectory);

        $process->mustRun();

        $logger->info("Command stdout:\n{$process->getOutput()}\n");
        $logger->debug("Command stderr:\n{$process->getErrorOutput()}\n");

        $logger->debug("Renaming certificate files");

        $fs->rename(
            "{$tlsDirectory}/{$hostname}+1.pem",
            "{$tlsDirectory}/crt.pem"
        );

        $fs->rename(
            "{$tlsDirectory}/{$hostname}+1-key.pem",
            "{$tlsDirectory}/key.pem"
        );
    }
}
