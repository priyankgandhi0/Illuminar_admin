@extends('layouts.app')
@section('title', __('daily_lights.edit_daily_light'))

@section('vendorStyles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ __('daily_lights.edit_daily_light') }}</h3></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('daily-lights.index') }}">{{ __('common.daily_lights') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('common.edit') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form action="{{ route('daily-lights.update', $dailyLight['id']) }}" method="POST" enctype="multipart/form-data" id="dailyLightForm">
                @csrf
                @method('PUT')
                @include('pages.daily-lights._form')
            </form>
        </div>
    </div>
</main>

<div class="dl-overlay-loader" id="dlOverlayLoader">
    <div class="loader-spinner"></div>
    <div class="loader-text" id="dlLoaderText">{{ __('daily_lights.updating') }}</div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@include('pages.daily-lights._scripts')
@endsection
