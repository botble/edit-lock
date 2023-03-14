<!-- Modal -->
<div class="modal fade" id="edit-lock-static-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="edit-lock-static-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-body">
                @if (! empty($metadata))
                    @include('plugins/edit-lock::modal-body')
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-take-over">{{ trans('plugins/edit-lock::edit-lock.take_over') }}</button>
                @php
                    $routeName = preg_replace('/.edit$/', '.index', Route::currentRouteName());
                    if (Route::has($routeName)) {
                        $route = route($routeName);
                    } else {
                        $route = route('dashboard.index');
                    }
                @endphp
                <a href="{{ $route }}" class="btn btn-primary">{{ trans('plugins/edit-lock::edit-lock.exit_editor') }}</a>
            </div>
        </div>
    </div>
</div>
