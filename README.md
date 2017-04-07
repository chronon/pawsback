## pawsback

* `p` as in PHP
* `aws` as in Amazon Web Services
* `back` as in backup

## Configuration

```
{
    "provider": {
        "S3": {
            "bucket": "bucket-name"
        }
    },
    "backups": {
        "sources": [
            {
                "name": "foo.com",
                "root": "/home/chronon/repos/pawsback/tests/test_app/foo.com/",
                "dirs": {
                    "shared/img": "",
                    "shared/files": ""
                }
            },
            {
                "name": "bar.com",
                "root": "/home/chronon/repos/pawsback/tests/test_app/bar.com/",
                "dirs": {
                    "shared/files": "",
                    "prefixed": "--exclude '*' --include 'baz_*'"
                }
            }
        ]
    }
}
```

```
{
    "provider": {
        "S3": {
            "version": "latest",
            "region": "us-east-1",
            "profile": "default",
            "delete": true,
            "options": "",
            "credentials": {
                "key": "",
                "secret": ""
            }
        }
    }
}
```

## Reference:

* https://docs.aws.amazon.com/aws-sdk-php/v3/guide/index.html
* https://docs.aws.amazon.com/aws-sdk-php/v3/guide/service/s3-transfer.html
* https://docs.aws.amazon.com/aws-sdk-php/v3/api/
