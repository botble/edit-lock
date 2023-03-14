<?php

namespace Botble\EditLock\Listeners;

use Botble\Base\Events\UpdatedContentEvent;
use EditLock;

class UpdatedContentListener
{
    public function handle(UpdatedContentEvent $event): void
    {
        $model = $event->data;

        if (is_in_admin(true) && is_object($model) && EditLock::isSupportedModule(get_class($model))) {
            EditLock::deleteMetaData($model);
        }
    }
}
