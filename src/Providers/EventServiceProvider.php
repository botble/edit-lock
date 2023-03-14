<?php

namespace Botble\EditLock\Providers;

use Botble\Base\Events\BeforeEditContentEvent;
use Botble\Base\Events\BeforeUpdateContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\EditLock\Listeners\BeforeEditContentListener;
use Botble\EditLock\Listeners\BeforeUpdateContentListener;
use Botble\EditLock\Listeners\UpdatedContentListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BeforeEditContentEvent::class => [
            BeforeEditContentListener::class,
        ],
        BeforeUpdateContentEvent::class => [
            BeforeUpdateContentListener::class,
        ],
        UpdatedContentEvent::class => [
            UpdatedContentListener::class,
        ],
    ];
}
