<?php
namespace Pawsback\Sdk;

use Aws\Exception\AwsException;
use Aws\S3\Transfer;
use Pawsback\Pawsback;

/**
 * Class: Backup
 *
 */
class Backup extends Pawsback {

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
        parent::__construct($path, $verbose, $debug);
    }

    /**
     * run
     *
     * @return void
     */
    public function run()
    {
        $config = $this->getConfig();
        $provider = $this->getProvider($config, 'S3');
        $provider = $this->prepareProvider($provider);
        $client = $this->getS3Client($provider);

        if (!$client->doesBucketExist($provider['bucket'])) {
            try {
                $client->createBucket(['Bucket' => $provider['bucket']]);
            } catch (AwsException $e) {
                return $e->getMessage();
            }
        }

        $backups = $this->getAndVerifyBackupPaths($config['backups']);

        $options = $this->debug ? ['debug' => true] : [];
        foreach ($backups as $name => $backup) {
            foreach ($backup as $key => $source) {
                $dest = 's3://' . $provider['bucket'] . '/' . $name . '/' . $key;

                if ($this->verbose) {
                    print_r(PHP_EOL . "Source: $source" . PHP_EOL . "Dest: $dest" . PHP_EOL);
                }

                $transfer = $this->getTransfer($client, $source, $dest, $options);
                $transfer->promise()->wait();
            }
        }

        if ($this->verbose) {
            $this->output .= 'Backup complete' . PHP_EOL;
        }

        return $this->output;
    }

    /**
     * getTransfer
     *
     * @param \Aws\S3\S3Client $client an S3Client instance
     * @param string $source The source directory
     * @param string $dest The destination directory
     * @param array $options Transfer options
     * @return object An instance of Transfer
     */
    protected function getTransfer(\Aws\S3\S3Client $client, $source, $dest, array $options = [])
    {
        return new Transfer($client, $source, $dest, $options);
    }
}
