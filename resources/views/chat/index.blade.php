@extends('layouts.master')

@section('title', 'Live Chat')

@push('styles')
<style>
    .gzc { display:grid; grid-template-columns: 320px minmax(0,1fr) 300px; gap:16px; height: calc(100vh - 270px); min-height: 520px; }
    .gzc-panel { background:var(--cb-surface,#fff); border:1px solid var(--cb-border,#e2e8f0); border-radius:16px; box-shadow:var(--cb-shadow); display:flex; flex-direction:column; min-height:0; overflow:hidden; }
    .gzc-head { padding:12px 14px; border-bottom:1px solid var(--cb-border,#e2e8f0); }
    .gzc-tabs { display:flex; gap:4px; flex-wrap:wrap; }
    .gzc-tab { border:0; background:transparent; padding:6px 10px; border-radius:999px; font-size:12.5px; font-weight:600; color:var(--cb-muted,#64748b); }
    .gzc-tab.active { background:var(--cb-teal,#0d9488); color:#fff; }
    .gzc-tab .n { display:inline-block; min-width:18px; padding:0 5px; margin-left:4px; border-radius:9px; background:rgba(0,0,0,.08); font-size:11px; }
    .gzc-tab.active .n { background:rgba(255,255,255,.25); }
    .gzc-search { width:100%; border:1.5px solid var(--cb-border,#e2e8f0); border-radius:10px; padding:7px 10px; font-size:13px; background:transparent; color:inherit; margin-top:10px; }
    .gzc-list { overflow-y:auto; flex:1; }
    .gzc-item { display:flex; gap:10px; padding:11px 14px; cursor:pointer; border-bottom:1px solid var(--cb-border,#eef2f7); position:relative; }
    .gzc-item:hover { background:var(--cb-surface-2,#f8fafc); }
    .gzc-item.active { background:rgba(13,148,136,.09); }
    .gzc-item.active::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--cb-teal,#0d9488); }
    .gzc-av { width:38px; height:38px; border-radius:50%; flex:0 0 38px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; color:#fff; background:linear-gradient(135deg,#0d9488,#0f2342); overflow:hidden; }
    .gzc-av img { width:100%; height:100%; object-fit:cover; }
    .gzc-item .nm { font-weight:700; font-size:13.5px; color:var(--cb-heading,#0f2342); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .gzc-item .pv { font-size:12.5px; color:var(--cb-muted,#64748b); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .gzc-item .tm { font-size:11px; color:var(--cb-muted,#94a3b8); white-space:nowrap; }
    .gzc-item .unread { background:#ef4444; color:#fff; border-radius:9px; font-size:11px; padding:0 6px; font-weight:700; }
    .gzc-chip { display:inline-block; font-size:10.5px; font-weight:700; padding:1px 7px; border-radius:999px; background:var(--cb-surface-2,#f1f5f9); color:var(--cb-muted,#64748b); }
    .gzc-chip.waiting { background:#fef3c7; color:#92400e; }
    .gzc-thread { flex:1; overflow-y:auto; padding:16px; background:var(--cb-surface-2,#f8fafc); }
    .gzc-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--cb-muted,#94a3b8); text-align:center; padding:30px; }
    .gzc-row { display:flex; margin:6px 0; gap:8px; align-items:flex-end; }
    .gzc-row.me { justify-content:flex-end; }
    .gzc-b { max-width:72%; padding:9px 12px; border-radius:16px; font-size:13.5px; line-height:1.45; white-space:pre-wrap; word-wrap:break-word; background:var(--cb-surface,#fff); border:1px solid var(--cb-border,#e2e8f0); color:var(--cb-heading,#0f172a); }
    .gzc-row.me .gzc-b { background:var(--cb-teal,#0d9488); color:#fff; border-color:transparent; border-bottom-right-radius:5px; }
    .gzc-row.me .gzc-b a { color:#fff; }
    .gzc-row.them .gzc-b { border-bottom-left-radius:5px; }
    .gzc-b img.ph { display:block; max-width:260px; max-height:260px; border-radius:10px; cursor:zoom-in; margin:-2px -4px 4px; }
    .gzc-meta { font-size:10.5px; opacity:.75; margin-top:3px; text-align:right; }
    .gzc-sys { text-align:center; margin:10px 0; } .gzc-sys span { font-size:11.5px; background:var(--cb-surface,#fff); border:1px dashed var(--cb-border,#e2e8f0); color:var(--cb-muted,#64748b); padding:3px 10px; border-radius:999px; display:inline-block; }
    .gzc-day { text-align:center; font-size:11px; color:var(--cb-muted,#94a3b8); margin:14px 0 6px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
    .gzc-card { display:flex; gap:10px; align-items:center; min-width:220px; color:inherit; text-decoration:none; white-space:normal; }
    .gzc-card img { width:44px; height:44px; border-radius:8px; object-fit:cover; background:#fff; }
    .gzc-card .t { font-weight:700; font-size:13px; } .gzc-card .s { font-size:12px; opacity:.8; }
    .gzc-typing { font-size:12px; color:var(--cb-muted,#64748b); padding:0 16px 6px; height:20px; background:var(--cb-surface-2,#f8fafc); }
    .gzc-compose { border-top:1px solid var(--cb-border,#e2e8f0); padding:10px; display:flex; gap:8px; align-items:flex-end; }
    .gzc-compose textarea { flex:1; resize:none; border:1.5px solid var(--cb-border,#e2e8f0); border-radius:12px; padding:9px 12px; font-size:13.5px; max-height:140px; background:transparent; color:inherit; }
    .gzc-compose textarea:focus { outline:none; border-color:var(--cb-teal,#0d9488); }
    .gzc-ibtn { border:0; width:38px; height:38px; border-radius:10px; background:var(--cb-surface-2,#f1f5f9); color:var(--cb-heading,#0f2342); display:inline-flex; align-items:center; justify-content:center; font-size:18px; }
    .gzc-send { background:var(--cb-teal,#0d9488); color:#fff; }
    .gzc-attach { padding:6px 10px 0; display:none; } .gzc-attach img { height:64px; border-radius:8px; }
    .gzc-banner { padding:10px 14px; background:#fffbeb; color:#92400e; font-size:13px; border-top:1px solid #fde68a; }
    .gzc-side { overflow-y:auto; padding:16px; }
    .gzc-side h6 { font-size:11.5px; text-transform:uppercase; letter-spacing:.06em; color:var(--cb-muted,#64748b); margin:18px 0 8px; }
    .gzc-kv { display:flex; justify-content:space-between; gap:8px; font-size:13px; padding:3px 0; } .gzc-kv span:first-child { color:var(--cb-muted,#64748b); }
    .gzc-kv a { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .gzc-ord { border:1px solid var(--cb-border,#e2e8f0); border-radius:10px; padding:8px 10px; margin-bottom:8px; font-size:12.5px; }
    .gzc-ord.ctx { border-color:var(--cb-teal,#0d9488); box-shadow:0 0 0 2px rgba(13,148,136,.12); }
    .gzc-dot { width:9px; height:9px; border-radius:50%; display:inline-block; margin-right:6px; }
    .gzc-dot.online { background:#22c55e; } .gzc-dot.away { background:#f59e0b; } .gzc-dot.offline { background:#94a3b8; }
    .gzc-more { text-align:center; padding:6px; }
    @media (max-width: 1399px) { .gzc { grid-template-columns: 290px minmax(0,1fr); } .gzc-customer { display:none; } }
    @media (max-width: 991px)  { .gzc { grid-template-columns: 1fr; height:auto; } .gzc-panel.gzc-chat { height:70vh; } .gzc-list { max-height:40vh; } }
</style>
@endpush

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Live Chat" icon="ri-chat-smile-3-line" subtitle="Talk to customers in real time — orders, deliveries, payments and refunds.">
        <x-slot:pills>
            <span class="cb-meta-pill"><i class="ri-time-line"></i> <span id="hdrWaiting">0</span> waiting</span>
            <span class="cb-meta-pill"><i class="ri-user-voice-line"></i> <span id="hdrMine">0</span> with you</span>
            <span class="cb-meta-pill" id="rtPill"><i class="ri-wifi-line"></i> <span id="rtState">Connecting…</span></span>
        </x-slot:pills>
        <x-slot:actions>
            <div class="dropdown">
                <button class="cb-hero-btn dropdown-toggle" data-bs-toggle="dropdown" type="button">
                    <span class="gzc-dot {{ $agentStatus }}" id="presDot"></span><span id="presLabel">{{ ucfirst($agentStatus) }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-presence="online"><span class="gzc-dot online"></span>Online — take new chats</a></li>
                    <li><a class="dropdown-item" href="#" data-presence="away"><span class="gzc-dot away"></span>Away — keep my chats</a></li>
                    <li><a class="dropdown-item" href="#" data-presence="offline"><span class="gzc-dot offline"></span>Offline</a></li>
                </ul>
            </div>
            <button type="button" class="cb-hero-btn" id="btnNotify"><i class="ri-notification-3-line"></i> Desktop alerts</button>
        </x-slot:actions>
    </x-cb.hero>

    <div class="gzc">
        {{-- Conversations --}}
        <div class="gzc-panel">
            <div class="gzc-head">
                <div class="gzc-tabs" id="tabs">
                    <button class="gzc-tab active" data-tab="mine" type="button">Mine <span class="n" data-count="mine">0</span></button>
                    <button class="gzc-tab" data-tab="waiting" type="button">Waiting <span class="n" data-count="waiting">0</span></button>
                    <button class="gzc-tab" data-tab="open" type="button">Open</button>
                    <button class="gzc-tab" data-tab="closed" type="button">Resolved</button>
                </div>
                <input class="gzc-search" id="search" placeholder="Search name, email, phone, order…" autocomplete="off">
            </div>
            <div class="gzc-list" id="list"></div>
        </div>

        {{-- Thread --}}
        <div class="gzc-panel gzc-chat" id="chatPanel">
            <div class="gzc-empty" id="emptyState">
                <i class="ri-chat-3-line" style="font-size:46px;"></i>
                <div class="fw-semibold mt-2">Pick a conversation</div>
                <div class="small">New chats from the app appear under <b>Waiting</b>. Set yourself <b>Online</b> to get chats automatically.</div>
            </div>
            <div id="chatWrap" class="d-none flex-column h-100" style="min-height:0;">
                <div class="gzc-head d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2" style="min-width:0;">
                        <div class="gzc-av" id="cAv"></div>
                        <div style="min-width:0;">
                            <div class="fw-bold text-truncate" id="cName"></div>
                            <div class="small text-muted text-truncate" id="cSub"></div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button class="btn btn-sm btn-success" id="btnTake" type="button"><i class="ri-hand-heart-line"></i> Take chat</button>
                        @if($canManage)
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown" id="btnAssign" type="button"><i class="ri-user-shared-line"></i> Assign</button>
                            <ul class="dropdown-menu dropdown-menu-end" id="agentMenu"><li><span class="dropdown-item-text small text-muted">Loading…</span></li></ul>
                        </div>
                        @endif
                        <button class="btn btn-sm btn-outline-danger" id="btnClose" type="button"><i class="ri-check-double-line"></i> Resolve</button>
                    </div>
                </div>
                <div class="gzc-thread" id="thread"></div>
                <div class="gzc-typing" id="typing"></div>
                <div class="gzc-banner d-none" id="ownerBanner"></div>
                <div class="gzc-attach" id="attachPrev"><img alt=""> <button class="btn btn-sm btn-link text-danger" id="attachClear" type="button">Remove</button></div>
                <div class="gzc-compose" id="composer">
                    <div class="dropup">
                        <button class="gzc-ibtn" data-bs-toggle="dropdown" title="Quick replies" type="button"><i class="ri-flashlight-line"></i></button>
                        <ul class="dropdown-menu" id="quickMenu" style="max-width:380px;"></ul>
                    </div>
                    <button class="gzc-ibtn" id="btnImg" title="Send a photo" type="button"><i class="ri-image-add-line"></i></button>
                    <input type="file" id="fileImg" accept="image/*" class="d-none">
                    <textarea id="input" rows="1" placeholder="Type a reply…  (Enter to send, Shift+Enter for a new line)"></textarea>
                    <button class="gzc-ibtn gzc-send" id="btnSend" title="Send" type="button"><i class="ri-send-plane-2-fill"></i></button>
                </div>
            </div>
        </div>

        {{-- Customer --}}
        <div class="gzc-panel gzc-customer">
            <div class="gzc-side" id="customer">
                <div class="text-muted small text-center mt-5">Customer details appear here.</div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<div class="modal fade" id="imgModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content bg-transparent border-0"><img id="imgModalSrc" class="img-fluid rounded" alt=""></div></div></div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
(function () {
    'use strict';
    var R = {
        list:     @json(route('admin.chat.conversations')),
        show:     @json(url('admin/chat/conversations')) + '/',
        presence: @json(route('admin.chat.presence')),
        agents:   @json(route('admin.chat.agents')),
        summary:  @json(route('admin.chat.summary')),
        auth:     @json(route('admin.chat.pusher-auth')),
        order:    @json(url('adminorders')) + '/'
    };
    var ME = {{ (int) auth()->id() }};
    var CAN_MANAGE = @json($canManage);
    var PUSHER = @json($pusher);
    var CUR = @json(\App\Support\Money::symbol());
    var MAX_KB = {{ (int) config('chat.max_image_kb') }};
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;
    var QUICK = [
        'Hi {name} 👋, thanks for reaching out to Gozak Mart. How can I help you today?',
        'Let me check that for you — one moment please.',
        'Could you share your order number so I can look it up?',
        'Your order is being processed and will be dispatched soon. You will get a notification once it ships.',
        'Deliveries within Lagos take 1–3 working days; other states take 3–7 working days.',
        'Please send a clear photo of the item so we can sort this out quickly.',
        'Your refund has been started. It usually reaches your account within 5–10 working days.',
        'Is there anything else I can help you with?',
        'Thanks for chatting with us, {name}! Have a lovely day. 💚'
    ];

    var state = { tab: 'mine', q: '', items: [], current: null, messages: [], hasMore: false, canReply: false, file: null, pusher: null, convChannel: null, lastTypingSent: 0, typingTimer: null };
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); };
    var initials = function (n) { return String(n || '?').trim().split(/\s+/).slice(0, 2).map(function (p) { return p[0]; }).join('').toUpperCase(); };
    var money = function (v) { return CUR + Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 }); };
    var timeShort = function (iso) {
        if (!iso) return '';
        var d = new Date(iso), now = new Date();
        if (d.toDateString() === now.toDateString()) return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        if ((now - d) / 86400000 < 6) return d.toLocaleDateString([], { weekday: 'short' }) + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        return d.toLocaleDateString([], { day: 'numeric', month: 'short' });
    };
    var waitLabel = function (s) { if (s == null) return ''; s = Math.round(s); return s < 60 ? s + 's' : s < 3600 ? Math.floor(s / 60) + 'm' : Math.floor(s / 3600) + 'h ' + Math.floor((s % 3600) / 60) + 'm'; };
    var avatarHtml = function (name, url) { return url ? '<img src="' + esc(url) + '" alt="">' : esc(initials(name)); };

    function api(method, url, body, isForm) {
        var opts = { method: method, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' };
        if (body && !isForm) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
        if (body && isForm) { opts.body = body; }
        return fetch(url, opts).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (j) {
                if (!r.ok) { var e = new Error(j.message || ('Request failed (' + r.status + ')')); e.status = r.status; throw e; }
                return j;
            });
        });
    }
    function toast(msg, icon) { if (window.GZ && GZ.toast) GZ.toast(msg, icon); }

    // ── Counts ─────────────────────────────────────────────────────────────
    function setCounts(c) {
        if (!c) return;
        $('#hdrWaiting').textContent = c.waiting; $('#hdrMine').textContent = c.mine;
        document.querySelectorAll('[data-count]').forEach(function (el) { el.textContent = c[el.dataset.count] || 0; });
        if (window.GZChatBadge) window.GZChatBadge(c);
    }
    var countsTimer;
    function refreshCounts() { clearTimeout(countsTimer); countsTimer = setTimeout(function () { api('GET', R.summary).then(setCounts).catch(function () {}); }, 400); }

    // ── Conversation list ──────────────────────────────────────────────────
    function loadList() {
        var url = R.list + '?tab=' + state.tab + (state.q ? '&q=' + encodeURIComponent(state.q) : '');
        return api('GET', url).then(function (j) { state.items = j.data; setCounts(j.counts); renderList(); });
    }
    function matchesTab(c) {
        if (state.q) return state.items.some(function (x) { return x.id === c.id; });
        switch (state.tab) {
            case 'waiting': return c.status === 'waiting';
            case 'mine':    return c.status !== 'closed' && !!c.agent && c.agent.id === ME;
            case 'open':    return c.status !== 'closed' && (CAN_MANAGE || !c.agent || c.agent.id === ME);
            case 'closed':  return c.status === 'closed' && (CAN_MANAGE || (!!c.agent && c.agent.id === ME));
        }
        return true;
    }
    function renderList() {
        var el = $('#list');
        if (!state.items.length) { el.innerHTML = '<div class="gzc-empty small py-5"><i class="ri-inbox-line" style="font-size:30px;"></i><div class="mt-1">Nothing here</div></div>'; return; }
        el.innerHTML = state.items.map(function (c) {
            var cu = c.customer || {};
            var right = c.status === 'waiting' ? '<span class="gzc-chip waiting">waiting ' + waitLabel(c.wait_seconds) + '</span>' : '<span class="tm">' + timeShort(c.last_message_at) + '</span>';
            return '<div class="gzc-item' + (state.current && state.current.id === c.id ? ' active' : '') + '" data-id="' + c.id + '">' +
                '<div class="gzc-av">' + avatarHtml(cu.name, cu.avatar) + '</div>' +
                '<div style="min-width:0;flex:1;">' +
                    '<div class="d-flex justify-content-between gap-2"><div class="nm">' + esc(cu.name || 'Customer') + '</div>' + right + '</div>' +
                    '<div class="d-flex justify-content-between gap-2 align-items-center"><div class="pv">' + esc(c.preview || c.topic_label) + '</div>' +
                    (c.agent_unread ? '<span class="unread">' + c.agent_unread + '</span>' : '') + '</div>' +
                    '<div class="mt-1"><span class="gzc-chip">' + esc(c.topic_label) + '</span> ' +
                    (c.agent && c.agent.id !== ME ? '<span class="gzc-chip"><i class="ri-user-line"></i> ' + esc(c.agent_full_name || c.agent.name) + '</span>' : '') +
                    (c.rating ? ' <span class="gzc-chip">' + '★'.repeat(c.rating) + '</span>' : '') + '</div>' +
                '</div></div>';
        }).join('');
    }
    function upsertItem(c) {
        var i = state.items.findIndex(function (x) { return x.id === c.id; });
        var keep = matchesTab(c);
        if (i >= 0) state.items.splice(i, 1);
        if (keep) { if (state.tab === 'waiting') state.items.push(c); else state.items.unshift(c); }
        renderList();
    }

    // ── Thread ─────────────────────────────────────────────────────────────
    function openConversation(id) {
        return api('GET', R.show + id).then(function (j) {
            state.current = j.conversation; state.messages = j.messages; state.hasMore = j.has_more; state.canReply = j.can_reply;
            $('#emptyState').classList.add('d-none'); $('#chatWrap').classList.remove('d-none'); $('#chatWrap').classList.add('d-flex');
            $('#typing').textContent = '';
            renderHeader(); renderThread(true); renderCustomer(j.customer); renderList();
            subscribeConversation(id);
            if (state.canReply && j.conversation.agent_unread) markRead();
            history.replaceState(null, '', '?c=' + id);
            if (state.canReply) $('#input').focus();
        }).catch(function (e) { toast(e.message, 'error'); });
    }
    function renderHeader() {
        var c = state.current, cu = c.customer || {};
        var mine = !!c.agent && c.agent.id === ME;
        $('#cAv').innerHTML = avatarHtml(cu.name, cu.avatar);
        $('#cName').textContent = cu.name || 'Customer';
        var status = c.status === 'waiting' ? 'Waiting for an agent' : c.status === 'active' ? ('With ' + (mine ? 'you' : (c.agent_full_name || 'another agent'))) : 'Resolved';
        $('#cSub').textContent = c.topic_label + ' · ' + status + (c.order_id ? ' · Order ' + c.order_id.substring(0, 8).toUpperCase() : '');

        var take = $('#btnTake');
        take.classList.toggle('d-none', mine || (c.status === 'active' && !CAN_MANAGE));
        take.innerHTML = c.status === 'closed' ? '<i class="ri-refresh-line"></i> Reopen' : (c.agent ? '<i class="ri-user-received-2-line"></i> Take over' : '<i class="ri-hand-heart-line"></i> Take chat');
        $('#btnClose').classList.toggle('d-none', c.status === 'closed' || !state.canReply);

        var banner = $('#ownerBanner');
        if (!state.canReply) {
            banner.classList.remove('d-none'); banner.innerHTML = '<i class="ri-lock-line"></i> ' + esc(c.agent_full_name || 'Another agent') + ' is handling this chat. You can read it but not reply.';
        } else if (c.status === 'closed') {
            banner.classList.remove('d-none');
            banner.innerHTML = '<i class="ri-information-line"></i> Resolved' + (c.rating ? ' · rated ' + '★'.repeat(c.rating) + (c.rating_comment ? ' — “' + esc(c.rating_comment) + '”' : '') : '') + '. Sending a message reopens it.';
        } else { banner.classList.add('d-none'); }
        $('#composer').style.display = state.canReply ? '' : 'none';
    }
    function bubbleBody(m) {
        var meta = m.meta || {};
        if (m.type === 'image') {
            return '<img class="ph" src="' + esc(m.image_url) + '" alt="photo">' + (m.body ? esc(m.body) : '');
        }
        if (m.type === 'order') {
            return '<a class="gzc-card" target="_blank" href="' + R.order + encodeURIComponent(meta.order_id || '') + '"><i class="ri-file-list-3-line" style="font-size:26px;"></i><div><div class="t">Order ' + esc(meta.reference) + '</div>' +
                '<div class="s">' + esc(meta.items) + ' item(s) · ' + money(meta.total) + ' · ' + esc(meta.status) + '</div></div></a>';
        }
        if (m.type === 'product') {
            return '<div class="gzc-card">' + (meta.thumbnail ? '<img src="' + esc(meta.thumbnail) + '" alt="">' : '<i class="ri-shopping-bag-3-line" style="font-size:26px;"></i>') + '<div><div class="t">' + esc(meta.title) + '</div><div class="s">' + money(meta.price) + '</div></div></div>';
        }
        return esc(m.body);
    }
    function msgHtml(m, prev) {
        var out = '';
        var d = m.created_at ? new Date(m.created_at) : new Date();
        if (!prev || new Date(prev.created_at || Date.now()).toDateString() !== d.toDateString()) {
            out += '<div class="gzc-day">' + (d.toDateString() === new Date().toDateString() ? 'Today' : d.toLocaleDateString([], { weekday: 'short', day: 'numeric', month: 'short' })) + '</div>';
        }
        if (m.sender_type === 'system') return out + '<div class="gzc-sys"><span>' + esc(m.body) + '</span></div>';
        var mine = m.sender_type === 'agent';
        var ticks = mine ? (m.pending ? ' <i class="ri-time-line"></i>' : (m.read_at ? ' <i class="ri-check-double-line" title="Seen"></i>' : ' <i class="ri-check-line" title="Sent"></i>')) : '';
        var who = mine && m.sender_id !== ME ? esc(m.sender_name) + ' · ' : '';
        return out + '<div class="gzc-row ' + (mine ? 'me' : 'them') + '">' +
            '<div class="gzc-b">' + bubbleBody(m) + '<div class="gzc-meta">' + who + timeShort(m.created_at) + ticks + '</div></div></div>';
    }
    function renderThread(scrollBottom) {
        var t = $('#thread'), prevH = t.scrollHeight, prevTop = t.scrollTop;
        var html = state.hasMore ? '<div class="gzc-more"><button class="btn btn-sm btn-light" id="btnOlder" type="button">Load earlier messages</button></div>' : '';
        state.messages.forEach(function (m, i) { html += msgHtml(m, state.messages[i - 1]); });
        t.innerHTML = html;
        t.scrollTop = scrollBottom ? t.scrollHeight : t.scrollHeight - prevH + prevTop;
        t.querySelectorAll('img.ph').forEach(function (img) { img.addEventListener('load', function () { if (scrollBottom) t.scrollTop = t.scrollHeight; }, { once: true }); });
    }
    function nearBottom() { var t = $('#thread'); return t.scrollHeight - t.scrollTop - t.clientHeight < 120; }
    function addMessage(m) {
        if (!state.current || m.conversation_id !== state.current.id) return;
        var i = state.messages.findIndex(function (x) { return (m.id && x.id === m.id) || (m.client_id && x.client_id === m.client_id); });
        var stick = nearBottom() || m.sender_type === 'agent';
        if (i >= 0) state.messages[i] = m; else state.messages.push(m);
        renderThread(stick);
    }
    var loadingOlder = false;
    function loadOlder() {
        var first = state.messages.find(function (m) { return m.id; });
        if (!first || loadingOlder) return;
        loadingOlder = true;
        api('GET', R.show + state.current.id + '/messages?before_id=' + first.id).then(function (j) {
            state.messages = j.messages.concat(state.messages); state.hasMore = j.has_more; renderThread(false);
        }).finally(function () { loadingOlder = false; });
    }
    function catchUp() {
        if (!state.current) return;
        var last = state.messages.filter(function (m) { return m.id; }).slice(-1)[0];
        api('GET', R.show + state.current.id + '/messages?after_id=' + (last ? last.id : 0)).then(function (j) { j.messages.forEach(addMessage); }).catch(function () {});
    }

    // ── Customer panel ─────────────────────────────────────────────────────
    function renderCustomer(c) {
        var el = $('#customer');
        if (!c) { el.innerHTML = '<div class="text-muted small">Customer account removed.</div>'; return; }
        var orders = (c.recent_orders || []).map(function (o) {
            return '<div class="gzc-ord' + (o.id === c.context_order ? ' ctx' : '') + '">' +
                '<div class="d-flex justify-content-between"><a href="' + esc(o.url) + '" target="_blank" class="fw-bold">' + esc(o.reference) + '</a><span>' + money(o.total) + '</span></div>' +
                '<div class="d-flex justify-content-between align-items-center mt-1"><span class="text-muted">' + esc(o.date) + ' · ' + esc(o.status) + '</span>' +
                (state.canReply ? '<button class="btn btn-sm btn-link p-0" type="button" data-share-order="' + esc(o.id) + '">Send to chat</button>' : '') + '</div></div>';
        }).join('') || '<div class="text-muted small">No orders yet.</div>';
        el.innerHTML =
            '<div class="text-center"><div class="gzc-av mx-auto" style="width:64px;height:64px;font-size:20px;">' + avatarHtml(c.name, c.avatar) + '</div>' +
            '<div class="fw-bold mt-2">' + esc(c.name) + '</div><div class="small text-muted">Customer since ' + esc(c.joined || '—') + '</div></div>' +
            '<h6>Contact</h6>' +
            '<div class="gzc-kv"><span>Email</span>' + (c.email ? '<a href="mailto:' + esc(c.email) + '">' + esc(c.email) + '</a>' : '<span>—</span>') + '</div>' +
            '<div class="gzc-kv"><span>Phone</span>' + (c.phone ? '<a href="tel:' + esc(c.phone) + '">' + esc(c.phone) + '</a>' : '<span>—</span>') + '</div>' +
            '<h6>Overview</h6>' +
            '<div class="gzc-kv"><span>Orders</span><b>' + c.orders_count + '</b></div>' +
            '<div class="gzc-kv"><span>Total spent</span><b>' + money(c.total_spent) + '</b></div>' +
            '<div class="gzc-kv"><span>Chats</span><b>' + c.chats_count + '</b></div>' +
            (c.avg_rating ? '<div class="gzc-kv"><span>Avg. chat rating</span><b>' + c.avg_rating + ' ★</b></div>' : '') +
            '<h6>Recent orders</h6>' + orders;
    }

    // ── Actions ────────────────────────────────────────────────────────────
    function uid() { return 'w' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8); }
    function send() {
        if (!state.current || !state.canReply) return;
        var input = $('#input'), body = input.value.trim();
        if (!body && !state.file) return;
        var cid = uid(), fd = new FormData();
        if (body) fd.append('body', body);
        if (state.file) fd.append('image', state.file);
        fd.append('client_id', cid);
        addMessage({ id: null, client_id: cid, conversation_id: state.current.id, sender_type: 'agent', sender_id: ME, type: state.file ? 'image' : 'text',
                     body: body || null, image_url: state.file ? URL.createObjectURL(state.file) : null, created_at: new Date().toISOString(), pending: true });
        input.value = ''; autosize(); clearFile(); sendTyping(false);
        api('POST', R.show + state.current.id + '/messages', fd, true).then(function (j) {
            addMessage(j.message); refreshCurrent(j.conversation); upsertItem(j.conversation);
        }).catch(function (e) {
            state.messages = state.messages.filter(function (m) { return m.client_id !== cid; }); renderThread(true);
            if (!input.value) input.value = body;
            toast(e.message, 'error');
        });
    }
    function markRead() {
        if (!state.current) return;
        var id = state.current.id;
        state.current.agent_unread = 0;
        api('POST', R.show + id + '/read').then(function (j) {
            setCounts(j.counts);
            var it = state.items.find(function (x) { return x.id === id; });
            if (it) { it.agent_unread = 0; renderList(); }
        }).catch(function () {});
    }
    function sendTyping(on) {
        if (!state.current || !state.canReply) return;
        var now = Date.now();
        if (on && now - state.lastTypingSent < 2500) return;
        if (!on && !state.lastTypingSent) return;
        state.lastTypingSent = on ? now : 0;
        api('POST', R.show + state.current.id + '/typing', { is_typing: on, socket_id: state.pusher ? state.pusher.connection.socket_id : null }).catch(function () {});
    }
    function refreshCurrent(c) {
        if (!state.current || !c || c.id !== state.current.id) return;
        state.current = Object.assign({}, state.current, c);
        var cur = state.current;
        state.canReply = CAN_MANAGE || !cur.agent || cur.agent.id === ME || cur.status === 'closed';
        renderHeader();
    }
    function clearFile() { state.file = null; $('#fileImg').value = ''; $('#attachPrev').style.display = 'none'; }
    function setFile(f) {
        if (!f) return;
        if (f.size > MAX_KB * 1024) { toast('That image is too large', 'error'); return; }
        state.file = f; var p = $('#attachPrev'); p.querySelector('img').src = URL.createObjectURL(f); p.style.display = 'block'; $('#input').focus();
    }
    function autosize() { var t = $('#input'); t.style.height = 'auto'; t.style.height = Math.min(140, t.scrollHeight) + 'px'; }

    // ── Realtime ───────────────────────────────────────────────────────────
    function setRt(text, ok) { $('#rtState').textContent = text; $('#rtPill').style.opacity = ok ? 1 : .65; }
    function connect() {
        if (!PUSHER.enabled || !window.Pusher) {
            setRt('Auto-refresh', false);
            setInterval(function () { loadList(); catchUp(); }, 8000);
            return;
        }
        state.pusher = new Pusher(PUSHER.key, {
            cluster: PUSHER.cluster, forceTLS: true,
            channelAuthorization: { endpoint: R.auth, transport: 'ajax', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } }
        });
        var wasConnected = false;
        state.pusher.connection.bind('state_change', function (s) {
            var ok = s.current === 'connected';
            setRt(ok ? 'Live' : (s.current === 'connecting' ? 'Connecting…' : 'Reconnecting…'), ok);
            if (ok && wasConnected) { loadList(); catchUp(); }   // missed events while offline
            if (ok) wasConnected = true;
        });
        state.pusher.subscribe('private-chat.agents').bind('inbox.updated', function (d) {
            var c = d.conversation;
            if (state.current && state.current.id === c.id && !document.hidden && state.canReply) c.agent_unread = 0;
            upsertItem(c);
            refreshCurrent(c);
            refreshCounts();
            var forMe = !c.agent || c.agent.id === ME;
            var viewing = state.current && state.current.id === c.id && !document.hidden;
            if (c.agent_unread > 0 && forMe && !viewing) notify(c);
        });
    }
    function subscribeConversation(id) {
        if (!state.pusher) return;
        if (state.convChannel) state.pusher.unsubscribe(state.convChannel.name);
        state.convChannel = state.pusher.subscribe('private-chat.conversation.' + id);
        state.convChannel.bind('message.new', function (d) {
            addMessage(d.message);
            if (d.conversation) refreshCurrent(d.conversation);
            if (d.message.sender_type === 'customer') {
                $('#typing').textContent = '';
                if (!document.hidden && state.canReply) markRead();
            }
        });
        state.convChannel.bind('message.read', function (d) {
            if (d.reader !== 'customer') return;
            state.messages.forEach(function (m) { if (m.sender_type === 'agent' && m.id && m.id <= d.up_to_id && !m.read_at) m.read_at = d.read_at; });
            renderThread(nearBottom());
        });
        state.convChannel.bind('typing', function (d) {
            if (d.side !== 'customer') return;
            clearTimeout(state.typingTimer);
            $('#typing').textContent = d.is_typing ? (d.name || 'Customer') + ' is typing…' : '';
            if (d.is_typing) state.typingTimer = setTimeout(function () { $('#typing').textContent = ''; }, 6000);
        });
        state.convChannel.bind('conversation.updated', function (d) { refreshCurrent(d.conversation); });
    }

    // Sound + desktop notification
    var audio = null;
    function beep() {
        try {
            audio = audio || new (window.AudioContext || window.webkitAudioContext)();
            var o = audio.createOscillator(), g = audio.createGain();
            o.frequency.value = 880; g.gain.value = .06; o.connect(g); g.connect(audio.destination); o.start(); o.stop(audio.currentTime + .15);
        } catch (e) {}
    }
    function notify(c) {
        beep();
        if (window.Notification && Notification.permission === 'granted') {
            var n = new Notification((c.customer && c.customer.name) || 'New chat message', { body: c.preview || c.topic_label, tag: 'chat-' + c.id });
            n.onclick = function () { window.focus(); openConversation(c.id); n.close(); };
        }
    }

    // ── Wire up ────────────────────────────────────────────────────────────
    $('#tabs').addEventListener('click', function (e) {
        var b = e.target.closest('.gzc-tab'); if (!b) return;
        document.querySelectorAll('.gzc-tab').forEach(function (x) { x.classList.toggle('active', x === b); });
        state.tab = b.dataset.tab; loadList();
    });
    var searchT;
    $('#search').addEventListener('input', function () { clearTimeout(searchT); var v = this.value; searchT = setTimeout(function () { state.q = v.trim(); loadList(); }, 300); });
    $('#list').addEventListener('click', function (e) { var it = e.target.closest('.gzc-item'); if (it) openConversation(+it.dataset.id); });
    $('#thread').addEventListener('click', function (e) {
        if (e.target.id === 'btnOlder') loadOlder();
        if (e.target.classList.contains('ph')) { $('#imgModalSrc').src = e.target.src; bootstrap.Modal.getOrCreateInstance($('#imgModal')).show(); }
    });
    $('#thread').addEventListener('scroll', function () { if (this.scrollTop < 40 && state.hasMore) loadOlder(); });
    $('#input').addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); send(); } });
    $('#input').addEventListener('input', function () { autosize(); if (this.value.trim()) sendTyping(true); else sendTyping(false); });
    $('#input').addEventListener('focus', function () { if (state.current && state.current.agent_unread && state.canReply) markRead(); });
    $('#input').addEventListener('paste', function (e) {
        var items = (e.clipboardData && e.clipboardData.items) || [];
        for (var i = 0; i < items.length; i++) { if (items[i].type.indexOf('image') === 0) { setFile(items[i].getAsFile()); break; } }
    });
    $('#btnSend').addEventListener('click', send);
    $('#btnImg').addEventListener('click', function () { $('#fileImg').click(); });
    $('#fileImg').addEventListener('change', function () { setFile(this.files[0]); });
    $('#attachClear').addEventListener('click', clearFile);
    $('#quickMenu').innerHTML = QUICK.map(function (q, i) { return '<li><a class="dropdown-item small text-wrap" href="#" data-q="' + i + '">' + esc(q) + '</a></li>'; }).join('');
    $('#quickMenu').addEventListener('click', function (e) {
        var a = e.target.closest('[data-q]'); if (!a) return; e.preventDefault();
        var name = ((state.current && state.current.customer && state.current.customer.name) || '').split(' ')[0] || 'there';
        var t = $('#input'); t.value = QUICK[a.dataset.q].replace('{name}', name); autosize(); t.focus();
    });
    $('#btnTake').addEventListener('click', function () {
        api('POST', R.show + state.current.id + '/take').then(function (j) {
            state.canReply = true; refreshCurrent(j.conversation); upsertItem(j.conversation); refreshCounts(); $('#input').focus();
        }).catch(function (e) { toast(e.message, 'warning'); loadList(); });
    });
    $('#btnClose').addEventListener('click', function () {
        if (!confirm('Mark this chat as resolved? The customer will be asked to rate it.')) return;
        api('POST', R.show + state.current.id + '/close').then(function (j) { refreshCurrent(j.conversation); upsertItem(j.conversation); refreshCounts(); })
            .catch(function (e) { toast(e.message, 'error'); });
    });
    $('#customer').addEventListener('click', function (e) {
        var b = e.target.closest('[data-share-order]'); if (!b) return;
        api('POST', R.show + state.current.id + '/order-card', { order_id: b.dataset.shareOrder }).then(function (j) { addMessage(j.message); })
            .catch(function (e) { toast(e.message, 'error'); });
    });
    var assignBtn = $('#btnAssign');
    if (assignBtn) {
        assignBtn.addEventListener('show.bs.dropdown', function () {
            api('GET', R.agents).then(function (j) {
                $('#agentMenu').innerHTML = j.agents.map(function (a) {
                    return '<li><a class="dropdown-item d-flex justify-content-between gap-3" href="#" data-agent="' + a.id + '"><span><span class="gzc-dot ' + esc(a.status) + '"></span>' + esc(a.name) + '</span><span class="text-muted small">' + a.chats + ' chats</span></a></li>';
                }).join('') || '<li><span class="dropdown-item-text small">No agents have chat permission yet.</span></li>';
            });
        });
        $('#agentMenu').addEventListener('click', function (e) {
            var a = e.target.closest('[data-agent]'); if (!a) return; e.preventDefault();
            api('POST', R.show + state.current.id + '/assign', { agent_id: +a.dataset.agent }).then(function (j) {
                refreshCurrent(j.conversation); upsertItem(j.conversation); refreshCounts(); toast('Chat assigned');
            }).catch(function (e) { toast(e.message, 'error'); });
        });
    }
    document.querySelectorAll('[data-presence]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            api('POST', R.presence, { status: this.dataset.presence }).then(function (j) {
                $('#presDot').className = 'gzc-dot ' + j.status;
                $('#presLabel').textContent = j.status.charAt(0).toUpperCase() + j.status.slice(1);
                setCounts(j.counts); loadList(); toast('You are now ' + j.status);
            }).catch(function (e) { toast(e.message, 'error'); });
        });
    });
    $('#btnNotify').addEventListener('click', function () {
        if (!window.Notification) { toast('This browser does not support desktop alerts', 'info'); return; }
        Notification.requestPermission().then(function (p) { toast(p === 'granted' ? 'Desktop alerts on' : 'Desktop alerts blocked', p === 'granted' ? 'success' : 'warning'); });
    });
    document.addEventListener('visibilitychange', function () { if (!document.hidden && state.current && state.current.agent_unread && state.canReply) markRead(); });

    // Re-render wait timers every 30s
    setInterval(function () { if (state.tab === 'waiting') { state.items.forEach(function (c) { if (c.wait_seconds != null) c.wait_seconds += 30; }); renderList(); } }, 30000);

    connect();
    loadList().then(function () {
        var openId = @json($openId);
        if (openId) { openConversation(openId); return; }
        if (!state.items.length && Number($('#hdrWaiting').textContent) > 0) $('[data-tab="waiting"]').click();
    });
})();
</script>
@endpush
