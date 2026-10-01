{{-- Shared look for the Role Management pages (ported from the CSS Kabba portal). --}}
@once
<style>
:root {
    --rol-primary:  #0f172a;
    --rol-accent:   #6366f1;
    --rol-accent2:  #8b5cf6;
    --rol-success:  #10b981;
    --rol-warning:  #f59e0b;
    --rol-danger:   #ef4444;
    --rol-info:     #0ea5e9;
    --rol-border:   #e2e8f0;
    --rol-surface:  #ffffff;
    --rol-surface2: #f8fafc;
    --rol-muted:    #64748b;
    --rol-radius:   14px;
    --rol-shadow:   0 4px 20px rgba(15,23,42,.07);
}
[data-bs-theme="dark"] {
    --rol-primary:  #e2e8f0;
    --rol-border:   #2b3446;
    --rol-surface:  #1e2432;
    --rol-surface2: #242b3b;
    --rol-muted:    #94a3b8;
}

@keyframes rolSlideDown { from{opacity:0;transform:translateY(-22px)} to{opacity:1;transform:translateY(0)} }
@keyframes rolFadeUp    { from{opacity:0;transform:translateY(14px)}  to{opacity:1;transform:translateY(0)} }
@keyframes rolScaleIn   { from{opacity:0;transform:scale(.9)}         to{opacity:1;transform:scale(1)} }
@keyframes rolPulse     { 0%,100%{transform:scale(1)} 50%{transform:scale(1.06)} }

