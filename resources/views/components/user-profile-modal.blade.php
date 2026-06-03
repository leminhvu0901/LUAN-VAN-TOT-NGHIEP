{{-- ===== USER PROFILE MODAL ===== --}}
@auth
<div id="user-profile-modal" style="display: none; position: fixed; inset: 0; z-index: 99999; font-family: 'Inter', system-ui, sans-serif;">
    <!-- Overlay -->
    <div id="user-profile-overlay" style="position: absolute; inset: 0; background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"></div>

    <!-- Modal Box -->
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; width: 90%; max-width: 400px; border-radius: 20px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden;">
        <!-- Close Button -->
        <button id="close-user-profile" style="position: absolute; top: 16px; right: 16px; color: #4b5563; background: none; border: none; cursor: pointer; padding: 4px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <div style="padding: 32px 24px 24px;">
            <!-- Header: Avatar + Info -->
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #e5e7eb;">
                <div style="position: relative;">
                    <div style="width: 72px; height: 72px; border-radius: 50%; border: 3px solid #10b981; padding: 2px; box-sizing: border-box;">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=10b981&color=fff&size=128" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    </div>
                    <div style="position: absolute; bottom: 0; right: 0; background: #10b981; border: 2px solid white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; color: white;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 style="font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0 0 4px 0;">{{ Auth::user()->name }}</h3>
                    <div style="display: flex; align-items: center; gap: 6px; color: #d97706; font-size: 0.875rem; font-weight: 600;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                        </svg>
                        <span>Thành viên @switch(Auth::user()->membership_level ?? 'new') @case('silver') Bạc @break @case('gold') Vàng @break @case('diamond') Kim cương @break @default Mới @endswitch</span>
                    </div>
                </div>
            </div>

            <!-- Menu List -->
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <!-- Item 1 -->
                <a href="{{ route('profile') }}" style="display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: #374151; padding: 8px 0; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center;">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
                        </div>
                        <span style="font-size: 1rem; font-weight: 500;">Thông tin tài khoản</span>
                    </div>
                    <svg width="20" height="20" fill="none" stroke="#9ca3af" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
                
                <!-- Item 2 -->
                <a href="{{ route('orders') }}" style="display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: #374151; padding: 8px 0; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center;">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <span style="font-size: 1rem; font-weight: 500;">Đơn hàng của tôi</span>
                    </div>
                    <svg width="20" height="20" fill="none" stroke="#9ca3af" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>

                <!-- Item 3 -->
                

                <!-- Item 4 -->
                
            </div>

            <!-- Logout Button -->
            <div style="margin-top: 32px;">
                <a href="{{ route('logout') }}" style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px 0; border: 1px solid #fca5a5; border-radius: 9999px; color: #dc2626; font-weight: 700; font-size: 1rem; text-decoration: none; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#fef2f2'" onmouseout="this.style.backgroundColor='transparent'">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    Đăng xuất
                </a>
            </div>

            <!-- Footer -->
            <div style="margin-top: 24px; text-align: center; font-size: 0.75rem; color: #9ca3af;">
                Phiên bản 2.4.0 (2026) • Happy Drink
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const $ = (id) => document.getElementById(id);

        /* ---- User Profile Modal ---- */
        const openUserProfile = () => {
            const modal = $('user-profile-modal');
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        };
        const closeUserProfile = () => {
            const modal = $('user-profile-modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        };
        
        $('account-btn')?.addEventListener('click', (e) => {
            e.preventDefault();
            openUserProfile();
        });
        $('close-user-profile')?.addEventListener('click', closeUserProfile);
        $('user-profile-overlay')?.addEventListener('click', closeUserProfile);
    })();
</script>
@endpush
@endauth
