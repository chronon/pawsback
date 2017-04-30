<?php
namespace Pawsback;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

/**
 * Class: Pawsback
 *
 */
class Pawsback
{

    /**
     * defaults
     *
     * @var array
     */
    protected $defaults = [
        'version' => 'latest',
        'region' => 'us-east-1',
        'profile' => 'default',
        'delete' => true,
        'options' => null,
    ];

    /**
     * path
     *
     * @var string
     */
    protected $path;

    /**
     * verbose
     *
     * @var mixed
     */
    protected $verbose;

    /**
     * debug
     *
     * @var mixed
     */
    protected $debug;

    /**
     * config
     *
     * @var mixed
     */
    protected $config;

    /**
     * client
     *
     * @var mixed
     */
    protected $client;

    /**
     * output
     *
     * @var string
     */
    public $output = '';

    /**
     * provider
     *
     * @var mixed
     */
    public $provider;

    /**
     * backups
     *
     * @var mixed
     */
    public $backups;

    /**
     * __construct
     *
     * @param mixed $path The path to the config file
     * @param bool $verbose Verbose output
     * @param bool $debug Debug output
     * @return void
     */
    public function __construct($path = null, $verbose = false, $debug = false)
    {
        if (!$path) {
            throw new \InvalidArgumentException('Missing required path value.');
        }

        $this->validatePath($path);

        $this->path = $path;
        $this->verbose = $verbose;
        $this->debug = $debug;

        $this->config = $this->getConfig();
        $this->provider = $this->prepareProvider($this->getProvider($this->config, 'S3'));
        $this->client = $this->getS3Client($this->provider);
        $this->checkAndCreateBucket($this->client, $this->provider);
        $this->backups = $this->getAndVerifyBackupPaths($this->config['backups']);
    }

    /**
     * validatePath
     *
     * @param string $path The path to validate
     * @return bool true if the path exists
     * @throws InvalidArgumentException
     */
    protected function validatePath($path)
    {
        $fileInfo = $this->newSplFileInfo($path);
        if (!$fileInfo->isReadable()) {
            throw new \InvalidArgumentException('Supplied config file is unreadable.');
        }

        return true;
    }

    /**
     * getConfig
     *
     * @return array An array of the json configuration
     */
    protected function getConfig()
    {
        $fileObject = $this->newSplFileObject($this->path, 'r');
        return json_decode($fileObject->fread($fileObject->getSize()), true);
    }

    /**
     * getProvider
     *
     * @param array $config The configuration
     * @param string $provider The provider type
     * @return mixed The provider array or null
     */
    protected function getProvider(array $config, $provider)
    {
        return isset($config['provider'][$provider]) ? $config['provider'][$provider] : null;
    }

    /**
     * getAndVerifyBackupPaths
     *
     * @param array $backups The backup array
     * @return array $paths The full paths to use as backup source
     */
    protected function getAndVerifyBackupPaths(array $backups)
    {
        $paths = [];
        foreach ($backups['sources'] as $source) {
            foreach ($source['dirs'] as $dir => $option) {
                $path = $source['root'] . $dir;
                if ($this->verifyPath($path)) {
                    $paths[$source['name']][$dir]['path'] = $path;
                    $paths[$source['name']][$dir]['option'] = $option;
                } else {
                    throw new \DomainException('Invalid path: ' . $path);
                }
            }
        }

        return $paths;
    }

    /**
     * checkAndCreateBucket
     *
     * @param \Aws\S3\Client $client The client
     * @param array $provider The provider
     * @return mixed
     */
    protected function checkAndCreateBucket(\Aws\S3\S3Client $client, $provider)
    {
        if (!$client->doesBucketExist($provider['bucket'])) {
            try {
                $client->createBucket(['Bucket' => $provider['bucket']]);
            } catch (AwsException $e) {
                throw new \DomainException($e->getMessage());
            }
        }

        return true;
    }

    /**
     * prepareProvider
     *
     * @param array $provider The provider
     * @return array The provider merged with the defaults
     */
    protected function prepareProvider(array $provider)
    {
        return array_merge($this->defaults, $provider);
    }

    /**
     * verifyPath
     *
     * @param string $path The path to check
     * @return bool True if the path is a directory
     */
    protected function verifyPath($path)
    {
        $fileObject = $this->newSplFileInfo($path, 'r');
        return $fileObject->isDir();
    }

    /**
     * getS3Client
     *
     * @param array $provider The provider
     * @return object An instance of S3Client
     */
    protected function getS3Client(array $provider)
    {
        return new S3Client($provider);
    }

    /**
     * newSplFileInfo
     *
     * @param mixed $path The path
     * @return void
     */
    protected function newSplFileInfo($path)
    {
        return new \SplFileInfo($path);
    }

    /**
     * newSplFileObject
     *
     * @param mixed $path The path
     * @param mixed $mode The mode
     * @return void
     */
    protected function newSplFileObject($path, $mode)
    {
        return new \SplFileObject($path, $mode);
    }
}
