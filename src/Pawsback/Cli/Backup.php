<?php
namespace Pawsback\Cli;

use Pawsback\Pawsback;

/**
 * Class: Backup
 *
 */
class Backup extends Pawsback {

    /**
     * cliSyncCmd
     *
     * @var string
     */
    protected $cliSyncCmd = 'aws s3 sync';

    /**
     * cliDryRunCmd
     *
     * @var string
     */
    protected $cliDryRunCmd = '--dryrun';

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
        foreach ($this->backups as $name => $backup) {
            foreach ($backup as $key => $source) {
                $dest = 's3://' . $this->provider['bucket'] . '/' . $name . '/' . $key;

                $cmd = $this->cliSyncCmd . ' ' . $source . ' ' . $dest;
                $cmd .= ' --region ' . $this->provider['region'];
                $cmd .= ' --profile ' . $this->provider['profile'];
                if ($this->debug) {
                    $cmd .= ' ' . $this->cliDryRunCmd;
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
