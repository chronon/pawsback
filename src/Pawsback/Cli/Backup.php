<?php
namespace Pawsback\Cli;

use Pawsback\Pawsback;

/**
 * Class: Backup
 *
 */
class Backup extends Pawsback
{

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
        $this->cliExists();
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

                $cmd = $this->cliSyncCmd . ' ' . $source['path'] . ' ' . $dest;
                $cmd .= ' --region ' . $this->provider['region'];
                $cmd .= ' --profile ' . $this->provider['profile'];
                $cmd .= $this->provider['delete'] ? ' --delete' : '';
                $cmd .= $this->provider['options'] ? ' ' . $this->provider['options'] : '';
                $cmd .= $source['option'] ? ' ' . $source['option'] : '';
                $cmd .= $this->debug ? ' ' . $this->cliDryRunCmd : '';

                if ($this->verbose) {
                    $this->output .= 'Source: ' . $source['path'] . PHP_EOL . "Dest: $dest" . PHP_EOL;
                    $this->output .= 'Command: ' . $cmd . PHP_EOL;
                }

                $action = false;
                $result = $this->shellExec($cmd);
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

    /**
     * shellExec
     *
     * @param string $cmd The command to run
     * @return bool
     */
    protected function shellExec($cmd)
    {
        return shell_exec($cmd);
    }

    /**
     * cliExists
     *
     * @return void
     */
    protected function cliExists()
    {
        exec('command -v aws >/dev/null 2>&1 || { exit 1; }', $out, $return);

        if ($return == 1) {
            throw new \RuntimeException('The `aws` CLI command cannot be found.');
        }

        return true;
    }
}
