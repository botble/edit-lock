<?php

namespace Botble\EditLock\Providers;

use Illuminate\Support\ServiceProvider;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\EditLock\Facades\EditLockFacade;
use Illuminate\Foundation\AliasLoader;

class EditLockServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->setNamespace('plugins/edit-lock');

        AliasLoader::getInstance()->alias('EditLock', EditLockFacade::class);
    }

    public function boot(): void
    {
        $this
            ->loadAndPublishConfigurations(['general'])
            ->loadAndPublishTranslations()
            ->loadAndPublishViews();

        $this->app->register(EventServiceProvider::class);
        $this->app->register(HookServiceProvider::class);
    }
}
