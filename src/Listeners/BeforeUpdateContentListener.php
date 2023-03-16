<?php

namespace Botble\EditLock\Listeners;

use Botble\Base\Events\BeforeUpdateContentEvent;
use Botble\Base\Http\Responses\BaseHttpResponse;
use EditLock;
use Illuminate\Support\Arr;

class BeforeUpdateContentListener
{
    public function handle(BeforeUpdateContentEvent $event): void
    {
        $model = $event->data;

        if (is_object($model) && EditLock::isSupportedModule(get_class($model))) {
            $user = EditLock::user();

            if ($user && ($metadata = EditLock::getMetaData($model))) {
                if (EditLock::isNotUser($metadata, $user)) {
                    $response = (new BaseHttpResponse())
                        ->withInput()
                        ->setError(true)
                        ->setMessage(trans('plugins/edit-lock::edit-lock.user_is_currently_editing', ['name' => Arr::get($metadata, 'user.name')]));
                    abort($response);
                }
            }
        }
    }
}
