<?php
use PHPUnit\Framework\TestCase;
use Pawsback\Pawsback;

/**
 * Class: PawsbackTestClass
 *
 * @see Pawsback
 */
class PawsbackTestClass extends Pawsback
{
    public function validatePath($path)
    {
        return parent::validatePath($path);
    }

    public function getConfig()
    {
        return parent::getConfig();
    }

    public function getProvider(array $config, $provider)
    {
        return parent::getProvider($config, $provider);
    }

    public function prepareProvider(array $provider)
    {
        return parent::prepareProvider($provider);
    }

    public function getS3Client(array $provider)
    {
        return parent::getS3Client($provider);
    }

    public function checkAndCreateBucket(\Aws\S3\S3Client $client, $provider)
    {
        return parent::checkAndCreateBucket($client, $provider);
    }

    public function getAndVerifyBackupPaths(array $pawsbacks)
    {
        return parent::getAndVerifyBackupPaths($pawsbacks);
    }
}

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
        $pawsback = $this->getMockBuilder('PawsbackTestClass')
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
        $pawsback = $this->getMockBuilder('PawsbackTestClass')
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
        $pawsback = $this->getMockBuilder('PawsbackTestClass')
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
        $pawsback = $this->getMockBuilder('PawsbackTestClass')
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
        $pawsback = $this->getMockBuilder('PawsbackTestClass')
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
}
