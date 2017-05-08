<?php
namespace Pawsback\Sdk;

use Aws\S3\Transfer;
use Pawsback\Pawsback;

/**
 * Class: Backup
 */
class Backup extends Pawsback
{

    /**
     * Constructor
     *
     * @param mixed $path The path to the config file
     * @param array $options Optional options
     * @return void
     */
    public function __construct($path = null, array $options = [])
    {
        parent::__construct($path, $options);
    }

    /**
     * Run the backup job
     *
     * @return string The resulting output
     */
    public function run()
    {
        $verbose = $this->options['verbose'];
        unset($this->options['verbose']);

        foreach ($this->backups as $name => $backup) {
            foreach ($backup as $key => $source) {
                $dest = 's3://' . $this->provider['bucket'] . '/' . $name . '/' . $key;

                if ($verbose) {
                    print_r(PHP_EOL . "Source: $source" . PHP_EOL . "Dest: $dest" . PHP_EOL);
                }

                $transfer = $this->getTransfer($this->client, $source, $dest, $this->options);
                $transfer->promise()->wait();
            }
        }

        if ($verbose) {
            $this->output .= 'Backup complete' . PHP_EOL;
        }

        return $this->output;
    }

    /**
     * Gets an instance of the Transfer class
     *
     * @param \Aws\S3\S3Client $client an S3Client instance
     * @param string $source The source directory
     * @param string $dest The destination directory
     * @param array $options Transfer options
     * @return object An instance of Transfer
     */
    protected function getTransfer(\Aws\S3\S3Client $client, $source, $dest, array $options = [])
    {
        return new Transfer($this->client, $source, $dest, $options);
    }
}
