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
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Redbox\RD\Project;
use Redbox\RD\ProjectFactory;

/**
 * Redbox Docker fix-permissions
 *
 * Fix the local permissions of a Magento 2 installation.
 */
class FixPermissionsCommand extends Command
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->project = ProjectFactory::findFromWorkingDirectory();

        $this
            ->setName('fix-permissions')
            ->setDescription('Fix permissions of your Magento 2 installation')
            ->setHelp(<<<EOT
The <info>fix-permissions</> command repairs the permissions of the
Magento 2 installation, setting appropriate ownership and permissions
on all files and folders.
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
        $projectName = $this->project->getProjectName();
        $networkName = $this->project->getNetworkName();

        $output->writeln('Correcting owner...');
        shell_exec(<<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  chown -R "$(id -u):82" .
CMD
        );

        $output->writeln('Correcting permissions for normal files...');
        shell_exec(<<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  find . -type f -exec chmod 744 {} \;
CMD
        );

        $output->writeln('Correcting permissions for directories...');
        shell_exec(<<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  find . -type d -exec chmod 755 {} \;
CMD
        );

        $output->writeln('Adding sticky bit to group permissions for directories...');
        shell_exec(<<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  find . -type d -exec chmod g+s {} \;
CMD
        );

        $output->writeln('Adding group write permissions to <info>var/</>...');
        shell_exec(<<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  find var/ -exec chmod g+w {} \;
CMD
        );

        $output->writeln('Adding group write permissions to <info>pub/</>...');
        shell_exec(<<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  find pub/ -exec chmod g+w {} \;
CMD
        );

        $output->writeln('Making <info>bin/magento</> executable...');
        shell_exec(<<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  chmod +x bin/magento
CMD
        );

        $output->writeln('All finished!');
    }
}
