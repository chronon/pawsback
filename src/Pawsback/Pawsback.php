<?php
namespace Pawsback;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

/**
 * Class: Pawsback
 */
class Pawsback
{

    /**
     * S3 defaults that can be overridden in the config file
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
     * @var bool
     */
    protected $verbose;

    /**
     * debug
     *
     * @var bool
     */
    protected $debug;

    /**
     * config
     *
     * @var array
     */
    protected $config = [];

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
     * @var array
     */
    public $provider = [];

    /**
     * backups
     *
     * @var array
     */
    public $backups = [];

    /**
     * Constructor
     *
     * @param mixed $path The path to the config file
     * @param bool $verbose Verbose output if true
     * @param bool $debug Dry run mode or Debug output
     * @return void
     * @throws InvalidArgumentException If $path is missing
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
     * Validates the config file exists and is readable
     *
     * @param string $path The path to validate
     * @return bool True if the path exists
     * @throws InvalidArgumentException If the config file can't be read
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
     * Converts the json config file into an array
     *
     * @return array An array of the json configuration
     */
    protected function getConfig()
    {
        $fileObject = $this->newSplFileObject($this->path, 'r');
        return json_decode($fileObject->fread($fileObject->getSize()), true);
    }

    /**
     * Extracts the `provider` array from the config array
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
     * Extracts and verifies the backup paths from the config file
     *
     * @param array $backups The backup array
     * @return array $paths The full paths to use as backup source
     * @throws DomainException If a backup path is not valid
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
     * Checks if the bucket exists and creates it if not
     *
     * @param \Aws\S3\Client $client The S3 client object
     * @param array $provider The provider configuration
     * @return mixed
     * @throws DomainException If an AwsException is thrown
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
     * Merges the default provider array with overrides from the config file
     *
     * @param array $provider The provider
     * @return array The provider merged with the defaults
     */
    protected function prepareProvider(array $provider)
    {
        return array_merge($this->defaults, $provider);
    }

    /**
     * Verifies a path exists
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
     * Gets an instance of S3Client
     *
     * @param array $provider The provider configuration
     * @return object An instance of S3Client
     */
    protected function getS3Client(array $provider)
    {
        return new S3Client($provider);
    }

    /**
     * Gets an instance of SplFileInfo
     *
     * @param mixed $path The path to something
     * @return object An instance of SplFileInfo
     */
    protected function newSplFileInfo($path)
    {
        return new \SplFileInfo($path);
    }

    /**
     * Gets an instance of SplFileObject
     *
     * @param string $path The path to the file
     * @param string $mode The open mode to use
     * @return object An instance of SplFileObject
     */
    protected function newSplFileObject($path, $mode)
    {
        return new \SplFileObject($path, $mode);
    }
}
