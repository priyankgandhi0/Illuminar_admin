<aside class="app-sidebar shadow">
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <img src="{{ asset('assets/images/tra-login-logo.png') }}" alt="Iluminar7S" class="brand-logo-full">
            <img src="{{ asset('assets/images/sidebar.png') }}" alt="Iluminar7S" class="brand-logo-mini">
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation"
                aria-label="Main navigation" data-accordion="false" id="navigation">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                        class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>{{ __('common.dashboard') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('users.index') }}"
                        class="nav-link {{ request()->routeIs('users.index') || request()->routeIs('users.show') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-people"></i>
                        <p>{{ __('common.users') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('daily-lights.index') }}"
                        class="nav-link {{ request()->routeIs('daily-lights.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-brightness-high"></i>
                        <p>{{ __('common.daily_lights') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('daily-light-categories.index') }}"
                        class="nav-link {{ request()->routeIs('daily-light-categories.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-collection"></i>
                        <p>{{ __('common.daily_light_categories') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('jornada-categories.index') }}"
                        class="nav-link {{ request()->routeIs('jornada-categories.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-tag"></i>
                        <p>{{ __('common.jornadas_categories') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('jornadas.index') }}"
                        class="nav-link {{ request()->routeIs('jornadas.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-journal-text"></i>
                        <p>{{ __('common.jornadas') }}</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <div class="sidebar-logout">
        <a href="#" class="nav-link logout-btn">
            <i class="bi bi-box-arrow-right"></i>
            <span class="logout-text">{{ __('common.logout') }}</span>
        </a>
    </div>
</aside>
