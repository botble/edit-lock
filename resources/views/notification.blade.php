<div class="note note-warning edit-lock-notification @if ($name) el-is-currently-editing @endif" @if (! $name) style="display: none" @endif
    data-el-interval="{{ EditLock::getInterval() }}">
    @if ($name) <p>{!! trans('plugins/edit-lock::edit-lock.user_is_currently_editing', ['name' => $name]) !!}</p> @endif
</div>
