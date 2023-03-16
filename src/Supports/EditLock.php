<?php

namespace Botble\EditLock\Supports;

use Botble\Base\Models\BaseModel;
use Botble\Support\Services\Cache\Cache;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use MetaBox;
use ReflectionProperty;

class EditLock
{
    protected Cache $cache;

    protected bool $useCache = true;

    protected int $interval;

    protected ?BaseModel $user;

    protected bool $userLoaded = false;

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

    public function updateMetaData(BaseModel $model, ?array $metadata): array
    {
        $user = $this->user();
        $isSaveMeta = false;
        if ($metadata) {
            if ($this->isNotUser($metadata, $user)) {
                $metadata = array_merge($metadata, [
                    'user' => $this->getUserData($user),
                ]);

                $isSaveMeta = true;
            }
        } else {
            $isSaveMeta = true;
            $metadata = [
                'user' => $this->getUserData($user),
            ];
        }

        $metadata['time'] = Carbon::now();

        $this->setCacheValue($this->cacheKey($model), $metadata);

        if ($isSaveMeta) {
            MetaBox::saveMetaBoxData($model, 'edit_lock', $metadata);
        } else {
            if (request()->wantsJson() && class_exists('Debugbar')) {
                \Debugbar::disable();
            }
        }

        return $metadata;
    }

    public function deleteMetaData(BaseModel $model): self
    {
        $this->cache->put($this->cacheKey($model), [], -1);
        MetaBox::deleteMetaData($model, 'edit_lock');

        return $this;
    }

    public function getUserData(?BaseModel $user): array
    {
        if (! $user) {
            return [];
        }

        return [
            'id' => $user->getKey(),
            'type' => get_class($user),
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

    public function user(): ?BaseModel
    {
        if (! $this->userLoaded) {
            $this->userLoaded = true;
            $authManager = app(AuthManager::class);
            $guardProperty = new ReflectionProperty($authManager, 'guards');
            $guardProperty->setAccessible(true);
            $guards = $guardProperty->getValue($authManager);

            if (is_array($guards) && count($guards)) {
                $guard = Arr::first(array_keys($guards));
                $user = Auth::guard($guard)->check() ? Auth::guard($guard)->user() : null;
                if ($user instanceof BaseModel) {
                    $this->user = $user;
                }
            }
        }

        return $this->user;
    }

    public function isNotUser(array $metadata, ?BaseModel $user): bool
    {
        if (! $user) {
            return false;
        }
        return get_class($user) != Arr::get($metadata, 'user.type') || $user->getKey() != Arr::get($metadata, 'user.id');
    }
}
