<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use MaxBucknell\Eisenhardt\Project;
use MaxBucknell\Eisenhardt\ProjectFactory;

/**
 * Eisenhardt fix-permissions
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

        $projectDir = $this->project->getInstallationDirectory();
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
  find . -not -path './.eisenhardt/*' -exec chown "$(id -u):10118" {} \;
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
  find . -type f -not -path './.eisenhardt/*' -exec chmod 744 {} \;
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
  find . -type d -not -path './.eisenhardt/*' -exec chmod 755 {} \;
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
  find . -type d -not -path './.eisenhardt/*' -exec chmod g+s {} \;
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
  chmod -R g+w var
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
  chmod -R g+w pub
CMD
            ,
            '<info>Adding group write permissions to <fg=yellow>app/etc/</></>' => <<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  chmod -R g+w app/etc
CMD
            ,
            '<info>Adding group write permissions to <fg=yellow>generated/</></>' => <<<CMD
docker run \
  -it \
  --rm \
  --net={$networkName} \
  -u "root:root" \
  --volumes-from="{$projectName}_magento_appserver_1" \
  -w /mnt/magento alpine \
  chmod -R g+w generated
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
