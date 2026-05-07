@extends('layouts.app')
@section('title', __('jornadas.create_jornada'))

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ __('jornadas.create_jornada') }}</h3></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('jornadas.index') }}">{{ __('common.jornadas') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('common.create') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form action="{{ route('jornadas.store') }}" method="POST" enctype="multipart/form-data" id="jornadaForm">
                @csrf
                @include('pages.jornadas._form')
            </form>
        </div>
    </div>
</main>

<div class="dl-overlay-loader" id="dlOverlayLoader">
    <div class="loader-spinner"></div>
    <div class="loader-text" id="dlLoaderText">{{ __('jornadas.saving') }}</div>
</div>
@endsection

@section('scripts')
@include('pages.jornadas._scripts')
@if(empty($categories) || count($categories) === 0)
<script>
    $(document).ready(function() {
        Swal.fire({
            title: Lang.no_categories_found,
            text: Lang.create_category_first,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c6a55a',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.create_category,
            cancelButtonText: Lang.ok
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = '{{ route("jornada-categories.index") }}';
            }
        });
    });
</script>
@endif
@endsection
