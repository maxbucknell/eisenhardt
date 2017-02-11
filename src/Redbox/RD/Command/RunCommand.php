<?php
/**
 * This file is part of the redbox/rd package.
 *
 * @copyright Copyright 2017 Redbox Digital. All rights reserved.
 */

namespace Redbox\RD\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Redbox Docker run command.
 *
 * Run an administrative command inside a Redbox Docker environment.
 */
class RunCommand extends Command
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->fs = new Filesystem();

        $this
            ->setName('run')
            ->setDescription('Run a command inside a Redbox Docker environment')
            ->addArgument(
                'container_command',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Command to run in container',
                [ 'bash' ]
            )
            ->setHelp(<<<EOT
The <info>run</> command creates an ephemeral container based on the
redbox-digital/docker-console image, mounts the volumes and joins the
networks in the current Redbox Docker project, and executes the given
command.
EOT
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $command = implode(' ', $input->getArgument('container_command'));

        $workingDirectory = $this->getWorkingDirectory();

        passthru(<<<CMD
docker run \
    -it \
    --rm \
    --volumes-from="rd_magento_appserver_1" \
    --volumes-from="rd_frontend_appdata_1" \
    -u "\$(id -u):82" \
    -v "/etc/passwd:/etc/passwd" \
    -v "\$HOME/.ssh/known_hosts:\$HOME/.ssh/known_hosts" \
    -v "\$HOME/.composer:\$HOME/.composer" \
    -v "\$HOME/.npm:\$HOME/.npm" \
    -e COMPOSER_HOME="\$HOME/.composer" \
    -v "\$SSH_AUTH_SOCK:\$SSH_AUTH_SOCK" \
    -e SSH_AUTH_SOCK="\$SSH_AUTH_SOCK" \
    -w "{$workingDirectory}" \
    redboxdigital/docker-console:7.0 \
    {$command}
CMD
        );
    }

    private function getWorkingDirectory()
    {
        $root = $this->getRedboxDockerInstallationDirectory();
        $cwd = getcwd();

        $relativePath = $this->fs->makePathRelative(
            $cwd,
            $root
        );

        return "/mnt/www/{$relativePath}";
    }

    /**
     * Find root directory of project.
     */
    private function getRedboxDockerInstallationDirectory()
    {
        return $this->findInParent('.rd');
    }

    /**
     * Find a given filename in parent directories.
     *
     * @param string $filename Name of file to find.
     * @param string $dir starting directory.
     */
    private function findInParent($filename, $dir = null)
    {
        if (is_null($dir)) {
            $dir = getcwd();
        }

        if ($this->fs->exists("{$dir}/{$filename}")) {
            return $dir;
        } elseif ($dir === '/') {
            throw new FileNotFoundException(
                "File {$filename} not found in any parent directory"
            );
        } else {
            return $this->findInParent($filename, dirname($dir));
        }
    }
}
