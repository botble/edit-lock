<h5>{{ trans('plugins/edit-lock::edit-lock.this_page_is_already_being_edited') }}</h5>
<div class="row mt-3">
    <div class="col-2">
        <img src="{{ Arr::get($metadata, 'user.avatar') }}" alt="{{ Arr::get($metadata, 'user.name') }}" class="img-fluid rounded">
    </div>
    <div class="col">
        <p>{!! BaseHelper::clean(trans('plugins/edit-lock::edit-lock.user_is_currenyly_working_on_this_page', ['name' => Arr::get($metadata, 'user.name')])) !!}</p>
        <p>{{ trans('plugins/edit-lock::edit-lock.if_you_take_over') }}</p>
    </div>
</div>
