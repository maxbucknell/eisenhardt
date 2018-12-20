<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

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
     * @param string $installationDirectory Base directory of Eisenhardt installation.
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
     */
    public function start(
        StartParams $params
    ) {
        $cwd = \getcwd();
        \chdir($this->getInstallationDirectory());

        $portInclude = $this->getPortInclude($params);
        $contribInclude = $this->getContribInclude($params);

        $command = <<<CMD
docker-compose \
    -f .eisenhardt/base.yml \
    -f .eisenhardt/dev.yml \
    {$portInclude} \
    {$contribInclude} \
    -p {$this->getProjectName()} \
    up -d --force-recreate 2> /dev/null
CMD;

        \passthru($command);

        \chdir($cwd);
    }

    /**
     * @param StartParams $params
     * @return string
     */
    private function getPortInclude(StartParams $params): string
    {
        return $params->isMapPorts() ? '-f .eisenhardt/ports.yml' : '';
    }

    private function getContribInclude(StartParams $params): string
    {
        if (!$params->isIncludeContrib()) {
            return '';
        }

        $files = \glob("{$this->getEisenhardtDirectory()}/contrib/*.yml");
        $includes = \array_map(
            function ($file) {
                return "-f {$file}";
            },
            $files
        );

        return \implode(' ', $includes);
    }

    public function stop()
    {
        $cwd = \getcwd();
        \chdir($this->getInstallationDirectory());

        $command = <<<CMD
docker-compose \
    -f .eisenhardt/base.yml \
    -f .eisenhardt/dev.yml \
    -p {$this->getProjectName()} \
    stop        
CMD;

        \passthru($command);

        \chdir($cwd);
    }

    public function getInfo()
    {
        $cwd = \getcwd();
        \chdir($this->getInstallationDirectory());


        $command = <<<CMD
docker ps \
    --filter "label=com.docker.compose.project={$this->getProjectName()}" \
    --format "{{.Names}}|{{.Status}}"
CMD;

        $commandResult = \trim(\shell_exec($command));

        $rows = \explode("\n", $commandResult);
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

        \chdir($cwd);

        return $rows;
    }

    private function getContainerIpAddress($containerName)
    {
        $command = <<<CMD
docker inspect -f \
    "{{ .NetworkSettings.Networks.{$this->getNetworkName()}.IPAddress }}" \
    {$containerName}
CMD;

        $result = \trim(\shell_exec($command));

        return $result;
    }

    /**
     * Create an ephemeral container and inject it into the project.
     *
     * @param RunParams $params
     */
    public function run(RunParams $params)
    {
        $cwd = \getcwd();
        \chdir($this->getInstallationDirectory());

        $tag = $this->getRunTag($params);
        $width = \trim(\shell_exec('tput cols'));
        $ipAddress = trim(\shell_exec('hostname -I | cut -d" " -f1'));
        $xdebugString = \implode(
            ' ',
            [
                "remote_host={$ipAddress}",
                'remote_connect_back=0',
                'xdebug.remote_mode=req',
                'xdebug.remote_port=9000'
            ]
        );

        $runCommand = <<<CMD
docker run \
    -it \
    --rm \
    --volumes-from="{$this->getProjectName()}_appserver_1" \
    --net="{$this->getNetworkName()}" \
    -u "\$(id -u):10118" \
    -v "/etc/passwd:/etc/passwd" \
    -v "\$HOME/.ssh/known_hosts:\$HOME/.ssh/known_hosts" \
    -v "\$COMPOSER_HOME:\$HOME/.composer" \
    -v "\$HOME/.npm:\$HOME/.npm" \
    -v "\$HOME/.gitconfig:\$HOME/.gitconfig" \
    -e COMPOSER_HOME="\$HOME/.composer" \
    -e XDEBUG_CONFIG="{$xdebugString}" \
    -e PHP_IDE_CONFIG="serverName='eisenhardt'" \
    -v "\$SSH_AUTH_SOCK:\$SSH_AUTH_SOCK" \
    -e SSH_AUTH_SOCK="\$SSH_AUTH_SOCK" \
    -e COLUMNS={$width} \
    -w "{$params->getWorkingDirectory()}" \
    maxbucknell/php:{$tag} \
    {$params->getCommand()}
CMD;

        \passthru($runCommand);

        \chdir($cwd);
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
            $versionString = "{$version['major']}.{$version['minor']}";
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
        $versionCommand = <<<CMD
docker-compose \
    -f .eisenhardt/base.yml \
    -p {$this->getProjectName()} \
    exec \
    appserver \
    php --version | \
    head -1 | \
    cut -d" " -f 2
CMD;

        $fullVersion = \shell_exec($versionCommand);
        $components = \explode('.', $fullVersion);

        return [
            'major' => $components[0],
            'minor' => $components[1],
            'patch' => $components[2]
        ];
    }
}
