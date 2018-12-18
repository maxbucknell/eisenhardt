<?php
/**
 * This file is part of the maxbucknell/eisenhardt package.
 */

namespace MaxBucknell\Eisenhardt\Command;

use MaxBucknell\Eisenhardt\Project;
use MaxBucknell\Eisenhardt\ProjectFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Eisenhardt initialization command.
 *
 * Initializes a new Eisenhardt environment in the current project
 * directory.
 */
class InitCommand extends Command
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
            ->setName('init')
            ->setDescription('Initialize a new Eisenhardt environment')
            ->setDefinition(
                new InputDefinition([
                    new InputOption(
                        'php-version',
                        'p',
                        InputOption::VALUE_OPTIONAL,
                        'PHP version to use with this project.',
                        '7.2'
                    ),
                    new InputArgument(
                        'hostname',
                        InputArgument::OPTIONAL,
                        'Host name this installation will run on. Required for TLS'
                    )
                ])
            )
            ->setHelp(<<<EOT
The <info>init</> command creates a directory inside your project root
called <info>.eisenhardt/</>, which contains various Docker related config.
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
        $this->createProject($input, $output);
        $this->initialiseTls($input, $output);

        $output->writeln("Initializing Eisenhardt project in <info>{$this->project->getEisenhardtDirectory()}</>");
    }

    private function createProject(
        InputInterface $input,
        OutputInterface $output
    ) {
        $cwd = getcwd();
        $location = __DIR__;
        $destination = "{$cwd}/.eisenhardt";
        $src = "{$location}/../../../../project-template";
        $version = $input->getOption('php-version');

        $copyCommand = "cp -r {$src} {$destination}";
        $output->writeln(
            "Running: `{$copyCommand}`.",
            OutputInterface::VERBOSITY_VERBOSE
        );
        shell_exec($copyCommand);

        $templateCommand = "find {$destination} -type f -exec sed -i 's/{{version}}/{$version}/' {} \;";
        $output->writeln(
            "Running `{$templateCommand}`",
            OutputInterface::VERBOSITY_VERBOSE
        );
        shell_exec($templateCommand);
    }

    private function initialiseTls(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->project = ProjectFactory::findFromWorkingDirectory();

        $hostname = $input->getArgument('hostname') ?? $this->project->getProjectName() . '.loc';
        $mkcertCommand = "mkcert {$hostname} *.{$hostname} 2>&1";

        chdir($this->project->getEisenhardtDirectory());
        mkdir('tls');
        chdir('tls');

        $output->writeln(
            "Running: {$mkcertCommand}",
            OutputInterface::VERBOSITY_VERBOSE
        );

        $mkcertOutput = shell_exec($mkcertCommand);

        $output->writeln(
            "mkcert output: {$mkcertOutput}",
            OutputInterface::VERBOSITY_VERBOSE
        );

        $output->writeln(
            "Moving certificates to generic location",
            OutputInterface::VERBOSITY_VERBOSE
        );

        shell_exec("mv {$hostname}+1.pem crt.pem");
        shell_exec("mv {$hostname}+1-key.pem key.pem");
    }
}
