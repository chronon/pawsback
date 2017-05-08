<?php
namespace Pawsback\Cli;

use Pawsback\Pawsback;

/**
 * Class: Backup
 */
class Backup extends Pawsback
{

    /**
     * The primary `aws` cli sync command
     *
     * @var string
     */
    public $cliSyncCmd = 'aws s3 sync';

    /**
     * The string to use to enable dry run mode
     *
     * @var string
     */
    public $cliDryRunCmd = '--dryrun';

    /**
     * Constructor
     *
     * @param mixed $path The path to the config file
     * @param array $options Optional options
     * @return void
     */
    public function __construct($path = null, array $options = [])
    {
        $this->cliExists();
        parent::__construct($path, $options);
    }

    /**
     * Assembles aws cli commands and runs them
     *
     * @return string The resulting output
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
                $cmd .= $this->options['debug'] ? ' ' . $this->cliDryRunCmd : '';

                if ($this->options['verbose']) {
                    $this->output .= 'Source: ' . $source['path'] . PHP_EOL . "Dest: $dest" . PHP_EOL;
                    $this->output .= 'Command: ' . $cmd . PHP_EOL;
                }

                if ($this->options['generate']) {
                    $action = true;
                    $this->output .= $cmd . PHP_EOL;
                } else {
                    $action = false;
                    $result = $this->shellExec($cmd);
                    if ($result != '') {
                        $this->output .= $result . PHP_EOL;
                        $action = true;
                    }
                }
            }
        }

        if (!$action) {
            $this->output .= PHP_EOL . 'No files in need of sync.' . PHP_EOL;
        }

        return $this->output;
    }

    /**
     * Wrapper method for `shell_exec`
     *
     * @param string $cmd The command to run
     * @return bool The result of `shell_exec`
     * @codeCoverageIgnore Don't need to test PHP functions
     */
    protected function shellExec($cmd)
    {
        return shell_exec($cmd);
    }

    /**
     * Checks for the existence of the aws cli tool
     *
     * @return bool True if the aws cli too exists
     * @throws RuntimeException If the aws cli tool cannot be found
     */
    protected function cliExists()
    {
        if ($this->checkForCli() == 1) {
            throw new \RuntimeException('The `aws` CLI command cannot be found.');
        }

        return true;
    }

    /**
     * Checks for the existence of the aws cli tool
     *
     * @return int The return status of the executed command
     * @codeCoverageIgnore Don't need to test PHP functions
     */
    protected function checkForCli()
    {
        exec('command -v aws >/dev/null 2>&1 || { exit 1; }', $out, $return);
        return $return;
    }
}