/* ── Hero ────────────────────────────────────────── */
.rol-hero {
    background: linear-gradient(135deg,#0f172a 0%,#1e1b4b 45%,#312e81 100%);
    border-radius: var(--rol-radius); padding: 30px 36px; margin-bottom: 24px;
    position: relative; overflow: hidden;
    animation: rolSlideDown .5s cubic-bezier(.22,1,.36,1);
}
.rol-hero::before { content:''; position:absolute; top:-80px; right:-80px; width:260px; height:260px; background:rgba(99,102,241,.14); border-radius:50%; animation:rolPulse 4s ease-in-out infinite; }
.rol-hero::after  { content:''; position:absolute; bottom:-50px; left:32%; width:160px; height:160px; background:rgba(139,92,246,.1); border-radius:50%; animation:rolPulse 4s ease-in-out infinite 2s; }
.rol-hero h1 { font-size:24px; font-weight:800; color:#fff; margin:0 0 6px; position:relative; z-index:1; letter-spacing:-.3px; }
.rol-hero p  { font-size:13.5px; color:rgba(255,255,255,.65); margin:0; position:relative; z-index:1; }
.rol-hero-actions { position:relative; z-index:1; margin-top:20px; display:flex; gap:8px; flex-wrap:wrap; }

/* ── Buttons ─────────────────────────────────────── */
.rol-btn { display:inline-flex; align-items:center; gap:6px; border:none; border-radius:9px; padding:9px 18px; font-size:13px; font-weight:600; cursor:pointer; transition:transform .15s,box-shadow .15s; text-decoration:none; }
.rol-btn-primary, .rol-btn.rol-btn-primary { background:linear-gradient(135deg,var(--rol-accent),var(--rol-accent2)); color:#fff; border:none; border-radius:9px; padding:9px 18px; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; box-shadow:0 3px 12px rgba(99,102,241,.3); text-decoration:none; cursor:pointer; transition:transform .15s,box-shadow .15s; }
.rol-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(99,102,241,.4); color:#fff; }
.rol-btn-success { background:linear-gradient(135deg,#10b981,#059669); color:#fff; box-shadow:0 3px 12px rgba(16,185,129,.3); }
.rol-btn-success:hover { transform:translateY(-1px); color:#fff; }
.rol-btn-danger  { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; box-shadow:0 3px 12px rgba(239,68,68,.3); }
.rol-btn-danger:hover  { transform:translateY(-1px); color:#fff; }
.rol-btn-back    { background:#fff; color:#0f172a; border:1.5px solid #e2e8f0; }
.rol-btn-back:hover { background:#f8fafc; color:#0f172a; }

/* ── Role cards (index) ──────────────────────────── */
.rol-card { background:var(--rol-surface); border:1px solid var(--rol-border); border-radius:var(--rol-radius); overflow:hidden; box-shadow:var(--rol-shadow); transition:transform .2s, box-shadow .2s, border-color .2s; animation:rolFadeUp .4s ease both; display:flex; flex-direction:column; height:100%; }
.rol-card.hoverable:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(15,23,42,.12); border-color:#c7d2fe; }
.rol-card-top { background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 80%,#312e81 100%); padding:20px 18px 16px; position:relative; overflow:hidden; }
.rol-card-top::before { content:''; position:absolute; top:-30px; right:-30px; width:100px; height:100px; background:rgba(99,102,241,.18); border-radius:50%; }
.rol-card-icon { width:46px; height:46px; border-radius:12px; background:rgba(255,255,255,.14); display:flex; align-items:center; justify-content:center; font-size:20px; color:#fff; margin-bottom:12px; position:relative; z-index:1; }
.rol-card-name { font-size:16px; font-weight:800; color:#fff; position:relative; z-index:1; margin-bottom:4px; }
.rol-card-count { font-size:12px; color:rgba(255,255,255,.65); position:relative; z-index:1; display:flex; align-items:center; gap:5px; }
.rol-card-count strong { color:rgba(255,255,255,.9); font-size:18px; }
.rol-card-body { padding:14px 18px; flex:1; }
.rol-perm-tag { display:inline-flex; align-items:center; gap:4px; background:#f0f4ff; color:#4338ca; border:1px solid #c7d2fe; border-radius:20px; padding:3px 10px; font-size:11px; font-weight:600; margin:2px; }
.rol-perm-more { color:var(--rol-accent); font-size:11.5px; font-weight:600; text-decoration:none; }
.rol-perm-more:hover { text-decoration:underline; }
.rol-card-footer { padding:12px 18px; border-top:1px solid var(--rol-border); background:var(--rol-surface2); display:flex; align-items:center; justify-content:space-between; }
.rol-users-badge { display:inline-flex; align-items:center; gap:5px; background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; border-radius:20px; padding:3px 12px; font-size:11.5px; font-weight:600; }
.rol-view-link { font-size:12.5px; font-weight:600; color:var(--rol-accent); text-decoration:none; display:flex; align-items:center; gap:4px; }
.rol-view-link:hover { color:var(--rol-accent2); }

/* ── Card header variant (show page) ─────────────── */
.rol-card-header { padding:14px 20px; border-bottom:1px solid var(--rol-border); background:var(--rol-surface); display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
.rol-card-header h5 { font-size:14px; font-weight:700; color:var(--rol-primary); margin:0; }
.rol-count { background:var(--rol-accent); color:#fff; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:700; margin-left:6px; }

/* ── Dropdown ────────────────────────────────────── */
.rol-dropdown .dropdown-toggle { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); color:#fff; border-radius:8px; padding:5px 9px; font-size:14px; }
.rol-dropdown .dropdown-toggle::after { display:none; }
.rol-dropdown .dropdown-toggle:hover { background:rgba(255,255,255,.25); }
.rol-dropdown .dropdown-menu { border-radius:10px; border:1px solid var(--rol-border); box-shadow:0 8px 24px rgba(15,23,42,.12); padding:6px; animation:rolScaleIn .15s ease; }
.rol-dropdown .dropdown-item { border-radius:7px; font-size:13px; font-weight:500; padding:8px 12px; display:flex; align-items:center; gap:8px; }
.rol-dropdown .dropdown-item:hover { background:#f0f4ff; color:var(--rol-accent); }
.rol-dropdown .dropdown-item.text-danger:hover { background:#fef2f2; color:var(--rol-danger); }

/* ── Permission list (show sidebar) ──────────────── */
.perm-list-item { display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; font-size:12.5px; font-weight:500; color:var(--rol-primary); margin-bottom:2px; }
.perm-list-item:hover { background:#f0f4ff; }
.perm-list-item i { color:var(--rol-accent); font-size:13px; flex-shrink:0; }
.perm-list-scroll { max-height:560px; overflow-y:auto; }
.edit-role-btn { width:100%; display:flex; align-items:center; justify-content:center; gap:6px; background:#f0f4ff; color:var(--rol-accent); border:1.5px solid #c7d2fe; border-radius:9px; padding:9px; font-size:13px; font-weight:600; cursor:pointer; transition:all .15s; margin-bottom:16px; }
.edit-role-btn:hover { background:var(--rol-accent); color:#fff; }

/* ── Modals ──────────────────────────────────────── */
.rol-modal .modal-content { border:none; border-radius:18px; overflow:hidden; box-shadow:0 24px 64px rgba(15,23,42,.2); animation:rolScaleIn .25s cubic-bezier(.22,1,.36,1); max-height:90vh; }
.rol-modal .modal-content form { display:flex; flex-direction:column; min-height:0; overflow:hidden; }
.rol-modal .modal-header { background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 60%,#312e81 100%); padding:20px 24px; border:none; flex:0 0 auto; }
.rol-modal .modal-title { color:#fff; font-weight:700; font-size:16px; }
.rol-modal .modal-header .btn-close { filter:invert(1); opacity:.8; }
.rol-modal .modal-body { padding:22px 24px; overflow-y:auto; flex:1 1 auto; min-height:0; }
.rol-modal .modal-footer { padding:14px 24px; border-top:1px solid var(--rol-border); background:var(--rol-surface2); flex:0 0 auto; }
.rol-form-label { font-size:11.5px; font-weight:700; color:var(--rol-muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; display:block; }
.rol-form-control { border:1.5px solid var(--rol-border); border-radius:9px; padding:9px 13px; font-size:13px; width:100%; background:var(--rol-surface); color:inherit; transition:border-color .2s, box-shadow .2s; }
.rol-form-control:focus { border-color:var(--rol-accent); box-shadow:0 0 0 3px rgba(99,102,241,.12); outline:none; }
.rol-info-banner { background:#eff6ff; border:1px solid #bfdbfe; border-radius:9px; padding:10px 14px; font-size:12.5px; color:#1d4ed8; margin-bottom:14px; }

/* ── Alerts / empty state ────────────────────────── */
.rol-alert-success { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:9px; padding:10px 14px; font-size:13px; color:#16a34a; }
.rol-alert-danger  { background:#fef2f2; border:1px solid #fecaca; border-radius:9px; padding:10px 14px; font-size:13px; color:#b91c1c; }
.rol-empty { text-align:center; padding:60px 24px; animation:rolFadeUp .5s ease; }
.rol-empty-icon { font-size:56px; opacity:.2; margin-bottom:14px; display:block; }
.rol-empty h4 { font-weight:700; color:var(--rol-muted); font-size:17px; }
.rol-empty p  { font-size:13px; color:var(--rol-muted); }

/* Select2 inside the add-user modal */
#addUserModalgrid .select2-container { width:100% !important; }
#addUserModalgrid .select2-container--default .select2-selection--multiple { border:1.5px solid var(--rol-border); border-radius:9px; min-height:46px; padding:4px; }
#addUserModalgrid .select2-container--default .select2-selection--multiple .select2-selection__choice { background:#eef2ff; border:1px solid #c7d2fe; color:#3730a3; border-radius:20px; padding:2px 10px 2px 22px; font-size:12px; }
.select2-container--open .select2-dropdown { z-index:2000; }
</style>
@endonce
