@extends('layouts.app')
@section('title', __('common.comments'))

@section('vendorStyles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ __('common.comments') }}</h3></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('common.comments') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('common.daily_lights') }}</strong>
                </div>
                <div class="card-body">
                    <div class="jn-table-wrap">
                        <table class="table table-bordered" id="commentsIndexTable">
                            <thead>
                                <tr>
                                    <th width="50">{{ __('common.hash') }}</th>
                                    <th width="130">{{ __('common.date') }}</th>
                                    <th>{{ __('common.title') }}</th>
                                    <th width="110">{{ __('common.status') }}</th>
                                    <th width="100">{{ __('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dailyLights as $item)
                                <tr>
                                    <td></td>
                                    <td style="white-space:nowrap;font-size:13px;">{{ $item['date'] }}</td>
                                    <td>{{ $item['title'] ?: '—' }}</td>
                                    <td>
                                        @if($item['status'] === 'published')
                                            <span class="dl-status-badge dl-status-published">{{ __('common.published') }}</span>
                                        @elseif($item['status'] === 'scheduled')
                                            <span class="dl-status-badge dl-status-scheduled">{{ __('common.scheduled') }}</span>
                                        @else
                                            <span class="dl-status-badge dl-status-draft">{{ __('common.draft') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dl-action-wrap">
                                            <a href="{{ route('daily-lights.comments', $item['id']) }}"
                                               class="dl-action-box dl-action-comments"
                                               title="{{ __('common.comments') }}">
                                                <i class="bi bi-chat-dots"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    var table = $('#commentsIndexTable').DataTable({
        order: [[1, 'desc']],
        columnDefs: [{ orderable: false, targets: [0, 4] }],
        language: {
            search: Lang.dt_search,
            emptyTable: Lang.dt_empty,
            zeroRecords: Lang.dt_zero_records,
            lengthMenu: Lang.dt_length_menu,
            info: Lang.dt_info,
            infoEmpty: Lang.dt_info_empty,
            infoFiltered: Lang.dt_info_filtered,
            paginate: { previous: Lang.dt_paginate_previous, next: Lang.dt_paginate_next }
        },
        drawCallback: function() {
            var api = this.api();
            var start = api.page.info().start;
            api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                cell.innerHTML = start + i + 1;
            });
        }
    });
});
</script>
@endsection
