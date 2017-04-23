<?php
namespace Pawsback\Test\Pawsback;

use PHPUnit\Framework\TestCase;
use Pawsback\Pawsback;

/**
 * Class: PawsbackTest
 *
 * @see TestCase
 */
class PawsBackTest extends TestCase
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
        $this->path = __DIR__.'/../test_app/';
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
    public function testConstructorWithoutPath()
    {
        $this->expectException('InvalidArgumentException');
        $pawsback = new Pawsback();
    }

    /**
     * testConstructorWithPath
     *
     * @return void
     */
    public function testConstructorWithPath()
    {
        $path = 'foo';
        $config = ['backups' => ['foo' => 'bar']];
        $provider = ['provider'];
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods([
                'validatePath',
                'getConfig',
                'getProvider',
                'prepareProvider',
                'getS3Client',
                'checkAndCreateBucket',
                'getAndVerifyBackupPaths',
            ])
            ->getMock();

        $S3Client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->getMock();

        $pawsback->expects($this->once())
            ->method('validatePath')
            ->with($this->identicalTo($path))
            ->will($this->returnValue(true));
        $pawsback->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));
        $pawsback->expects($this->once())
            ->method('getProvider')
            ->with($this->identicalTo($config), $this->identicalTo('S3'))
            ->will($this->returnValue($provider));
        $pawsback->expects($this->once())
            ->method('prepareProvider')
            ->with($this->identicalTo($provider))
            ->will($this->returnValue($provider));
        $pawsback->expects($this->once())
            ->method('getS3Client')
            ->with($this->identicalTo($provider))
            ->will($this->returnValue($S3Client));
        $pawsback->expects($this->once())
            ->method('checkAndCreateBucket')
            ->with($this->identicalTo($S3Client), $this->identicalTo($provider))
            ->will($this->returnValue('bucket'));
        $pawsback->expects($this->once())
            ->method('getAndVerifyBackupPaths')
            ->with($this->identicalTo($config['backups']))
            ->will($this->returnValue('bucket'));

        $pawsback->__construct($path);
    }

    /**
     * testValidatePathValid
     *
     * @return void
     */
    public function testValidatePathValid()
    {
        $path = 'canary';
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(['newSplFileInfo'])
            ->getMock();

        $fileInfo = $this->getMockBuilder('\SplFileInfo')
            ->disableOriginalConstructor()
            ->setMethods(['isReadable'])
            ->getMock();

        $pawsback->expects($this->once())
            ->method('newSplFileInfo')
            ->with($this->identicalTo($path))
            ->will($this->returnValue($fileInfo));

        $fileInfo->expects($this->once())
            ->method('isReadable')
            ->withAnyParameters()
            ->will($this->returnValue(true));

        $result = $pawsback->validatePath($path);

        $this->assertTrue($result);
    }

    /**
     * testValidatePathInvalid
     *
     * @return void
     */
    public function testValidatePathInvalid()
    {
        $path = 'canary';
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(['newSplFileInfo'])
            ->getMock();
        $fileInfo = $this->getMockBuilder('\SplFileInfo')
            ->disableOriginalConstructor()
            ->setMethods(['isReadable'])
            ->getMock();

        $pawsback->expects($this->once())
            ->method('newSplFileInfo')
            ->with($this->identicalTo($path))
            ->will($this->returnValue($fileInfo));
        $fileInfo->expects($this->once())
            ->method('isReadable')
            ->withAnyParameters()
            ->will($this->returnValue(false));

        $this->expectException('InvalidArgumentException');

        $result = $pawsback->validatePath($path);
    }

    /**
     * testGetConfig
     *
     * @return void
     */
    public function testGetConfig()
    {
        $file = './tests/test_app/test.json';
        $config = '{"foo": "bar"}';
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(['newSplFileObject'])
            ->getMock();
        $fileObject = $this->getMockBuilder('\SplFileObject')
            ->setConstructorArgs([$file, 'r'])
            ->setMethods(['fread', 'getSize'])
            ->getMock();

        $pawsback->expects($this->once())
            ->method('newSplFileObject')
            ->will($this->returnValue($fileObject));
        $fileObject->expects($this->once())
            ->method('getSize')
            ->will($this->returnValue('foo'));
        $fileObject->expects($this->once())
            ->method('fread')
            ->will($this->returnValue($config));

        $result = $pawsback->getConfig();

        $this->assertInternaltype('array', $result);
        $this->assertArrayHasKey('foo', $result);
    }

    /**
     * testGetProvider
     *
     * @return void
     * @dataProvider provideTestGetProvider
     */
    public function testGetProvider($config, $provider, $expected)
    {
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $pawsback->getProvider($config, $provider);
        $this->assertSame($expected, $result);
    }

    /**
     * provideTestGetProvider
     *
     * @return void
     */
    public function provideTestGetProvider()
    {
        return [
            [
                ['provider' => ['S3' => ['foo' => 'bar']]],
                'S3',
                ['foo' => 'bar'],
            ],
            [
                ['provider' => ['baz' => ['foo' => 'bar']]],
                'S3',
                null,
            ],
            [
                ['ding' => ['baz' => ['foo' => 'bar']]],
                'dong',
                null,
            ],
        ];
    }

    /**
     * testGetAndVerifyBackupPaths
     *
     * @return void
     */
    public function testGetAndVerifyBackupPathsInvalidPath()
    {
        $backups = ['sources' => [
            [
                'name' => 'foo',
                'root' => '/bar/baz/',
                'dirs' => [
                    'one' => '',
                    'two' => '',
                ],
            ],
        ]];
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(['verifyPath'])
            ->getMock();

        $pawsback->expects($this->once())
            ->method('verifyPath')
            ->will($this->returnValue(false));

        $this->expectException('DomainException');
        $pawsback->getAndVerifyBackupPaths($backups);
    }

    /**
     * testGetAndVerifyBackupPathsValidPath
     *
     * @return void
     */
    public function testGetAndVerifyBackupPathsValidPath()
    {
        $backups = ['sources' => [
            [
                'name' => 'foo',
                'root' => '/bar/baz/',
                'dirs' => [
                    'one' => '',
                    'two' => '--special-power zoom',
                ],
            ],
            [
                'name' => 'bar',
                'root' => '/ding/dong/',
                'dirs' => [
                    'img' => '',
                ],
            ],
        ]];
        $expected = [
            'foo' => [
                'one' => [
                    'path' => '/bar/baz/one',
                    'option' => '',
                ],
                'two' => [
                    'path' => '/bar/baz/two',
                    'option' => '--special-power zoom',
                ],
            ],
            'bar' => [
                'img' => [
                    'path' => '/ding/dong/img',
                    'option' => '',
                ],
            ],
        ];
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(['verifyPath'])
            ->getMock();

        $pawsback->expects($this->any())
            ->method('verifyPath')
            ->will($this->returnValue(true));

        $result = $pawsback->getAndVerifyBackupPaths($backups);
        $this->assertSame($expected, $result);
    }

    /**
     * testCheckAndCreateBucketBucketExists
     *
     * @return void
     */
    public function testCheckAndCreateBucketBucketExists()
    {
        $provider = ['bucket' => 'foo'];
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $S3Client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['doesBucketExist', 'createBucket'])
            ->getMock();

        $S3Client->expects($this->once())
            ->method('doesBucketExist')
            ->will($this->returnValue(true));

        $result = $pawsback->checkAndCreateBucket($S3Client, $provider);
        $this->assertTrue($result);
    }

    /**
     * testCheckAndCreateBucketCreateBucketSuccess
     *
     * @return void
     */
    public function testCheckAndCreateBucketCreateBucketSuccess()
    {
        $provider = ['bucket' => 'foo'];
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $S3Client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['doesBucketExist', 'createBucket'])
            ->getMock();

        $S3Client->expects($this->once())
            ->method('doesBucketExist')
            ->will($this->returnValue(false));
        $S3Client->expects($this->once())
            ->method('createBucket')
            ->will($this->returnValue(true));

        $result = $pawsback->checkAndCreateBucket($S3Client, $provider);
        $this->assertTrue($result);
    }

    /**
     * testCheckAndCreateBucketCreateBucketFails
     *
     * @return void
     */
    public function testCheckAndCreateBucketCreateBucketFails()
    {
        $provider = ['bucket' => 'foo'];
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $S3Client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['doesBucketExist', 'createBucket'])
            ->getMock();
        $AwsException = $this->getMockBuilder('Aws\Exception\AwsException')
            ->disableOriginalConstructor()
            ->getMock();
        $AwsCommandInterface = $this->getMockBuilder('Aws\CommandInterface')
            ->getMock();

        $S3Client->expects($this->once())
            ->method('doesBucketExist')
            ->will($this->returnValue(false));
        $S3Client->expects($this->once())
            ->method('createBucket')
            ->will($this->throwException(new $AwsException(new \Exception, $AwsCommandInterface)));

        $this->expectException('DomainException');
        $result = $pawsback->checkAndCreateBucket($S3Client, $provider);
    }

    /**
     * testPrepareProvider
     *
     * @return void
     */
    public function testPrepareProvider()
    {
        $provider = [
            'region' => 'us-west-1',
            'delete' => false,
        ];
        $expected = [
            'version' => 'latest',
            'region' => 'us-west-1',
            'profile' => 'default',
            'delete' => false,
            'options' => null,
        ];

        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $pawsback->prepareProvider($provider);
        $this->assertSame($expected, $result);
    }

    /**
     * testVerifyPath
     *
     * @return void
     */
    public function testVerifyPath()
    {
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(['newSplFileInfo'])
            ->getMock();
        $fileObject = $this->getMockBuilder('\SplFileInfo')
            ->disableOriginalConstructor()
            ->setMethods(['isDir'])
            ->getMock();

        $pawsback->expects($this->once())
            ->method('newSplFileInfo')
            ->will($this->returnValue($fileObject));
        $fileObject->expects($this->once())
            ->method('isDir')
            ->will($this->returnValue(true));

        $pawsback->verifyPath($this->path);
    }

    /**
     * testGetS3Client
     *
     * @return void
     */
    public function testGetS3Client()
    {
        $provider = [
            'region' => 'us-east-1',
            'version' => 'latest',
        ];

        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $pawsback->getS3Client($provider);
        $this->assertInstanceOf(\Aws\S3\S3Client::class, $result);
    }

    /**
     * testNewSplFileInfo
     *
     * @return void
     */
    public function testNewSplFileInfo()
    {
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $pawsback->newSplFileInfo($this->path);
        $this->assertInstanceOf(\SplFileInfo::class, $result);
    }

    /**
     * testNewSplFileObject
     *
     * @return void
     */
    public function testNewSplFileObject()
    {
        $pawsback = $this->getMockBuilder('\Pawsback\Test\TestPawsback')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $pawsback->newSplFileObject($this->path . 'test.json', 'r');
        $this->assertInstanceOf(\SplFileObject::class, $result);
    }
}
