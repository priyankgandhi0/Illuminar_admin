<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Iluminar7S | {{ __('auth.admin_login') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
        crossorigin="anonymous" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Source Sans 3', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1A1208;
            position: relative;
            overflow: hidden;
        }

        /* Subtle background glow */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(ellipse at center, rgba(200, 144, 46, 0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: rgba(61, 40, 16, 0.55);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(200, 144, 46, 0.2);
            overflow: hidden;
            position: relative;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #C8902E, #D4A537, #C8902E, transparent);
            background-size: 200% 100%;
            animation: borderGlow 3s ease-in-out infinite;
            border-radius: 16px 16px 0 0;
        }

        @keyframes borderGlow {
            0%, 100% { background-position: 200% 0; }
            50% { background-position: 0% 0; }
        }

        .login-card-body {
            padding: 40px 35px 40px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-logo img {
            max-height: 70px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 10px rgba(200, 144, 46, 0.3));
            animation: moveUpDown 4s ease-in-out infinite;
        }

        @keyframes moveUpDown {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .login-subtitle {
            text-align: center;
            color: #9CA3AF;
            font-size: 15px;
            margin-bottom: 28px;
        }

        .form-label-text {
            color: #9CA3AF;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
            display: block;
        }

        .form-control {
            border: 1px solid rgba(200, 144, 46, 0.15);
            border-radius: 10px !important;
            padding: 12px 44px 12px 16px;
            font-size: 15px;
            height: 48px;
            transition: all 0.3s ease;
            background: #2A1E10 !important;
            color: #FFFFFF !important;
        }

        .form-control:focus {
            border-color: #C8902E;
            box-shadow: 0 0 0 3px rgba(200, 144, 46, 0.2), 0 0 15px rgba(200, 144, 46, 0.1);
            outline: none;
            background: #352815 !important;
        }

        .form-control::placeholder {
            color: #8A7560;
        }

        /* Fix browser autofill dark theme */
        .form-control:-webkit-autofill,
        .form-control:-webkit-autofill:hover,
        .form-control:-webkit-autofill:focus {
            -webkit-text-fill-color: #FFFFFF !important;
            -webkit-box-shadow: 0 0 0 1000px #2A1E10 inset !important;
            box-shadow: 0 0 0 1000px #2A1E10 inset !important;
            border-color: rgba(200, 144, 46, 0.15) !important;
            caret-color: #FFFFFF !important;
        }

        .form-control.is-invalid {
            border-color: #ef4444;
            background-image: none;
        }

        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #6B7280;
            z-index: 5;
            pointer-events: none;
            font-size: 16px;
        }

        .btn-login {
            background: linear-gradient(135deg, #C8902E, #D4A537);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(200, 144, 46, 0.3);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #B07D28, #C8902E);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(200, 144, 46, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6B7280;
            cursor: pointer;
            z-index: 5;
            padding: 0;
            font-size: 16px;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: #C8902E;
        }

        .text-danger {
            color: #ef4444 !important;
        }

        @media (max-width: 480px) {
            .login-card-body {
                padding: 30px 20px 34px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-card-body">
                <div class="login-logo">
                    <img src="{{ asset('assets/images/tra-login-logo.png') }}" alt="Iluminar7S">
                </div>
                {{-- <p class="login-subtitle">Sign in to your Account</p> --}}
                <form action="{{ route('login.post') }}" method="POST" novalidate>
                    @csrf
                    <div class="mb-3">
                        <span class="form-label-text">{{ __('auth.email') }}</span>
                        <div class="input-group">
                            <input type="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="{{ __('auth.enter_email') }}"
                                value="{{ old('email') }}" required />
                            <span class="input-icon"><i class="bi bi-envelope"></i></span>
                        </div>
                        @error('email')
                            <div class="text-danger mt-1" style="font-size: 13px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <span class="form-label-text">{{ __('auth.password') }}</span>
                        <div class="input-group">
                            <input type="password" name="password" id="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="{{ __('auth.enter_password') }}" required />
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="bi bi-lock" id="toggleIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="text-danger mt-1" style="font-size: 13px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-login">
                        {{ __('auth.sign_in') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "4000",
        };

        @if (session('success'))
            toastr.success("{{ session('success') }}");
        @endif

        @if (session('error'))
            toastr.error("{{ session('error') }}");
        @endif

        @if (session('warning'))
            toastr.warning("{{ session('warning') }}");
        @endif

        @if (session('info'))
            toastr.info("{{ session('info') }}");
        @endif

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-lock');
                toggleIcon.classList.add('bi-unlock');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-unlock');
                toggleIcon.classList.add('bi-lock');
            }
        }
    </script>
</body>

</html>
