<nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">

        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="bi bi-list" style="font-size: 22px;"></i>
                </a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item">
                <a class="nav-link" href="#" role="button" id="fullscreen-toggle" title="{{ __('common.fullscreen') }}">
                    <i class="bi bi-arrows-fullscreen" id="fullscreen-icon" style="font-size: 16px;"></i>
                </a>
            </li>
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="bi bi-person-circle me-2" style="font-size: 22px;"></i>
                    <span class="d-none d-md-inline" style="font-size: 15px; font-weight: 500;">
                        {{ session('admin.name', 'Admin') }}
                    </span>
                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow user-dropdown">
                    <li class="px-3 py-2">
                        <div class="d-flex align-items-center">
                            <div style="width:42px;height:42px;background:linear-gradient(135deg,#C8902E,#9A6D22);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-right:12px;flex-shrink:0;">
                                <i class="bi bi-person-fill text-white" style="font-size:18px;"></i>
                            </div>
                            <div>
                                <div class="fw-semibold" style="color:#1f2937; font-size:16px;">{{ session('admin.name', 'Admin') }}</div>
                                <small style="font-size:14px; color:#6b7280;">{{ session('admin.email', '') }}</small>
                            </div>
                        </div>
                    </li>

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center logout-btn" href="#"
                            style="font-size:16px;">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            {{ __('common.logout') }}
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
