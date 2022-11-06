<?php

namespace Backdoor\WebsiteBackup;

use Illuminate\Support\Facades\Facade;

class WebsiteBackupFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'WebsiteBackup';
    }
}
