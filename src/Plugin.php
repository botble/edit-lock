<?php

namespace Botble\EditLock;

use Botble\Base\Models\MetaBox;
use Botble\PluginManagement\Abstracts\PluginOperationAbstract;

class Plugin extends PluginOperationAbstract
{
    public static function remove()
    {
        MetaBox::where('meta_key', 'edit_lock')->delete();
    }
}
