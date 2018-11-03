<?php declare(strict_types=1);

namespace MaxBucknell\Eisenhardt;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class ProjectTest extends TestCase
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

    public function test_noninitialised_dir_throws()
    {

        $dir = $this->getTempDir();
        \mkdir($dir, 0777, true);

        $this->expectException(FileNotFoundException::class);

        $project = new Project($dir);
    }

    public function test_initialised_dir_succeeds()
    {
        $dir = $this->getInitialisedProject();
        $project = new Project($dir);

        $this->assertTrue(true, 'Project failed to initialise');
    }
    
    public function test_installation_directory()
    {
        $dir = $this->getInitialisedProject();
        $project = new Project($dir);

        $this->assertEquals(
            $dir,
            $project->getInstallationDirectory(),
            'Installation directory not set correctly.'
        );
    }

    public function test_eisenhardt_directory()
    {
        $dir = $this->getInitialisedProject();
        $project = new Project($dir);

        $this->assertEquals(
            $dir . '/.eisenhardt',
            $project->getEisenhardtDirectory(),
            'Eisenhardt directory not set properly.'
        );
    }

    public function test_relative_directory_finder()
    {
        $dir = $this->getInitialisedProject();
        $project = new Project($dir);

        $this->assertEquals(
            'a/b/c/',
            $project->getRelativeDirectory($dir . '/a/b/c'),
            'Relative directories not being found correctly'
        );
    }

    public function test_project_name()
    {
        $dir = $this->getTempDir();
        \mkdir($dir, 0777, true);
        \chdir($dir);

        \mkdir('project/.eisenhardt', 0777, true);
        $basicProject = new Project($dir . '/project');

        $this->assertEquals(
            'project',
            $basicProject->getProjectName(),
            'Basic project name not set correctly'
        );

        \mkdir('project_other/.eisenhardt', 0777, true);
        $otherProject = new Project($dir . '/project_other');

        $this->assertEquals(
            'projectother',
            $otherProject->getProjectName(),
            'Two part project name not set correctly'
        );

        \mkdir('project_other_254/.eisenhardt', 0777, true);
        $numericProject = new Project($dir . '/project_other_254');

        $this->assertEquals(
            'projectother254',
            $numericProject->getProjectName(),
            'Numeric project name not set correctly'
        );
    }

    public function test_network_name()
    {
        $dir = $this->getTempDir();
        \mkdir($dir, 0777, true);
        \chdir($dir);

        \mkdir('project/.eisenhardt', 0777, true);
        $basicProject = new Project($dir . '/project');

        $this->assertEquals(
            'project_magento',
            $basicProject->getNetworkName(),
            'Basic network name not set correctly'
        );

        \mkdir('project_other/.eisenhardt', 0777, true);
        $otherProject = new Project($dir . '/project_other');

        $this->assertEquals(
            'projectother_magento',
            $otherProject->getNetworkName(),
            'Two part network name not set correctly'
        );

        \mkdir('project_other_254/.eisenhardt', 0777, true);
        $numericProject = new Project($dir . '/project_other_254');

        $this->assertEquals(
            'projectother254_magento',
            $numericProject->getNetworkName(),
            'Numeric network name not set correctly'
        );
    }
}