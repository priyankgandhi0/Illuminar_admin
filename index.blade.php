@extends('layout.main')
@section('head-css-script')
    <link rel="stylesheet" href="{{ asset('public/assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('public/assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
@endsection
@section('content')
    <style>
        td.bb {
            display: flex;
            gap: 5px;
        }

        .badge {
            display: inline-block;
            min-width: 10px;
            padding: 5px 10px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            background-color: #777;
            border-radius: 10px;
        }

        .doc-image {
            width: 130px;
            height: 130px;
        }

        .video-card {
            border-radius: 12px;
            transition: all 0.3s ease-in-out;
            margin-bottom: 0px;
        }


        .video-card:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .video-title,
        .video-description,
        .video-price {
            padding-left: 10px;
            text-transform: capitalize;
        }

        .video-description {
            word-break: break-all;
        }

        .video-title {
            font-weight: 600;
        }

        .video-buttons {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: flex-start;
            padding-right: 20px;
        }

        .video-button {
            flex-direction: column;
            align-items: flex-end;
            justify-content: flex-start;
        }

        .video-button .btn {
            margin-left: 5px;
        }

        /* .watch-video-btn
                    {
                        color: #34FDFD;
                    } */

        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%;
            /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
        }

        .video-wrapper video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .review-section {
            padding-left: 10px;
        }

        .review-section div[style*="overflow-y: auto"]::-webkit-scrollbar {
            width: 6px;
        }

        .review-section div[style*="overflow-y: auto"]::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .review-section div[style*="overflow-y: auto"]::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }

        .review-section div[style*="overflow-y: auto"]::-webkit-scrollbar-thumb:hover {
            background: #999;
        }

        @media (max-width: 1200px) {
            .card-title {
                font-size: 16px;
            }

            .video-buttons {
                padding-right: 12px;
            }
        }
    </style>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                @include('flash')
                <div class="col-12">

                    <div class="card border-0">
                        <div class="card-body">

                            <div class="row">

                                @forelse($Videodata as $video)
                                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 mb-4">
                                        <div class="card shadow-sm border-0 h-100 rounded-3 video-card"
                                            style="cursor:pointer" data-video-id="{{ $video->video_id }}"
                                            data-user="{{ $video->user_id }}">

                                            <img src="{{ $video->thumbnail_url_path }}" class="card-img-top"
                                                style="height:180px;object-fit:cover;"
                                                onerror="this.src='{{ asset('public/assets/media/default.png') }}'">

                                            <div class="card-body">
                                                <h6 class="fw-semibold mb-1">
                                                    {{ $video->video_title }}
                                                </h6>

                                                <small class="text-muted d-block mb-2">
                                                    {{ $video->created_at->diffForHumans() }}
                                                </small>

                                                {{-- <button class="btn btn-sm btn-danger ban-video-btn"
                                                    data-user="{{ $video->user_id }}"
                                                    data-video-id="{{ $video->video_id }}">
                                                    Ban Video
                                                </button> --}}
                                            </div>
                                        </div>
                                    </div>

                                @empty
                                    <div class="col-12 text-center">
                                        <p>No videos available</p>
                                    </div>
                                @endforelse

                            </div>

                            <div class="d-flex justify-content-center mt-4">
                                <div class="d-flex justify-content-center mt-4">
                                    {{ $Videodata->links('pagination::bootstrap-4') }}
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="modal fade" id="videoModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body p-0">

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close">
                            Close
                        </button>
                        <button type="button" class="btn btn-primary" id="markAsBanBtn">
                            Mark as Ban &nbsp;
                            <span class="spinner-border d-none spinner-border-sm" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="bannedModal" tabindex="-1">
            <div class="modal-dialog">
                <form id="bannedForm" method="POST" action="{{ route('video.banned.store') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reason</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" class="user-id-input" name="user_id">
                            <input type="hidden" class="video-id-input" name="video_id">
                            <textarea name="reason" class="form-control" placeholder="Enter reason" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" aria-label="Close">
                                Close
                            </button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </section>
