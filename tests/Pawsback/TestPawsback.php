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
