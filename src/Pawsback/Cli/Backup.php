<?php
namespace Pawsback\Cli;

use Pawsback\Pawsback;

/**
 * Class: Backup
 *
 */
class Backup extends Pawsback {

    /**
     * cmd
     *
     * @var string
     */
    protected $syncCmd = 'aws s3 sync';

    /**
     * dryRunCmd
     *
     * @var string
     */
    protected $dryRunCmd = '--dryrun';

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
        $this->checkAndCreateBucket($client, $provider);
        $backups = $this->getAndVerifyBackupPaths($config['backups']);

        foreach ($backups as $name => $backup) {
            foreach ($backup as $key => $source) {
                $dest = 's3://' . $provider['bucket'] . '/' . $name . '/' . $key;

                $cmd = $this->syncCmd . ' ' . $source . ' ' . $dest;
                $cmd .= ' --region ' . $provider['region'];
                $cmd .= ' --profile ' . $provider['profile'];
                if ($this->debug) {
                    $cmd .= ' ' . $this->dryRunCmd;
                }

                if ($this->verbose) {
                    $this->output .= "Source: $source" . PHP_EOL . "Dest: $dest" . PHP_EOL;
                    $this->output .= 'Command: ' . $cmd . PHP_EOL;
                }

                $action = false;
                $result = shell_exec($cmd);
                if ($result != '') {
                    $this->output .= $result . PHP_EOL;
                    $action = true;
                }
            }
        }

        if (!$action) {
            $this->output .= 'No files in need of sync.' . PHP_EOL;
        }

        return $this->output;
    }
}
