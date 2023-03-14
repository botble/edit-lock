<?php

namespace Botble\EditLock\Listeners;

use Assets;
use Botble\Base\Events\BeforeEditContentEvent;
use Botble\Base\Http\Responses\BaseHttpResponse;
use EditLock;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class BeforeEditContentListener
{
    public function handle(BeforeEditContentEvent $event): void
    {
        $model = $event->data;
        if (is_in_admin(true) && is_object($model) && EditLock::isSupportedModule(get_class($model))) {
            $request = $event->request;

            Assets::addScriptsDirectly(['/vendor/core/plugins/edit-lock/js/edit-lock.js']);

            $user = Auth::user();

            $response = new BaseHttpResponse();

            $metadata = EditLock::getMetaData($model);
            if ($metadata && Arr::get($metadata, 'user.id') != $user->getKey()) {
                if ($request->wantsJson()) {
                    if ($request->input('_el_take_over')) {
                        $metadata = EditLock::updateMetaData($model, $metadata);
                        abort($response->setData(['metadata' => $metadata])->setMessage(trans('plugins/edit-lock::edit-lock.taken_over_successfully')));
                    } else {
                        if (class_exists('Debugbar')) {
                            \Debugbar::disable();
                        }
                        abort($response->setData($this->responseData($metadata))->setError(true));
                    }
                }
            } else {
                $metadata = EditLock::updateMetaData($model, $metadata ?: []);

                if ($request->wantsJson() && ($request->input('_el_ping') || $request->input('_el_check'))) {
                    abort($response->setData($this->responseData($metadata)));
                }
            }

            add_action(BASE_ACTION_TOP_FORM_CONTENT_NOTIFICATION, function () use ($metadata, $user) {
                $userName = '';
                if ($metadata && Arr::get($metadata, 'user.id') != $user->getKey()) {
                    $userName = Arr::get($metadata, 'user.name');
                }

                echo view('plugins/edit-lock::notification', ['name' => $userName]);
            });

            add_filter(BASE_FILTER_FOOTER_LAYOUT_TEMPLATE, fn ($html) => $html .= view('plugins/edit-lock::footer', ['metadata' => $metadata]));
        }
    }

    protected function responseData(array $metadata)
    {
        return [
            'metadata' => $metadata,
            'notification' => view('plugins/edit-lock::notification', Arr::get($metadata, 'user'))->render(),
            'modal_body' => view('plugins/edit-lock::modal-body', ['metadata' => $metadata])->render(),
        ];
    }
}
