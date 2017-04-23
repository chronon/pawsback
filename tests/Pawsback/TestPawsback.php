<?php
namespace Pawsback\Test;

use Pawsback\Pawsback;

/**
 * Class: TestPawsback
 *
 * @see Pawsback
 */
class TestPawsback extends Pawsback
{
    public $defaults = [
        'version' => 'latest',
        'region' => 'us-east-1',
        'profile' => 'default',
        'delete' => true,
        'options' => null,
    ];

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

    public function verifyPath($path)
    {
        return parent::verifyPath($path);
    }

    public function newSplFileInfo($path)
    {
        return parent::newSplFileInfo($path);
    }

    public function newSplFileObject($path, $mode)
    {
        return parent::newSplFileObject($path, $mode);
    }
}
