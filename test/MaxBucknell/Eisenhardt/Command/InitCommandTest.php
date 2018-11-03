<?php declare(strict_types=1);

namespace MaxBucknell\Eisenhardt\Command;

use MaxBucknell\Eisenhardt\ScaffoldTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class InitCommandTest extends TestCase
{
    use ScaffoldTrait;

    private $directory;

    public function setUp()
    {
        $this->directory = \getcwd();
    }

    public function tearDown()
    {
        \chdir($this->directory);
    }

    public function test_fails_without_base_host()
    {
        $dir = $this->getTempDir();
        \mkdir($dir, 0777, true);
        \chdir($dir);
        $command = new InitCommand();
        $commandTester = new CommandTester($command);

        $this->expectException(RuntimeException::class);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        echo $output;

    }

    public function test_create_project_tls_certificate()
    {
        $dir = $this->getTempDir();
        \mkdir($dir, 0777, true);
        \chdir($dir);
        $command = new InitCommand();
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'hostname' => 'testing.loc'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains(
            'Initializing Eisenhardt project in',
            $output,
            'Project not created properly'
        );
    }
}