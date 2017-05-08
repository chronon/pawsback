[![CircleCI](https://circleci.com/gh/chronon/pawsback.svg?style=shield&circle-token=133a5b115b9c8fc8a5c454c1e051a60a0af7bf8c)](https://circleci.com/gh/chronon/pawsback)

# PawsBack

* **`P` as in PHP** since that's what it's written in.
* **`aws` as in Amazon Web Services S3** since that is where it backs things up to.
* **`Back` as in backup** since that is what it does.

`PawsBack` is a PHP CLI tool which allows you to configure multiple backup sources in a configuration file and sync them to S3. The AWS PHP SDK is used for bucket checking and creation, but not for uploading since it offers no intelligent syncing (everything is uploaded every time). `PawsBack` generates commands for the [aws cli tool](https://aws.amazon.com/cli/) which does have an intelligent sync mode, allowing you to upload only changed files and delete ones that no longer exist.

## Requirements

* PHP - developed and tested with v7.0.x but should work on v5.6 as well ([see test build](https://circleci.com/gh/chronon/pawsback/44) with PHP v5.6.22 and PHPUnit v5.7.19). To install with PHP 5.6 change the `require` version in `composer.json`, and optionally set the PHPUnit version to `^5.0` to run tests.
* The [aws cli tool](https://aws.amazon.com/cli/), which requires Python. The [bundled installer](http://docs.aws.amazon.com/cli/latest/userguide/awscli-install-bundle.html) is very simple to install.

## Installation

To install globally into `~/.composer/vendor/bin`:

```
composer global require chronon/pawsback
```

You can then symlink `~/.composer/vendor/bin` to an existing directory in your `PATH` (such as `~/bin`) or add the directory to your `PATH`.

## Configuration

A sample configuration file can be found at `tests/test_app/test.json` and is similar to the one below.

#### `provider:S3`

* required `bucket`: the bucket to backup to
* optional `region`: S3 region, default is `"us-east-1"`
* optional `profile`: aws cli tool profile, default is `"default"`
* optional `delete`: whether to delete S3 files if removed from source, default is `true`
* optional `options`: any additional aws cli tool options to add globally, default is `""`

#### `backups:sources`

* required `name`: the directory on S3 that will be be created and store the data for this source
* required `root`: an absolute path to the backup root directory
* required `dirs`: and array of directories as key and backup optional options as values

The [S3 command documentation](http://docs.aws.amazon.com/cli/latest/userguide/using-s3-commands.html) has lots of useful examples explaining `--exclude`, `--include`, etc. An example is in the sample configuration file below, where in the `prefixed` subdirectory only files that start with `baz_` would be backed up.

#### `PawsBack` sample configuration file:

``` json
{
    "provider": {
        "S3": {
            "bucket": "chronon-pawsback-test-1"
        }
    },
    "backups": {
        "sources": [
            {
                "name": "foo.com",
                "root": "/home/ubuntu/pawsback/tests/test_app/foo.com/",
                "dirs": {
                    "shared/img": "",
                    "shared/files": ""
                }
            },
            {
                "name": "bar.com",
                "root": "/home/ubuntu/pawsback/tests/test_app/bar.com/",
                "dirs": {
                    "shared/files": "",
                    "prefixed": "--exclude '*' --include 'baz_*'"
                }
            }
        ]
    }
}
```

## Usage

The single entry point is `bin/pawsback`, which can be run with the `-h` options for help:

```
pawsback help:

  -p    The full path to the backup config file
  -v    Verbose output, can be used with dry run
  -d    Dry run, display what would happen without action
  -g    Generate mode, display a list of commands without validating anything
  -h    This help message
```

**NOTES:**

* The `-d` option for dry run **will check and create a bucket** if it doesn't exist as it's using the aws cli tool's dry run mode. To just see the commands that will run without checking/creating a bucket or verifying backup paths, use the `-g` mode to generate a list of commands.
* All options can be used together.

## AWS CLI Configuration

Once installed the `aws` cli tool can be configured by running `aws configure`, or by creating the simple configuration files `config` and `credentials` in `~/.aws`. If multiple profiles are needed, configure the profiles in the `aws` configuration (see [named profiles](https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-getting-started.html)) and use the named profile in your `provider:S3:profile` configuration.

## Tests

Tests (PHP Codesniffer and PHPUnit) can be run with `bin/run-tests`. Code coverage is **100%**, and any pull requests **must** include applicable tests.

## Reference:

* [https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-welcome.html](http://docs.aws.amazon.com/cli/latest/userguide/cli-chap-welcome.html)
* [https://docs.aws.amazon.com/aws-sdk-php/v3/guide/](https://docs.aws.amazon.com/aws-sdk-php/v3/guide/)
* [https://docs.aws.amazon.com/aws-sdk-php/v3/api/](https://docs.aws.amazon.com/aws-sdk-php/v3/api/)
