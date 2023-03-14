<?php

namespace Botble\EditLock\Supports;

use Botble\Base\Models\BaseModel;
use Botble\Support\Services\Cache\Cache;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use MetaBox;

class EditLock
{
    protected Cache $cache;

    protected bool $useCache = true;

    protected int $interval;

    public function __construct()
    {
        $this->cache = new Cache(app('cache'), self::class);
        $this->useCache = config('plugins.edit-lock.general.use_cache', true);
        $this->setInterval((int) config('plugins.edit-lock.general.interval', 90));
    }

    public function setInterval(int $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function getDuration(): float
    {
        return $this->getInterval() + max($this->getInterval() / 20, 7);
    }

    public function getMetaData(BaseModel $model): bool|array
    {
        $cacheKey = $this->cacheKey($model);
        $meta = [];
        if ($this->hasCache($cacheKey)) {
            $meta = $this->cacheValue($cacheKey);
        } else {
            $meta = $model->getMetaData('edit_lock', true);
        }

        if ($meta) {
            $time = Carbon::create(Arr::get($meta, 'time'));
            if ($time->diffInSeconds(Carbon::now()) >= $this->getDuration()) {
                return false;
            }

            return $meta;
        }

        return false;
    }

    public function updateMetaData(BaseModel $model, ?array $meta): array
    {
        $user = Auth::user();
        $isSaveMeta = false;
        if ($meta) {
            if (Arr::get($meta, 'user.id') != $user->getKey()) {
                $meta = array_merge($meta, [
                    'user' => $this->getUserData($user),
                ]);

                $isSaveMeta = true;
            }
        } else {
            $isSaveMeta = true;
            $meta = [
                'user' => $this->getUserData($user),
            ];
        }

        $meta['time'] = Carbon::now();

        $this->setCacheValue($this->cacheKey($model), $meta);

        if ($isSaveMeta) {
            MetaBox::saveMetaBoxData($model, 'edit_lock', $meta);
        } else {
            if (request()->wantsJson() && class_exists('Debugbar')) {
                \Debugbar::disable();
            }
        }

        return $meta;
    }

    public function deleteMetaData(BaseModel $model): self
    {
        $this->cache->put($this->cacheKey($model), [], -1);
        MetaBox::deleteMetaData($model, 'edit_lock');

        return $this;
    }

    public function getUserData(BaseModel $user): array
    {
        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'avatar' => $user->avatar_url,
        ];
    }

    public function cacheKey(BaseModel $data): string
    {
        return md5(get_class($data) . '_' . $data->id);
    }

    public function cacheValue(string $key): mixed
    {
        if ($this->useCache) {
            return $this->cache->get($key);
        }

        return null;
    }

    public function hasCache(string $key): bool
    {
        return $this->useCache ? $this->cache->has($key) : false;
    }

    public function setCacheValue(?string $key, mixed $data): bool
    {
        return $this->cache->put($key, $data);
    }

    public function registerModule(string $key, string $model): self
    {
        config([
            'plugins.edit-lock.general.supported' => array_merge(
                $this->supportedModules(),
                [$key => $model]
            ),
        ]);

        return $this;
    }

    public function supportedModules(): array
    {
        return (array) config('plugins.edit-lock.general.supported', []);
    }

    public function isSupportedModule(string $model): bool
    {
        return in_array($model, $this->supportedModules());
    }
}
