<nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
        <a class="navbar-brand brand-logo" href="/"><img src="{{ asset('images/logo.svg') }}" class="mr-2"
                alt="logo" /></a>
        <a class="navbar-brand brand-logo-mini" href="/"><img src="{{ asset('images/logo-mini.svg') }}"
                alt="logo" /></a>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="icon-menu"></span>
        </button>

        <ul class="navbar-nav navbar-nav-right">
            <li class="nav-item dropdown">
                <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#"
                    data-toggle="dropdown">
                    <i class="icon-bell mx-0"></i>
                    @if ($navbarNotificationCount > 0)
                        <span class="count"></span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list"
                    aria-labelledby="notificationDropdown">
                    <div class="d-flex justify-content-between align-items-center dropdown-header">
                        <span class="mb-0 font-weight-normal">Pemberitahuan</span>
                        <span class="badge badge-danger">{{ $navbarNotificationCount }}</span>
                    </div>

                    @forelse ($navbarNotifications as $notification)
                        <a class="dropdown-item preview-item" href="{{ route('pemberitahuan.index') }}">
                            <div class="preview-thumbnail">
                                <div class="preview-icon bg-{{ $notification->severity }}">
                                    <i class="ti-alert mx-0"></i>
                                </div>
                            </div>
                            <div class="preview-item-content">
                                <h6 class="preview-subject font-weight-normal">{{ $notification->title }}</h6>
                                <p class="font-weight-light small-text mb-0 text-muted">
                                    {{ $notification->item_name }} — Stok
                                    {{ number_format($notification->current_stock, 2, ',', '.') }}
                                    {{ $notification->satuan }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <div class="dropdown-item text-center text-muted py-3">
                            Tidak ada pemberitahuan stok.
                        </div>
                    @endforelse

                    <a class="dropdown-item text-center font-weight-medium py-3 border-top"
                        href="{{ route('pemberitahuan.index') }}">
                        Lihat Semua Pemberitahuan
                    </a>
                </div>
            </li>
            <li class="nav-item nav-profile dropdown">
                <a class="nav-link dropdown-toggle" href="/profile" data-toggle="dropdown" id="profileDropdown">
                    <img src="{{ asset('images/man.png') }}" alt="profile" />
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">

                    <div class="col-12">
                        <div class="border-bottom text-center pb-4">
                            <img src="{{ asset('images/man.png') }}" alt="Foto Profil"
                                class="img-sm rounded-circle mt-3 d-block mx-auto" />
                            <div class="align-items-center">
                                <h5>{{ Auth::user()->display_name ?? 'Name' }}</h5>

                                <div class="align-items-center justify-content-center">
                                    <h6 class="mb-0 me-2 text-muted">{{ Auth::user()->email ?? 'Email' }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a class="dropdown-item" href="/profile">
                        <i class="ti-user text-primary"></i>
                        Profil
                    </a>
                    {{-- <a class="dropdown-item">
                      <i class="ti-settings text-primary"></i>
                      Pengaturan
                    </a> --}}
                    <a class="dropdown-item" id="logoutButton">
                        <i class="ti-power-off text-primary"></i>
                        Logout
                    </a>
                </div>
            </li>
            <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
                data-toggle="offcanvas">
                <span class="icon-menu"></span>
            </button>
        </ul>

    </div>
</nav>
