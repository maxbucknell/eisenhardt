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
        $this->project = ProjectFactory::findFromWorkingDirectory();

        $projectDir = $project->getInstallationDirectory;
        $output->writeln(
            "Found project in `{$projectDir}`.",
            OutputInterface::VERBOSITY_VERBOSE
        );

        $projectName = $this->project->getProjectName();
        $networkName = $this->project->getNetworkName();

        $commands = [
            '<info>Correcting owner</>' => <<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  find . -not -path './.rd/*' -exec chown "$(id -u):10118" {} \;
CMD
            ,
            '<info>Correcting permissions for normal files</>' => <<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  find . -type f -not -path './.rd/*' -exec chmod 744 {} \;
CMD
            ,
            '<info>Correcting permissions for directories</>' => <<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  find . -type d -not -path './.rd/*' -exec chmod 755 {} \;
CMD
            ,
            '<info>Adding sticky bit to group permissions for directories</>' => <<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  find . -type d -not -path './.rd/*' -exec chmod g+s {} \;
CMD
            ,
            '<info>Adding group write permissions to <fg=yellow>var/</></>' => <<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  find var/ -not -path './.rd/*' -exec chmod g+w {} \;
CMD
            ,
            '<info>Adding group write permissions to <fg=yellow>pub/</></>' => <<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  find pub/ -not -path './.rd/*' -exec chmod g+w {} \;
CMD
            ,
            '<info>Making <fg=yellow>bin/magento</> executable</>' => <<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  chmod +x bin/magento
CMD
            ,
        ];

        foreach ($commands as $message => $command) {
            $output->writeln($message);
            $output->writeln(
                "Running: {$command}",
                OutputInterface::VERBOSITY_VERBOSE
            );
            shell_exec($command);
        }

        $output->writeln('All finished!');
    }
}
