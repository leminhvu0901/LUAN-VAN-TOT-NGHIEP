@extends('layouts.app')

@section('content')
<div class="profile-page">
    <div class="profile-container">
        
        {{-- Header --}}
        <div class="profile-header">
            <a href="{{ url('/') }}" class="profile-header__btn">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>
            <h1 class="profile-header__title">Thông tin tài khoản</h1>
            <button class="profile-header__icon">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </button>
        </div>

        {{-- Avatar Section --}}
        <div class="profile-avatar-sec">
            <div class="profile-avatar-wrapper">
                <div class="profile-avatar">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=10b981&color=fff&size=200" alt="Avatar">
                </div>
                <div class="profile-avatar__edit">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </div>
            </div>
            <h2 class="profile-name">{{ Auth::user()->name }}</h2>
            <p class="profile-tier">Thành viên @switch(Auth::user()->membership_level ?? 'new') @case('silver') Bạc @break @case('gold') Vàng @break @case('diamond') Kim cương @break @default Mới @endswitch</p>
        </div>

        {{-- Form fields --}}
        <div class="profile-form">
            
            {{-- Họ và tên --}}
            <div class="profile-group">
                <label class="profile-label">Họ và tên</label>
                <div class="profile-input-wrapper">
                    <div class="profile-icon-left">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input type="text" value="{{ Auth::user()->name }}" class="profile-input" placeholder="Nhập họ và tên">
                </div>
            </div>

            {{-- Số điện thoại --}}
            <div class="profile-group">
                <label class="profile-label">Số điện thoại</label>
                <div class="profile-input-wrapper">
                    <div class="profile-icon-left">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <input type="tel" value="{{ Auth::user()->phone ?? '' }}" class="profile-input" placeholder="Nhập số điện thoại">
                </div>
            </div>

            {{-- Email --}}
            <div class="profile-group">
                <label class="profile-label">Email</label>
                <div class="profile-input-wrapper">
                    <div class="profile-icon-left">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <input type="email" value="{{ Auth::user()->email }}" class="profile-input" placeholder="Nhập email">
                </div>
            </div>

            {{-- Địa chỉ --}}
            <div class="profile-group">
                <label class="profile-label">Địa chỉ</label>
                <div class="profile-input-wrapper">
                    <div class="profile-icon-left">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <input type="text" value="{{ Auth::user()->address ?? '' }}" class="profile-input" placeholder="Nhập địa chỉ của bạn">
                </div>
            </div>

        </div>

        {{-- Fixed Bottom Button --}}
        <div class="profile-footer">
            <div class="profile-footer__inner">
                <button class="profile-save-btn">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                    </svg>
                    Lưu thay đổi
                </button>
            </div>
        </div>

    </div>
</div>
@endsection
