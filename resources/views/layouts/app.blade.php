<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>@yield('title', 'Dashboard') | {{ config('app.name', 'TruePath') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    <link rel="preload" href="{{ asset('assets/css/adminlte.css') }}" as="style" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
        integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
        crossorigin="anonymous" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="{{ asset('assets/css/adminlte.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}?v={{ filemtime(public_path('assets/css/custom.css')) }}" />

    @yield('vendorStyles')
    @yield('style')
</head>

<body class="layout-fixed sidebar-mini sidebar-expand-lg bg-body-tertiary">
    <div class="page-loader" id="pageLoader">
        <div class="loader-spinner"></div>
    </div>
    <div class="app-wrapper">
        @include('layouts.partials.header')
        @include('layouts.partials.aside')
        @yield('content')
        @include('layouts.partials.footer')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('assets/js/adminlte.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    @yield('vendorScripts')
    @yield('scripts')

    <script>
        window.Lang = @json(__('js'));
    </script>

    <script>
        // Set global SweetAlert defaults for translated cancel button
        const _swalFire = Swal.fire.bind(Swal);
        Swal.fire = function(options) {
            if (typeof options === 'object' && options.showCancelButton && !options.cancelButtonText) {
                options.cancelButtonText = Lang.cancel;
            }
            return _swalFire(options);
        };
    </script>

    <script>
        // Hide page loader once all resources are fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            $('#pageLoader').addClass('hidden');
        });

        // Show page loader on any navigation (link click, form submit)
        $(document).on('click', 'a[href]:not([href^="#"]):not([href^="javascript"]):not([target="_blank"]):not(.logout-btn)', function() {
            $('#pageLoader').removeClass('hidden');
        });

        // Logout confirmation
        $(document).on('click', '.logout-btn', function(e) {
            e.preventDefault();
            Swal.fire({
                title: Lang.logout,
                text: Lang.confirm_logout,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#C8902E',
                cancelButtonColor: '#6b7280',
                confirmButtonText: Lang.yes_logout
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#pageLoader').removeClass('hidden');
                    $('#logout-form').submit();
                }
            });
        });
    </script>

    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "4000",
        };

        @if (session('success'))
            toastr.success({!! json_encode(session('success')) !!});
        @endif

        @if (session('error'))
            toastr.error({!! json_encode(session('error')) !!});
        @endif

        @if (session('warning'))
            toastr.warning({!! json_encode(session('warning')) !!});
        @endif

        @if (session('info'))
            toastr.info({!! json_encode(session('info')) !!});
        @endif
    </script>

    <script>
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-dark',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true,
        };
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
            if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined) {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: Default.scrollbarTheme,
                        autoHide: Default.scrollbarAutoHide,
                        clickScroll: Default.scrollbarClickScroll,
                    },
                });
            }
        });
    </script>

    <script>
        // Fullscreen Toggle (no jQuery dependency)
        (function() {
            function isFullscreen() {
                return !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);
            }

            function enterFullscreen() {
                var el = document.documentElement;
                if (el.requestFullscreen) return el.requestFullscreen();
                if (el.webkitRequestFullscreen) return el.webkitRequestFullscreen();
                if (el.msRequestFullscreen) return el.msRequestFullscreen();
            }

            function exitFullscreen() {
                if (document.exitFullscreen) return document.exitFullscreen();
                if (document.webkitExitFullscreen) return document.webkitExitFullscreen();
                if (document.msExitFullscreen) return document.msExitFullscreen();
            }

            function updateIcon() {
                var icon = document.getElementById('fullscreen-icon');
                if (!icon) return;
                if (isFullscreen()) {
                    icon.className = 'bi bi-fullscreen-exit';
                } else {
                    icon.className = 'bi bi-arrows-fullscreen';
                }
            }

            var fsBtn = document.getElementById('fullscreen-toggle');
            if (fsBtn) {
                fsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    try {
                        if (!isFullscreen()) {
                            var p = enterFullscreen();
                            if (p && p.then) p.then(updateIcon).catch(function() {});
                        } else {
                            var p = exitFullscreen();
                            if (p && p.then) p.then(updateIcon).catch(function() {});
                        }
                    } catch(err) {}
                    updateIcon();
                });
            }

            ['fullscreenchange', 'webkitfullscreenchange', 'msfullscreenchange'].forEach(function(evt) {
                document.addEventListener(evt, updateIcon);
            });
        })();
    </script>

    <script>
        // Reset sidebar state when resizing back to desktop
        $(window).on('resize', function() {
            if ($(window).width() >= 992) {
                $('body').removeClass('sidebar-collapse sidebar-open sidebar-is-hovering');
                $('.sidebar-overlay').remove();
            }
        });
    </script>

</body>

</html>
