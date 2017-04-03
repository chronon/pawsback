<?php
namespace Pawsback;

use Aws\S3\S3Client;

/**
 * Class: Pawsback
 *
 */
class Pawsback {

    /**
     * defaults
     *
     * @var array
     */
    protected $defaults = [
        'version' => 'latest',
        'region' => 'us-east-1',
        'profile' => 'default',
    ];

    /**
     * output
     *
     * @var string
     */
    protected $output = '';

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
    }

    /**
     * validatePath
     *
     * @param string $path The path to validate
     * @return bool true if the path exists
     * @throws InvalidArgumentException
     */
    public function validatePath($path)
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
    public function getConfig()
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
            foreach ($source['dirs'] as $dir) {
                $path = $source['root'] . $dir;
                if ($this->verifyPath($path)) {
                    $paths[$source['name']][$dir] = $path;
                } else {
                    throw new \DomainException('Invalid path: ' . $path);
                }
            }
        }

        return $paths;
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
    protected function getS3Client(array $provider) {
        return new S3Client($provider);
    }

    /**
     * newSplFileInfo
     *
     * @param mixed $path The path
     * @return void
     * @codeCoverageIgnore Don't test PHP's ability to use `new`
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
     * @codeCoverageIgnore Don't test PHP's ability to use `new`
     */
    protected function newSplFileObject($path, $mode)
    {
        return new \SplFileObject($path, $mode);
    }
}
