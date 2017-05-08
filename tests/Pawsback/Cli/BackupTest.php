<?php
namespace Pawsback\Test\Pawsback\Cli;

use PHPUnit\Framework\TestCase;

/**
 * Class: BackupTest
 *
 * @see TestCase
 */
class BackupTest extends TestCase
{

    /**
     * path
     *
     * @var string
     */
    public $path;

    /**
     * setup
     *
     * @return void
     */
    public function setup()
    {
        parent::setup();
        $this->path = realpath(__DIR__ . '/../../') . '/test_app/';
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * testConstructorWithoutPath
     *
     * @return void
     */
    public function testConstructor()
    {
        $path = null;
        $backup = $this->getMockBuilder('\Pawsback\Cli\Backup')
            ->disableOriginalConstructor()
            ->setMethods(['cliExists'])
            ->getMock();

        $backup->expects($this->once())
            ->method('cliExists')
            ->will($this->returnValue(true));

        $this->expectException('InvalidArgumentException');
        $backup->__construct($path);
    }

    /**
     * testRunVerboseWithAction
     *
     * @return void
     */
    public function testRunVerboseWithAction()
    {
        $backup = $this->getMockBuilder('\Pawsback\Cli\Backup')
            ->setConstructorArgs([
                $this->path . 'test.json',
                ['verbose' => true],
            ])
            ->setMethods(['shellExec', 'checkAndCreateBucket'])
            ->getMock();

        $backup->expects($this->at(0))
            ->method('shellExec')
            ->with($this->identicalTo(
                $backup->cliSyncCmd . ' ' .
                '/home/ubuntu/pawsback/tests/test_app/foo.com/shared/img' .
                ' s3://chronon-pawsback-test-1/' .
                'foo.com/shared/img' .
                ' --region us-east-1' .
                ' --profile default' .
                ' --delete'
            ))
            ->will($this->returnValue('canary 0'));
        $backup->expects($this->at(1))
            ->method('shellExec')
            ->with($this->identicalTo(
                $backup->cliSyncCmd . ' ' .
                '/home/ubuntu/pawsback/tests/test_app/foo.com/shared/files' .
                ' s3://chronon-pawsback-test-1/' .
                'foo.com/shared/files' .
                ' --region us-east-1' .
                ' --profile default' .
                ' --delete'
            ))
            ->will($this->returnValue('canary 1'));
        $backup->expects($this->at(2))
            ->method('shellExec')
            ->with($this->identicalTo(
                $backup->cliSyncCmd . ' ' .
                '/home/ubuntu/pawsback/tests/test_app/bar.com/shared/files' .
                ' s3://chronon-pawsback-test-1/' .
                'bar.com/shared/files' .
                ' --region us-east-1' .
                ' --profile default' .
                ' --delete'
            ))
            ->will($this->returnValue('canary 2'));
        $backup->expects($this->at(3))
            ->method('shellExec')
            ->with($this->identicalTo(
                $backup->cliSyncCmd . ' ' .
                '/home/ubuntu/pawsback/tests/test_app/bar.com/prefixed' .
                ' s3://chronon-pawsback-test-1/' .
                'bar.com/prefixed' .
                ' --region us-east-1' .
                ' --profile default' .
                ' --delete' .
                " --exclude '*' --include 'baz_*'"
            ))
            ->will($this->returnValue('canary 3'));

        $this->assertEmpty($backup->output);

        $backup->run();

        $this->assertNotEmpty($backup->output);
        $this->assertContains('chronon-pawsback-test-1', $backup->output);
        $this->assertContains('foo.com/shared/img', $backup->output);
        $this->assertNotContains('No files in need of sync.', $backup->output);
    }

    /**
     * testRunWithoutAction
     *
     * @return void
     */
    public function testRunWithoutAction()
    {
        $backup = $this->getMockBuilder('\Pawsback\Cli\Backup')
            ->setConstructorArgs([
                $this->path . 'test.json',
            ])
            ->setMethods(['shellExec', 'checkAndCreateBucket'])
            ->getMock();

        $this->assertEmpty($backup->output);

        $backup->run();

        $this->assertNotEmpty($backup->output);
        $this->assertNotContains('chronon-pawsback-test-1', $backup->output);
        $this->assertNotContains('foo.com/shared/img', $backup->output);
        $this->assertContains('No files in need of sync.', $backup->output);
    }

    /**
     * testRunGenerateMode
     *
     * @return void
     */
    public function testRunGenerateMode()
    {
        $backup = $this->getMockBuilder('\Pawsback\Cli\Backup')
            ->setConstructorArgs([
                $this->path . 'test.json',
                ['generate' => true],
            ])
            ->setMethods(['shellExec', 'checkAndCreateBucket'])
            ->getMock();

        $backup->expects($this->never())
            ->method('checkAndCreateBucket');

        $this->assertEmpty($backup->output);

        $backup->run();

        $this->assertNotEmpty($backup->output);
        $this->assertContains('chronon-pawsback-test-1', $backup->output);
        $this->assertContains('foo.com/shared/img', $backup->output);
        $this->assertNotContains('No files in need of sync.', $backup->output);
    }

    /**
     * testCliNotExists
     *
     * @return void
     */
    public function testCliNotExists()
    {
        $backup = $this->getMockBuilder('\Pawsback\Cli\Backup')
            ->disableOriginalConstructor()
            ->setMethods(['checkForCli'])
            ->getMock();

        $backup->expects($this->once())
            ->method('checkForCli')
            ->will($this->returnValue(1));

        $this->expectException('RuntimeException');
        $backup->__construct(null);
    }
}
