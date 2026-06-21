{{-- ===== USER PROFILE MODAL ===== --}}
@auth
<div id="user-profile-modal" class="user-profile-modal-wrapper">
    <!-- Overlay -->
    <div id="user-profile-overlay" class="user-profile-overlay"></div>

    <!-- Modal Box -->
    <div class="user-profile-modal-box">
        <!-- Close Button -->
        <button id="close-user-profile" class="user-profile-close-btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <div class="user-profile-content">
            <!-- Header: Avatar + Info -->
            <div class="user-profile-header">
                <div class="user-profile-avatar-wrapper">
                    <div class="user-profile-avatar-border">
                        @if(Auth::user()->avatar)
                            <img src="{{ asset('images/avatars/' . Auth::user()->avatar) }}" alt="Avatar" class="user-profile-avatar-img">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=10b981&color=fff&size=128" alt="Avatar" class="user-profile-avatar-img">
                        @endif
                    </div>
                    <div class="user-profile-verified-badge">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="user-profile-name">{{ Auth::user()->name }}</h3>
                    <div class="user-profile-membership">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                        </svg>
                        <span>Thành viên @switch(Auth::user()->membership_level ?? 'new') @case('silver') Bạc @break @case('gold') Vàng @break @case('diamond') Kim cương @break @default Mới @endswitch</span>
                    </div>
                </div>
            </div>

            <!-- Menu List -->
            <div class="user-profile-menu">
                <!-- Item 1 -->
                <a href="{{ route('profile') }}" class="user-profile-menu-item">
                    <div class="user-profile-menu-item-content">
                        <div class="user-profile-menu-item-icon">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
                        </div>
                        <span class="user-profile-menu-item-text">Thông tin tài khoản</span>
                    </div>
                    <svg width="20" height="20" fill="none" stroke="#9ca3af" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
                
                <!-- Item 2 -->
                <a href="{{ route('orders') }}" class="user-profile-menu-item">
                    <div class="user-profile-menu-item-content">
                        <div class="user-profile-menu-item-icon">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <span class="user-profile-menu-item-text">Đơn hàng của tôi</span>
                    </div>
                    <svg width="20" height="20" fill="none" stroke="#9ca3af" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>

                <!-- Item 3 -->
                

                <!-- Item 4 -->
                
            </div>

            <!-- Logout Button -->
            <div class="user-profile-logout-wrapper">
                <a href="{{ route('logout') }}" class="user-profile-logout-btn">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    Đăng xuất
                </a>
            </div>

            <!-- Footer -->
            <div class="user-profile-footer">
                Phiên bản 2.4.0 (2026) • Happy Drink
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('js/frontend/user-profile-modal.js') }}"></script>
@endpush
@endauth
