<div class="modal fade" id="addUserModal">

<div class="modal-dialog">

<div class="modal-content">

<form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">

@csrf

<div class="modal-header">
<h5>{{ __('users.add_user') }}</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<div class="mb-3">
<label>{{ __('users.name') }}</label>
<input type="text" name="userName" class="form-control">
</div>

<div class="mb-3">
<label>{{ __('users.user_image') }}</label>
<input type="file" name="image" class="form-control">
</div>

</div>

<div class="modal-footer">

<button class="btn btn-secondary" data-bs-dismiss="modal">
{{ __('common.cancel') }}
</button>

<button class="btn btn-primary">
{{ __('common.save') }}
</button>

</div>

</form>

</div>

</div>

</div>
