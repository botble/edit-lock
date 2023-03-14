<?php

namespace Botble\EditLock\Providers;

use EditLock;
use Html;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use ReflectionProperty;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Utilities\Helper;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(BASE_FILTER_GET_LIST_DATA, [$this, 'updateColumnsTable'], 153, 2);
        add_filter(BASE_FILTER_TABLE_QUERY, [$this, 'modifyQueryInTable'], 153);
    }

    public function updateColumnsTable(EloquentDataTable|CollectionDataTable $data, Model|string|null $model): EloquentDataTable|CollectionDataTable
    {
        if (is_in_admin(true) && is_object($model) && EditLock::isSupportedModule(get_class($model))) {
            $property = new ReflectionProperty($data, 'columnDef');
            $property->setAccessible(true);
            $columnDef = $property->getValue($data);

            $user = Auth::user();

            $data
                ->editColumn('checkbox', function ($item) use ($columnDef, $user) {
                    if ($metadata = EditLock::getMetaData($item)) {
                        if ($user->getKey() != Arr::get($metadata, 'user.id')) {
                            return Html::tag('i', '', ['class' => 'fa fa-lock']);
                        }
                    }
                    $checkbox = Arr::last($columnDef['edit'], fn ($value) => $value['name'] == 'checkbox');

                    return Helper::compileContent($checkbox['content'], $checkbox, $item);
                })
                ->editColumn('name', function ($item) use ($columnDef, $user) {
                    $name = Arr::last($columnDef['edit'], fn ($value) => $value['name'] == 'name');

                    $content = '';
                    if ($metadata = EditLock::getMetaData($item)) {
                        if ($user->getKey() != Arr::get($metadata, 'user.id')) {
                            $image = Html::tag('img', '', ['src' => Arr::get($metadata, 'user.avatar'), 'width' => 18, 'class' => 'me-1 rounded']);
                            $userName = Html::tag('span', trans('plugins/edit-lock::edit-lock.user_is_currently_editing', ['name' => Arr::get($metadata, 'user.name')]));
                            $content = Html::tag('div', $image . $userName, ['class' => 'small text-muted']);
                        }
                    }

                    return $content . Helper::compileContent($name['content'], $name, $item);
                });
        }

        return $data;
    }

    public function modifyQueryInTable(Builder|EloquentBuilder|null $query): Builder|EloquentBuilder|null
    {
        $model = '';
        if ($query instanceof Builder || $query instanceof EloquentBuilder) {
            $model = $query->getModel();
        }

        if ($model && EditLock::isSupportedModule(get_class($model))) {
            $query->with(['metadata']);
        }

        return $query;
    }
}
