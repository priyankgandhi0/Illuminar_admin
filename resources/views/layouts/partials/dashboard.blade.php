@extends('layouts.app')
@section('title', __('common.dashboard'))
@section('style')
<style>
    .dash-card {
        border-radius: 12px;
        padding: 24px;
        color: #fff;
        position: relative;
        overflow: hidden;
        min-height: 140px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .dash-card .dash-icon {
        position: absolute;
        right: 16px;
        top: 16px;
        font-size: 48px;
        opacity: 0.2;
    }
    .dash-card h3 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .dash-card p {
        font-size: 18px;
        margin-bottom: 0;
        opacity: 0.9;
    }
    .dash-card .dash-footer {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: rgba(255,255,255,0.8);
        font-size: 13px;
        text-decoration: none;
        margin-top: 12px;
        transition: color 0.2s;
    }
    .dash-card .dash-footer:hover {
        color: #fff;
    }
    .dash-card-users { background: linear-gradient(135deg, #C8902E, #9A6D22); }
</style>
@endsection
@section('content')
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0">{{ __('common.dashboard') }}</h3>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="#">{{ __('common.home') }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ __('common.dashboard') }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-3 col-6 mb-3">
                        <div class="dash-card dash-card-users">
                            <i class="bi bi-people-fill dash-icon"></i>
                            <div>
                                <h3>{{ $userCount }}</h3>
                                <p>{{ __('common.users') }}</p>
                            </div>
                            <a href="{{ route('users.index') }}" class="dash-footer">
                                {{ __('common.more_info') }} <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </div>
                    </div>
                    {{-- <div class="col-lg-3 col-6">
                        <div class="small-box text-bg-success">
                            <div class="inner">
                                <h3>{{ \App\Models\Occasion::totalCount() }}</h3>
                                <p>Occasions</p>
                            </div>
                            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M7 2a1 1 0 011 1v1h8V3a1 1 0 112 0v1h1a3 3 0 013 3v11a3 3 0 01-3 3H5a3 3 0 01-3-3V7a3 3 0 013-3h1V3a1 1 0 011-1zM4 10h16v8a1 1 0 01-1 1H5a1 1 0 01-1-1v-8z"></path>

                            </svg>
                            <a href="{{ route('occasions.index')}}"
                                class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                                More info <i class="bi bi-link-45deg"></i>
                            </a>
                        </div>
                    </div> --}}
                    {{-- <div class="col-lg-3 col-6">
                        <div class="small-box text-bg-warning">
                            <div class="inner">
                                <h3>{{ \App\Models\Announcement::totalCount() }}</h3>
                                <p>Announcement</p>
                            </div>
                            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                               <path d="M3 10v4a2 2 0 002 2h1l1 4a1 1 0 001 .7h1a1 1 0 001-1.2L9.5 16H13l7 3V5l-7 3H5a2 2 0 00-2 2z"></path>

                            </svg>
                            <a href="{{ route('announcements.index')}}"
                                class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                                More info <i class="bi bi-link-45deg"></i>
                            </a>
                        </div>
                    </div> --}}
                    {{-- <div class="col-lg-3 col-6">
                        <div class="small-box text-bg-danger">
                            <div class="inner">
                                <h3>{{ \App\Models\ReportOccasion::totalCount() }}</h3>
                                <p>Reported Occasions</p>
                            </div>
                            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M7 2a1 1 0 011 1v1h8V3a1 1 0 112 0v1h1a3 3 0 013 3v11a3 3 0 01-3 3H5a3 3 0 01-3-3V7a3 3 0 013-3h1V3a1 1 0 011-1zM4 10h16v8a1 1 0 01-1 1H5a1 1 0 01-1-1v-8z"></path>

                            </svg>
                            <a href="{{ route('reported-occasions.index') }}"
                                class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                                More info <i class="bi bi-link-45deg"></i>
                            </a>
                        </div>
                    </div> --}}
                    {{-- <div class="col-lg-3 col-6">
                        <div class="small-box text-bg-danger">
                            <div class="inner">
                                <h3>{{ \App\Models\ReportAnnouncement::totalCount() }}</h3>
                                <p>Reported Announcements</p>
                            </div>
                             <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                               <path d="M3 10v4a2 2 0 002 2h1l1 4a1 1 0 001 .7h1a1 1 0 001-1.2L9.5 16H13l7 3V5l-7 3H5a2 2 0 00-2 2z"></path>

                            </svg>
                            <a href="{{ route('reported-announcements.index') }}"
                                class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                                More info <i class="bi bi-link-45deg"></i>
                            </a>
                        </div>
                    </div> --}}
                </div>
            </div>
        </div>
    </main>
@endsection
