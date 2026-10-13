<?php

declare(strict_types=1);

namespace Koh\AiWorkflows\Tests\Unit\Infrastructure\Discovery;

use Koh\AiWorkflows\Domain\Contract\WorkflowParserInterface;
use Koh\AiWorkflows\Domain\Enum\TriggerType;
use Koh\AiWorkflows\Domain\Model\WorkflowDefinition;
use Koh\AiWorkflows\Domain\Model\WorkflowTrigger;
use Koh\AiWorkflows\Infrastructure\Discovery\WorkflowDiscovery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Discovery is what turns a folder of YAML files into the list of workflows an
 * operator can run. A directory that cannot be read has to come back empty
 * rather than fatal — it is the normal state of an installation that has not
 * defined any workflows yet, and the backend module calls this on every view.
 *
 * The tests run against a real temporary directory: the resolution goes through
 * GeneralUtility::getFileAbsFileName(), and stubbing that out would leave the
 * one part worth checking untested.
 */
final class WorkflowDiscoveryTest extends TestCase
{
    private string $projectPath = '';
    private string $workflowPath = '';

    /** @var list<string> */
    private array $parsedFiles = [];

    private WorkflowDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectPath = \sys_get_temp_dir() . '/aiworkflows-discovery-' . \getmypid();
        $this->workflowPath = $this->projectPath . '/Workflows';
        \mkdir($this->workflowPath, 0o777, true);

        // getFileAbsFileName() only resolves paths inside the project root, so
        // the temporary directory has to be that root.
        Environment::initialize(
            new ApplicationContext('Testing'),
            true,
            true,
            $this->projectPath,
            $this->projectPath . '/public',
            $this->projectPath . '/var',
            $this->projectPath . '/config',
            $this->projectPath . '/index.php',
            'UNIX',
        );

        $this->parsedFiles = [];

        $parser = $this->createMock(WorkflowParserInterface::class);
        $parser->method('parseFile')->willReturnCallback(
            function (string $file): WorkflowDefinition {
                $this->parsedFiles[] = $file;

                return new WorkflowDefinition(
                    \basename($file, '.yaml'),
                    '',
                    new WorkflowTrigger(TriggerType::Manual),
                    [],
                    [],
                );
            },
        );

        $this->discovery = new WorkflowDiscovery($parser);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->workflowPath . '/*') ?: [] as $file) {
            \unlink($file);
        }
        @\rmdir($this->workflowPath);
        @\rmdir($this->projectPath);

        parent::tearDown();
    }

    private function writeWorkflow(string $filename): void
    {
        \file_put_contents($this->workflowPath . '/' . $filename, "name: test\n");
    }

    #[Test]
    public function everyYamlFileBecomesAWorkflowKeyedByItsFilename(): void
    {
        $this->writeWorkflow('content-pipeline.yaml');
        $this->writeWorkflow('nightly-report.yaml');

        $workflows = $this->discovery->discoverWorkflows($this->workflowPath);

        self::assertSame(['content-pipeline', 'nightly-report'], \array_keys($workflows));
        self::assertCount(2, $this->parsedFiles);
    }

    /**
     * The folder also holds READMEs and editor leftovers. Handing those to the
     * YAML parser would fail the whole listing over a file that is not a
     * workflow.
     */
    #[Test]
    public function filesThatAreNotYamlAreIgnored(): void
    {
        $this->writeWorkflow('real.yaml');
        $this->writeWorkflow('README.md');
        $this->writeWorkflow('draft.yaml.bak');

        $workflows = $this->discovery->discoverWorkflows($this->workflowPath);

        self::assertSame(['real'], \array_keys($workflows));
    }

    /**
     * An installation without any workflows is the normal starting state, and
     * the backend module calls this on every view.
     */
    #[Test]
    public function anEmptyDirectoryYieldsNoWorkflows(): void
    {
        self::assertSame([], $this->discovery->discoverWorkflows($this->workflowPath));
    }

    #[Test]
    public function aDirectoryThatDoesNotExistYieldsNoWorkflows(): void
    {
        self::assertSame([], $this->discovery->discoverWorkflows($this->projectPath . '/does-not-exist'));
    }

    /**
     * A path outside the project root does not resolve at all. That must read as
     * "nothing here", not as a crash — and it must certainly not list files.
     */
    #[Test]
    public function aPathOutsideTheProjectYieldsNoWorkflows(): void
    {
        self::assertSame([], $this->discovery->discoverWorkflows('/etc'));
    }

    #[Test]
    public function aSingleWorkflowIsFoundByName(): void
    {
        $this->writeWorkflow('content-pipeline.yaml');

        $workflow = $this->discovery->getWorkflow($this->workflowPath, 'content-pipeline');

        self::assertNotNull($workflow);
        self::assertSame('content-pipeline', $workflow->name);
        self::assertSame([$this->workflowPath . '/content-pipeline.yaml'], $this->parsedFiles);
    }

    /**
     * The run command turns this null into "workflow not found" and exits
     * non-zero, so it has to mean exactly that.
     */
    #[Test]
    public function anUnknownNameYieldsNull(): void
    {
        self::assertNull($this->discovery->getWorkflow($this->workflowPath, 'no-such-workflow'));
        self::assertSame([], $this->parsedFiles, 'a missing file must not reach the parser');
    }

    /**
     * A trailing slash on the configured directory is easy to leave in and must
     * not produce a doubled separator that misses the file.
     */
    #[Test]
    public function aTrailingSlashOnTheDirectoryIsTolerated(): void
    {
        $this->writeWorkflow('content-pipeline.yaml');

        self::assertNotNull($this->discovery->getWorkflow($this->workflowPath . '/', 'content-pipeline'));
        self::assertCount(1, $this->discovery->discoverWorkflows($this->workflowPath . '/'));
    }
}
