@extends('layouts.app')
@section('title', __('users.user_details'))

@section('style')
<style>
    .profile-header {
        background: linear-gradient(135deg, #C8902E 0%, #9A6D22 100%);
        border-radius: 12px;
        padding: 32px;
        color: #fff;
        margin-bottom: 24px;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #D4A537 0%, #C8902E 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 38px;
        font-weight: 700;
        color: #ffffff;
        border: 3px solid rgba(255, 255, 255, 0.3);
    }

    .profile-name {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .profile-email {
        font-size: 15px;
        opacity: 0.85;
    }

    .info-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .info-card .card-header {
        background: #C8902E !important;
        color: #ffffff !important;
        border-bottom: none !important;
        font-weight: 600;
        font-size: 16px;
        padding: 14px 20px;
        border-radius: 0 !important;
    }

    .info-card .card-header i {
        margin-right: 8px;
        color: #ffffff;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 13px 20px;
        border-bottom: 1px solid #e9ecef;
    }

    .info-item:nth-child(even) {
        background: #f8fafc;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #334155;
        font-size: 16px;
        min-width: 160px;
        flex-shrink: 0;
    }

    .info-value {
        color: #1f2937;
        font-weight: 500;
        font-size: 16px;
        text-align: right;
        word-break: break-all;
    }

    .status-badge {
        padding: 5px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">{{ __('users.user_details') }}</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('users.index') }}">{{ __('common.users') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('users.details') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            {{-- Profile Header --}}
            <div class="profile-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{ $user->user_profile_photo ? '/app_images/profile_images/' . $user->user_profile_photo : asset('assets/images/blank.png') }}"
                            class="profile-avatar" style="object-fit:cover;">
                        <div>
                            <div class="profile-name">{{ $user->user_name ?? '-' }}</div>
                            <div class="profile-email">{{ $user->email ?? '-' }}</div>
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                @if($user->is_active)
                                    <span class="status-badge bg-success">{{ __('common.active') }}</span>
                                @else
                                    <span class="status-badge bg-danger">{{ __('common.inactive') }}</span>
                                @endif

                                {{-- @if($user->verify_email == 1)
                                    <span class="status-badge bg-info">Email Verified</span>
                                @else
                                    <span class="status-badge bg-warning text-dark">Email Not Verified</span>
                                @endif --}}
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('users.index') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left"></i> {{ __('users.back_to_users') }}
                    </a>
                </div>
            </div>

            <div class="row">
                {{-- Personal Information --}}
                <div class="col-lg-6">
                    <div class="card info-card">
                        <div class="card-header">
                            <i class="bi bi-person"></i> {{ __('users.personal_info') }}
                        </div>
                        <div class="card-body p-0">
                            <div class="info-item">
                                <span class="info-label">{{ __('users.name') }}</span>
                                <span class="info-value">{{ $user->user_name ?? '-' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">{{ __('users.email') }}</span>
                                <span class="info-value">{{ $user->email ?? '-' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">{{ __('users.phone') }}</span>
                                <span class="info-value">{{ $user->phone ?? '-' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">{{ __('users.date_of_birth') }}</span>
                                <span class="info-value">{{ $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->format('d M Y') : '-' }}</span>
                            </div>
                            {{-- <div class="info-item">
                                <span class="info-label">{{ __('users.login_type') }}</span>
                                <span class="info-value">{{ ucfirst($user->login_type ?? '-') }}</span>
                            </div> --}}
                            {{-- <div class="info-item">
                                <span class="info-label">Profile Photo</span>
                                <span class="info-value">
                                    @if($user->user_profile_photo)
                                        <img src="{{ asset('storage/profile_images/' . $user->user_profile_photo) }}" style="width:60px;height:60px;border-radius:8px;object-fit:cover;">
                                    @else
                                        -
                                    @endif
                                </span>
                            </div> --}}
                        </div>
                    </div>
                </div>

                {{-- Education Information --}}
                <div class="col-lg-6">
                    <div class="card info-card">
                        <div class="card-header">
                            <i class="bi bi-mortarboard"></i> {{ __('users.education_info') }}
                        </div>
                        <div class="card-body p-0">
                            <div class="info-item">
                                <span class="info-label">{{ __('users.education_level') }}</span>
                                <span class="info-value">{{ $user->education_level ?? '-' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">{{ __('users.current_status') }}</span>
                                <span class="info-value">{{ $user->current_status ?? '-' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">{{ __('users.major_field_of_study') }}</span>
                                <span class="info-value">{{ $user->current_major_field_of_study ?? '-' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">{{ __('users.expected_graduation') }}</span>
                                <span class="info-value">{{ $user->expected_graduation_year ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Location Information --}}
                <div class="col-lg-6">
                    <div class="card info-card">
                        <div class="card-header">
                            <i class="bi bi-geo-alt"></i> {{ __('users.location') }}
                        </div>
                        <div class="card-body p-0">
                            <div class="info-item">
                                <span class="info-label">{{ __('users.state_location') }}</span>
                                <span class="info-value">{{ $user->state_location ?? '-' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">{{ __('users.latitude') }}</span>
                                <span class="info-value">{{ $user->latitude ?? '-' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">{{ __('users.longitude') }}</span>
                                <span class="info-value">{{ $user->longitude ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Account Information --}}
                {{-- <div class="col-lg-6">
                    <div class="card info-card">
                        <div class="card-header">
                            <i class="bi bi-shield-check"></i> Account Information
                        </div>
                        <div class="card-body p-0">
                            <div class="info-item">
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    @if($user->is_active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-danger">Inactive</span>
                                    @endif
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email Verified</span>
                                <span class="info-value">
                                    @if($user->verify_email)
                                        <span class="badge text-bg-success">Yes</span>
                                    @else
                                        <span class="badge text-bg-warning">No</span>
                                    @endif
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone Verified</span>
                                <span class="info-value">
                                    @if($user->verify_phone)
                                        <span class="badge text-bg-success">Yes</span>
                                    @else
                                        <span class="badge text-bg-warning">No</span>
                                    @endif
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Logged Out</span>
                                <span class="info-value">{{ $user->is_logged_out ? 'Yes' : 'No' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Badge</span>
                                <span class="info-value">{{ $user->badge ?? '-' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Device Type</span>
                                <span class="info-value">{{ ucfirst($user->device_type ?? '-') }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Created Date</span>
                                <span class="info-value">
                                    {{ $user->created_date ? \Carbon\Carbon::parse($user->created_date)->format('d M Y, h:i A') : '-' }}
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Updated Date</span>
                                <span class="info-value">
                                    {{ $user->updated_date ? \Carbon\Carbon::parse($user->updated_date)->format('d M Y, h:i A') : '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div> --}}
            </div>

        </div>
    </div>
</main>
@endsection
