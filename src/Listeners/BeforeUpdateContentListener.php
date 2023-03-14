<?php

namespace Botble\EditLock\Listeners;

use Botble\Base\Events\BeforeUpdateContentEvent;
use Botble\Base\Http\Responses\BaseHttpResponse;
use EditLock;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class BeforeUpdateContentListener
{
    public function handle(BeforeUpdateContentEvent $event): void
    {
        $model = $event->data;

        if (is_in_admin(true) && is_object($model) && EditLock::isSupportedModule(get_class($model))) {
            $user = Auth::user();

            if ($metadata = EditLock::getMetaData($model)) {
                if (Arr::get($metadata, 'user.id') != $user->getKey()) {
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
