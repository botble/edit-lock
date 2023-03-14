<?php

namespace Botble\EditLock\Facades;

use Botble\EditLock\Supports\EditLock;
use Illuminate\Support\Facades\Facade;

class EditLockFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EditLock::class;
    }
}