@endsection
@section('footer-script')
    <script src="{{ asset('public/assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('public/assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('public/assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('public/assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('public/assets/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('public/assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('public/assets/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('public/assets/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('public/assets/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('public/assets/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('public/assets/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('public/assets/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
    <script>
        $(function() {
            $("#user_list").DataTable({
                "responsive": true,
                "autoWidth": false,

            });

        });
    </script>
    <script>
        $(document).on('click', '.video-card', function() {

            let video_id = $(this).data('video-id');

            $.ajax({
                url: '{{ route('video.details') }}',
                method: 'GET',
                data: {
                    video_id: video_id
                },

                beforeSend: function() {
                    $('#videoModal').modal('show');
                    $('#videoModal .modal-body')
                        .html('<div class="p-5 text-center">Loading...</div>');
                },

                success: function(data) {


                    var videoData = data.video_data
                    var reviewData = data.review_data

                    const thumbnailPath = videoData.thumbnail_url_path && videoData
                        .thumbnail_url_path !== '' ?
                        videoData.thumbnail_url_path :
                        '{{ asset('public/assets/media/default.png') }}';

                    const videoFile = videoData.video_url_path && videoData.video_url_path !== '' ?
                        videoData.video_url_path :
                        '{{ asset('public/assets/media/default.png') }}';

                    const fullVideoSrc = `${videoFile}`;



                    $('#user_id').val(videoData.user_id);
                    $('#video_id').val(videoData.video_id);

                    let reviewHTML = '';

                    if (reviewData.length > 0) {
                        reviewHTML += `<div class="review-section mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold">Reviews</h5>
                        </div>
                        <div style="max-height: 500px; overflow-y: auto;">`;

                        reviewData.forEach(function(review) {
                            let starsHtml = generateStars(review.review_count);

                            let userName = review.users?.full_name || `User #${review.user_id}`;
                            let profileImage = review.users?.user_profile_photo && review.users
                                .user_profile_photo !== '' ?
                                `{{ asset('../') }}` + review.users.user_profile_photo :
                                `{{ asset('public/assets/media/avatars/blank.png') }}`;

                            reviewHTML += `
                                <div class="card mb-3 shadow-sm border-0 rounded-3">
                                    <div class="card-body p-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="d-flex align-items-center">
                                                <img src="${profileImage}" alt="User" class="rounded-circle me-2" width="45" height="45" style="object-fit: cover;margin-right: 10px;" onerror="this.onerror=null; this.src='{{ asset('public/assets/media/avatars/blank.png') }}';">
                                                <div>
                                                    <strong class="d-block">${userName}</strong>
                                                    <div>${starsHtml}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-muted mt-2 mb-0" style="margin-left: 5px;">${review.review_text}</p>
                                    </div>
                                </div>`;
                        });
                        reviewHTML += `</div></div>`;
                    } else {
                        reviewHTML =
                            `<div class="review-section mt-3"><h6>No reviews available.</h6></div>`;
                    }


                    let modalContent = `
                        <input type="hidden" name="video_id" id="video_id" value="${videoData.video_id}">
                        <div class="video-wrapper">
                            <video controls id="modalVideoPlayer" poster="${thumbnailPath}" autoplay style="width: 100%; max-height: 100%; object-fit: contain;">
                                <source src="${fullVideoSrc}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        <div class="row align-items-start mt-3">
                            <div class="col-md-6">
                                <h5 class="video-title">${videoData.video_title}</h5>
                                <p class="video-description">${videoData.description}</p>
                                <p class="text-success fw-bold video-price">Price: $${videoData.price}</p>
                                
                            </div>
                            <div class="col-md-6 video-buttons">
                                <div style="text-align: end;"><p class="fw-bold" >${videoData.view_count} Views</p></div>
                                <div class="mb-3 flex-wrap video-button">
                                    <span class="btn btn-sm btn-secondary"><i class="fas fa-thumbs-up"></i> ${videoData.likes_count}</span>
                                    <span class="btn btn-sm btn-secondary"><i class="fas fa-thumbs-down"></i> ${videoData.dislikes_count}</span>
                                    <span class="btn btn-sm btn-secondary"><i class="fas fa-comments"></i> ${videoData.comment_count}</span>
                                    <span class="btn btn-sm btn-secondary">Reviews: ${videoData.review_count}</span>
                                </div>
                            </div>
                        </div>

                    ` + reviewHTML;

                    $('#videoModal').find('.modal-body').html(modalContent);
                }
            });

        });

        function generateStars(rating) {
            let fullStars = Math.floor(rating);
            let stars = '';

            for (let i = 0; i < 5; i++) {
                if (i < fullStars) {
                    stars += '★ '; // filled star
                } else {
                    stars += '☆ '; // empty star
                }
            }

            return `<span class="" style="color:#ffbf00;font-size: 20px;">${stars.trim()}</span>`;
        }

        $(document).on('click', '.ban-video-btn', function() {
            let videoId = $(this).data('video-id');
            let userId = $(this).data('user');

            $('.video-id-input').val(videoId);
            $('.user-id-input').val(userId);

            $('#bannedModal').modal('show');
        });

        $('#videoModal').on('hidden.bs.modal', function() {
            $('#modalVideoPlayer')[0].pause();
        });
    </script>
    <script>
        // $('#markAsBanBtn').click(function() {
        //     const videoId = $('#video_id').val();
        //     $('.video-id-input').val(videoId);
        //     $('#bannedModal').modal('show');
        // });
        $('#markAsBanBtn').click(function() {

            let videoId = $('#video_id').val();
            let userId = $('#user_id').val();

            $('.video-id-input').val(videoId);
            $('.user-id-input').val(userId);

            $('#videoModal').modal('hide');
            $('#bannedModal').modal('show');
        });
        $(document).on('click', '.ban-video-btn', function(e) {
            e.stopPropagation(); // prevent card click

            let videoId = $(this).data('video-id');
            let userId = $(this).data('user');

            $('.video-id-input').val(videoId);
            $('.user-id-input').val(userId);

            $('#bannedModal').modal('show');
        });
    </script>
@endsection
