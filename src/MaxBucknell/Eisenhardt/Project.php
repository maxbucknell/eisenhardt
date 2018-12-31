<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Object representing a Magento installation.
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string $installationDirectory Base directory of Eisenhardt installation.
     * @param LoggerInterface $logger
     */
    public function __construct(
        string $installationDirectory,
        LoggerInterface $logger
    ) {
        $this->installationDirectory = $installationDirectory;
        $this->filesystem = new Filesystem();
        $this->logger = $logger;

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

    /**
     * Copy a given file into the project's contrib directory.
     *
     * @param string $file
     */
    public function installContribFile(string $file)
    {
        $destination = "{$this->getEisenhardtDirectory()}/contrib/";

        $copyCommand = "cp {$file} {$destination}";

        \mkdir($destination);
        \shell_exec($copyCommand);
    }

    /**
     * Start the project containers.
     *
     * @param StartParams $params
     * @throws ProcessFailedException
     */
    public function start(
        StartParams $params
    ) {
        $this->logger->debug("Starting {$this->getProjectName()} Project");

        $eisenhardtDirectory = $this->getRelativeDirectory($this->getEisenhardtDirectory());

        $this->logger->debug("Project in {$this->getInstallationDirectory()}");
        $this->logger->debug("Eisenhardt files {$eisenhardtDirectory}");

        $command = [
            'docker-compose',
            "-f{$eisenhardtDirectory}base.yml",
            "-f{$eisenhardtDirectory}dev.yml"
        ];

        if ($params->isMapPorts()) {
            $this->logger->debug("Including ports mapping");
            $command[] = "-f{$eisenhardtDirectory}/ports.yml";
        }

        if ($params->isIncludeContrib()) {
            $this->logger->debug("Including contrib files");
            foreach ($this->getContribFiles($params) as $file) {
                $command[] = "-f{$eisenhardtDirectory}/contrib/{$file}";
            }
        }

        $command[] = "-p {$this->getProjectName()}";
        $command[] = 'up';
        $command[] = '-d';
        $command[] = '--force-recreate';

        $implodedCommand = \implode(" \\\n    ", $command);
        $this->logger->info("Running command:\n{$implodedCommand}");

        $printedCommand = \print_r($command, true);
        $this->logger->debug("Actual command:\n[$printedCommand}");

        $process = new Process(
            $command,
            $this->getInstallationDirectory()
        );

        $process->mustRun();

        $this->logger->info("Command stdout:\n{$process->getOutput()}\n");
        $this->logger->debug("Command stderr:\n{$process->getErrorOutput()}\n");
    }

    /**
     * @param StartParams $params
     * @return iterable
     */
    private function getContribFiles(StartParams $params): iterable
    {
        if (!$params->isIncludeContrib()) {
            return [];
        }

        $path = "{$this->getEisenhardtDirectory()}/contrib";
        $files = \glob("{$path}/*.yml");

        foreach ($files as $absoluteFile) {
            yield \basename($absoluteFile);
        }
    }

    /**
     * Stop all project containers.
     *
     * We used to use docker-compose stop here, but it's tough to keep track
     * of all containers, given that a contrib file can be included or excluded
     * at start time, and we don't know what is and isn't involved.
     */
    public function stop()
    {
        foreach ($this->getInfo() as $container) {
            $this->logger->debug("Stopping container {$container['name']}");

            if (!$container['is_running']) {
                $this->logger->info("Container {$container['name']} is not running");
                continue;
            }

            $command = [
                'docker',
                'stop',
                $container['name']
            ];

            $implodedCommand = \implode(" \\\n    ", $command);
            $this->logger->info("Running command:\n{$implodedCommand}");

            $printedCommand = \print_r($command, true);
            $this->logger->debug("Actual command:\n[$printedCommand}");

            $process = new Process($command);

            $process->run();

            $this->logger->info("Command stdout:\n{$process->getOutput()}\n");
            $this->logger->debug("Command stderr:\n{$process->getErrorOutput()}\n");

            if (!$process->isSuccessful()) {
                $this->logger->warning("Failed to stop container {$container['name']}");
            }
        }
    }

    public function getInfo()
    {
        $command = [
            'docker',
            'ps',
            "--filter=label=com.docker.compose.project={$this->getProjectName()}",
            "--format={{.Names}}|{{.Status}}"
        ];

        $implodedCommand = \implode(" \\\n    ", $command);
        $this->logger->info("Running command:\n{$implodedCommand}");

        $printedCommand = \print_r($command, true);
        $this->logger->debug("Actual command:\n[$printedCommand}");

        $process = new Process($command);

        $process->mustRun();

        $result = $process->getOutput();

        $this->logger->info("Command stdout:\n{$result}\n");
        $this->logger->debug("Command stderr:\n{$process->getErrorOutput()}\n");

        $rows = \explode("\n", \trim($result));
        $rows = \array_map(
            function ($row) {
                [$name, $status] = \explode('|', $row);
                $isUp = \strpos($status, 'Up') === 0;
                $ipAddress = $isUp ? $this->getContainerIpAddress($name) : null;

                return [
                    'is_running' => $isUp,
                    'name' => $name,
                    'ip_address' => $ipAddress
                ];
            },
            $rows
        );

        return $rows;
    }

    private function getContainerIpAddress($containerName)
    {
        $command = [
            'docker',
            'inspect',
            "-f{{ .NetworkSettings.Networks.{$this->getNetworkName()}.IPAddress }}",
            $containerName
        ];

        $implodedCommand = \implode(" \\\n    ", $command);
        $this->logger->info("Running command:\n{$implodedCommand}");

        $printedCommand = \print_r($command, true);
        $this->logger->debug("Actual command:\n[$printedCommand}");

        $process = new Process($command);
        $process->mustRun();

        $output = $process->getOutput();

        $this->logger->info("Command stdout:\n{$output}\n");
        $this->logger->debug("Command stderr:\n{$process->getErrorOutput()}\n");

        return \trim($output);
    }

    /**
     * Create an ephemeral container and inject it into the project.
     *
     * @param RunParams $params
     */
    public function run(RunParams $params)
    {
        $uid = \getmyuid();
        $home = \getenv('HOME');
        $composerHome = \getenv('COMPOSER_HOME') ?? "{$home}/.config/composer";
        $ipAddress = $this->getLocalIpAddress();
        $sshSocket = \getenv('SSH_AUTH_SOCK');

        $xdebugString = \implode(
            ' ',
            [
                "remote_host={$ipAddress}",
                'remote_connect_back=0',
                'xdebug.remote_mode=req',
                'xdebug.remote_port=9000'
            ]
        );

        $userString = $params->isAsRoot() ? 'root:root' : "{$uid}:10118";

        $command = [
            'docker',
            'run',
        ];

        if ($params->isInteractive()) {
            $command[] = '-it';
        }

        \array_push($command, ...[
            '--rm',
            "--volumes-from={$this->getContainerId('appserver')}",
            "--net={$this->getNetworkName()}",
            "-u{$userString}",
            "-v/etc/passwd:/etc/passwd",
            "-v{$home}/.ssh/known_hosts:{$home}/.ssh/known_hosts",
            "-v{$composerHome}:{$home}/.composer",
            "-eCOMPOSER_HOME={$home}/.composer",
            "-v{$home}/.npm:{$home}/.npm",
            "-v{$home}/.gitconfig:{$home}/.gitconfig",
            "-v{$sshSocket}:{$sshSocket}",
            "-ePHP_IDE_CONFIG=serverName='eisenhardt'",
            "-eXDEBUG_CONFIG='{$xdebugString}'",
            "-w{$params->getWorkingDirectory()}",
            "maxbucknell/php:{$this->getRunTag($params)}"
        ])  ;

        \array_push($command, ...$params->getCommand());

        $implodedCommand = \implode(" \\\n    ", $command);
        $this->logger->info("Running command:\n{$implodedCommand}");

        $printedCommand = \print_r($command, true);
        $this->logger->debug("Actual command:\n[$printedCommand}");

        $process = new Process($command);

        if ($params->isInteractive()) {
            $process->setTty(true);
        }

        $process->run();

        if (!$params->isInteractive()) {
            $this->logger->debug("Command stdout:\n{$process->getOutput()}\n");
            $this->logger->debug("Command stderr:\n{$process->getErrorOutput()}\n");
        }
    }

    /**
     * Get IP address of host machine on local network.
     *
     * @return string
     */
    private function getLocalIpAddress(): string
    {
        $command = [
            'hostname',
            '-I'
        ];

        $implodedCommand = \implode(" \\\n    ", $command);
        $this->logger->info("Running command:\n{$implodedCommand}");

        $printedCommand = \print_r($command, true);
        $this->logger->debug("Actual command:\n[$printedCommand}");

        $process = new Process($command);
        $process->run();
        $result = $process->getOutput();

        $this->logger->info("Command stdout:\n{$result}\n");
        $this->logger->debug("Command stderr:\n{$process->getErrorOutput()}\n");

        $ipAddresses = \explode(" ", $result);
        $ipAddress = $ipAddresses[0];

        $this->logger->info("Selected IP address: {$ipAddress}");

        return $ipAddress;
    }

    /**
     * Get Console Container Tag.
     *
     * Depending on the circumstances, a different container version may
     * be used.
     *
     * @param RunParams $params
     * @return string
     */
    private function getRunTag(
        RunParams $params
    ) {
        if (\is_null($params->getPhpVersion())) {
            $version = $this->getPhpVersion();
            $versionString = "{$version['release']}.{$version['major']}";
        } else {
            $versionString = $params->getPhpVersion();
        }

        if ($params->isDebug()) {
            return "{$versionString}-console-xdebug";
        }

        return "{$versionString}-console";
    }

    /**
     * Get the current PHP version the project is running as.
     *
     * @return array
     */
    public function getPhpVersion()
    {
        $command = [
            'docker',
            'exec',
            $this->getContainerId('appserver'),
            'php',
            '-r',
            'echo PHP_VERSION;'
        ];

        $implodedCommand = \implode(" \\\n    ", $command);
        $this->logger->info("Running command:
{$implodedCommand}");

        $printedCommand = \print_r($command, true);
        $this->logger->debug("Actual command:
[$printedCommand}");

        $process = new Process($command);

        $process->mustRun();

        $result = $process->getOutput();

        $this->logger->info("Command stdout:\n{$result}\n");
        $this->logger->debug("Command stderr:\n{$process->getErrorOutput()}\n");

        $fullVersion = \trim($result);
        $components = \explode('.', $fullVersion);

        return [
            'release' => $components[0],
            'major' => $components[1],
            'minor' => $components[2]
        ];
    }

    private function getContainerId(string $service): string
    {
        $command = [
            'docker',
            'ps',
            "--filter=label=com.docker.compose.project={$this->getProjectName()}",
            "--filter=label=com.docker.compose.service={$service}",
            "--format={{.ID}}"
        ];

        $implodedCommand = \implode(" \\\n    ", $command);
        $this->logger->info("Running command:
{$implodedCommand}");

        $printedCommand = \print_r($command, true);
        $this->logger->debug("Actual command:
[$printedCommand}");

        $process = new Process($command);

        $process->mustRun();

        $result = $process->getOutput();

        $this->logger->info("Command stdout:\n{$result}\n");
        $this->logger->debug("Command stderr:\n{$process->getErrorOutput()}\n");

        return \trim($result);
    }

    public function repairPermissions()
    {
        $id = \getmyuid();
        $commands = [
            'file ownership' => [
                'find',
                '.',
                '-type',
                'd',
                '-path',
                './.eisenhardt',
                '-prune',
                '-o',
                '-exec',
                'chown',
                '-v',
                "{$id}:10118",
                '{}',
                ';'
            ],
            'file permissions' => [
                'find',
                '.',
                '-type',
                'd',
                '-path',
                './.eisenhardt',
                '-prune',
                '-o',
                '-type',
                'f',
                '-exec',
                'chmod',
                '-v',
                '744',
                '{}',
                ';'
            ],
            'directory permissions' => [
                'find',
                '.',
                '-type',
                'd',
                '-path',
                './.eisenhardt',
                '-prune',
                '-o',
                '-type',
                'd',
                '-exec',
                'chmod',
                '-v',
                '755',
                '{}',
                ';',
                '-exec',
                'chmod',
                '-v',
                'g+s',
                '{}',
                ';'
            ],
            'var permissions' => [
                'chmod',
                '-v',
                '-R',
                'g+w',
                'var'
            ],
            'pub permissions' => [
                'chmod',
                '-v',
                '-R',
                'g+w',
                'pub'
            ],
            'app/etc permissions' => [
                'chmod',
                '-v',
                '-R',
                'g+w',
                'app/etc'
            ],
            'generated permissions' => [
                'chmod',
                '-v',
                '-R',
                'g+w',
                'generated'
            ],
            'bin/magento permissions' => [
                'chmod',
                '-v',
                '+x',
                'bin/magento'
            ],
        ];

        foreach ($commands as $description => $command) {
            $this->logger->info("Fixing {$description}");
            $this->run(new RunParams(
                $command,
                '/mnt/magento',
                false,
                null,
                true,
                false
            ));
        }
    }
}
