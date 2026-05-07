@extends('layouts.app')
@section('title', __('jornadas.edit_jornada'))

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ __('jornadas.edit_jornada') }}</h3></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('jornadas.index') }}">{{ __('common.jornadas') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('common.edit') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form action="{{ route('jornadas.update', $jornada['id']) }}" method="POST" enctype="multipart/form-data" id="jornadaForm">
                @csrf
                @method('PUT')
                @include('pages.jornadas._form')
            </form>
        </div>
    </div>
</main>

<div class="dl-overlay-loader" id="dlOverlayLoader">
    <div class="loader-spinner"></div>
    <div class="loader-text" id="dlLoaderText">{{ __('jornadas.updating') }}</div>
</div>
@endsection

@section('scripts')
@include('pages.jornadas._scripts')
@endsection
