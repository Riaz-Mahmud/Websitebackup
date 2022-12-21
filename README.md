# Website Backup

This package is used to backup any website link. It will download all the files including images, css, js, etc. and save it in a folder.

## Installation

```bash

composer require backdoor/websitebackup

```

## Usage

```php

use Backdoor\WebsiteBackup\WebsiteBackup;

```
```php
function siteBackup(){

    $url = 'link to your website page to backup';
    $path = 'path to save backup file';
    $filePath = 'path to set as file path in backup file. This is optional. If not set, it will use the $path';

    $websiteBackup = new WebsiteBackup();
    $backup = $websiteBackup->backup($url, $path);

}

```

## Return

```php

array:3 [▼[
    'error' => false,
    'message' => 'Backup created successfully',
    'path' => 'your_given_path/index.html'
];

```

## Example

```php

use Backdoor\WebsiteBackup\WebsiteBackup;

```
```php

function siteBackup(){
    $url = 'link to your website page to backup';
    $path = 'path to save backup file';
    $filePath = 'path to set as file path in backup file. This is optional. If not set, it will use the $path';

    $websiteBackup = new WebsiteBackup();
    $backup = $websiteBackup->backup($url, $path, $filePath);

    if(!$backup['error']){
        echo $backup['path'];
    }
}

```

## License

The MIT License (MIT). Please see [License](LICENSE) for more information.


## Security

If you discover any security related issues, please email [email protected] instead of using the issue tracker.
