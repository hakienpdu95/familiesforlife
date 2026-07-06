<header class="topbar">
    <div class="topbar-left">
        <button class="icon-btn" id="mobileSidebarBtn" title="Menu" style="display:none">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <button class="icon-btn" id="desktopCollapseBtn" title="Thu gọn sidebar">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

    </div>

    <div class="topbar-center">
        <div class="search-box">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#94a3b8;flex-shrink:0"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="search" placeholder="Tìm kiếm...">
            <kbd>⌘K</kbd>
        </div>
    </div>

    <div class="topbar-right">

        <button class="icon-btn" id="themeBtn" title="Đổi giao diện">
            <svg id="iconSun" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M18.66 5.34l1.41-1.41"/></svg>
            <svg id="iconMoon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
        </button>

        {{-- Bell Dropdown — Alpine component --}}
        <div x-data="notifBell()" @click.outside="open = false" class="dd-wrap">

            <button class="icon-btn" @click="toggle()" title="Thông báo">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span x-show="unread > 0"
                      x-text="unread > 99 ? '99+' : unread"
                      class="notif-badge"></span>
            </button>

            <div x-show="open" x-transition.opacity.duration.150ms
                 class="dd-panel notif-panel" style="display:none">

                {{-- Header --}}
                <div class="notif-hdr">
                    <span>Thông báo
                        <span x-show="unread > 0"
                              x-text="'(' + unread + ' chưa đọc)'"
                              class="notif-hdr__unread"></span>
                    </span>
                    <div class="notif-hdr__actions">
                        <button x-show="unread > 0" @click.prevent="readAll()"
                                class="notif-act-btn">Đọc tất cả</button>
                        <a href="{{ url('/dashboard/notifications') }}" class="notif-act-link">Xem tất cả</a>
                    </div>
                </div>

                {{-- Loading --}}
                <div x-show="loading" class="notif-loading">
                    <svg class="notif-spinner" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" d="M12 2a10 10 0 0 1 10 10"/>
                    </svg>
                </div>

                {{-- Empty --}}
                <div x-show="!loading && items.length === 0" class="notif-empty">
                    Không có thông báo mới
                </div>

                {{-- List --}}
                <div x-show="!loading && items.length > 0" class="notif-list">
                    <template x-for="n in items" :key="n.uuid">
                        <a :href="n.url || '#'"
                           @click="markRead(n)"
                           class="notif-item"
                           :class="{ 'notif-item--unread': !n.read }">
                            <div class="notif-icon" :class="'notif-icon--' + n.icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                     x-html="iconPath(n.icon)"></svg>
                            </div>
                            <div class="notif-txt">
                                <p :class="{ 'notif-txt--read': n.read }" x-text="n.title"></p>
                                <small x-text="n.time_ago"></small>
                            </div>
                            <div x-show="!n.read" class="notif-dot"></div>
                        </a>
                    </template>
                </div>

                {{-- Footer --}}
                <div x-show="!loading" class="notif-footer">
                    <a href="{{ url('/dashboard/notifications') }}" class="notif-footer-link">
                        Xem tất cả thông báo →
                    </a>
                </div>

            </div>
        </div>

        <div class="dd-wrap">
            <button class="avatar-btn" id="userBtn">
                <img src="https://api.dicebear.com/9.x/initials/svg?seed={{ urlencode(auth()->user()->name ?? 'Admin') }}&backgroundColor=6366f1&fontFamily=Arial&fontSize=40&fontWeight=700" alt="Avatar">
                <span class="av-name">{{ auth()->user()->name ?? 'Admin' }}</span>
                <svg class="av-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="dd-panel user-panel" id="userPanel">
                <div class="user-email">{{ auth()->user()->email ?? 'admin@example.com' }}</div>
                <a href="{{ route('auth.profile') }}" class="u-item">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Hồ sơ cá nhân
                </a>
                <a href="#" class="u-item">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Cài đặt
                </a>
                <div class="u-divider"></div>
                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="u-item danger">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Đăng xuất
                    </button>
                </form>
            </div>
        </div>

    </div>
</header>
