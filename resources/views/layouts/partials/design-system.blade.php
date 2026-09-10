{{--
    Design system for the Confima customer portal.
    Concept: a "growth ledger" — the visual language of a trader's passbook,
    re-drawn for the web. Deep forest teal (trust, growth) + spice-market
    marigold (prosperity) on a soft sage paper. Amounts and reference
    numbers are always set in the mono face, like ledger entries.

    Signature element: the "journey bar" — repayment progress, application
    status, and KYC completion are all shown as a stitched path with a
    position marker, rather than a bare percentage. It reframes progress as
    a journey a client is walking, not a number they're being graded on.
--}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap');

    :root{
        --ink:#16241F;
        --ink-soft:#4B5A54;
        --ink-faint:#7C897F;
        --paper:#EFF2EA;
        --card:#FFFFFF;
        --line:#DCE3D5;
        --primary:#1F5B4E;
        --primary-deep:#0E2B24;
        --primary-tint:#E4EFE9;
        --accent:#E0A438;
        --accent-deep:#A8781F;
        --accent-tint:#FBF1DD;
        --danger:#C1502E;
        --danger-tint:#FBEAE3;
        --info:#2C6E8C;
        --info-tint:#E3EEF3;
        --radius-lg:18px;
        --radius:14px;
        --radius-sm:9px;
        --shadow: 0 1px 2px rgba(14,43,36,.05), 0 10px 28px -14px rgba(14,43,36,.22);
    }

    *{ box-sizing:border-box; }
    html{ -webkit-text-size-adjust:100%; }
    body{
        margin:0; background:var(--paper); color:var(--ink);
        font-family:'Public Sans', sans-serif; font-size:15px; line-height:1.55;
        -webkit-font-smoothing:antialiased;
    }
    h1,h2,h3,h4{ font-family:'Fraunces', serif; color:var(--ink); font-weight:600; margin:0; letter-spacing:-.01em; }
    a{ color:var(--primary); text-decoration:none; }
    small,.eyebrow{ font-size:11.5px; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-faint); font-weight:600; }
    .money, .mono{ font-family:'IBM Plex Mono', monospace; font-weight:600; letter-spacing:-.01em; }
    ::selection{ background:var(--accent-tint); color:var(--primary-deep); }
    :focus-visible{ outline:2.5px solid var(--accent); outline-offset:2px; }

    /* ---------- layout shell ---------- */
    .shell{ display:flex; min-height:100vh; }
    .sidebar{
        width:252px; flex-shrink:0; background:var(--primary-deep); color:#DCE9E4;
        display:flex; flex-direction:column; position:sticky; top:0; height:100vh; padding:22px 16px;
    }
    .brand{ display:flex; align-items:center; gap:10px; padding:6px 8px 22px; }
    .brand img{ width:32px; height:32px; object-fit:contain; border-radius:7px; background:#fff; padding:3px; }
    .brand-name{ font-family:'Fraunces',serif; font-size:16px; font-weight:600; color:#fff; line-height:1.15; }
    .brand-tag{ font-size:10px; color:#9FB8AF; text-transform:none; letter-spacing:0; font-weight:400; }

    .nav-group{ margin-bottom:4px; }
    .nav-link{
        display:flex; align-items:center; gap:11px; padding:10px 12px; border-radius:var(--radius-sm);
        color:#B9CFC7; font-size:13.5px; font-weight:500; margin-bottom:2px; transition:background .15s,color .15s;
    }
    .nav-link svg{ width:17px; height:17px; flex-shrink:0; opacity:.85; }
    .nav-link:hover{ background:rgba(255,255,255,.06); color:#fff; }
    .nav-link.active{ background:var(--accent); color:var(--primary-deep); font-weight:600; }
    .nav-link.active svg{ opacity:1; }

    .sidebar-foot{ margin-top:auto; padding-top:16px; border-top:1px solid rgba(255,255,255,.1); }
    .user-chip{ display:flex; align-items:center; gap:9px; padding:8px; }
    .avatar{
        width:34px; height:34px; border-radius:50%; background:var(--accent); color:var(--primary-deep);
        font-family:'Fraunces',serif; font-weight:700; font-size:13px; display:flex; align-items:center; justify-content:center; flex-shrink:0;
    }
    .user-chip-name{ color:#fff; font-size:13px; font-weight:600; line-height:1.3; }
    .user-chip-sub{ color:#9FB8AF; font-size:11px; }
    .logout-link{ display:flex; align-items:center; gap:8px; color:#9FB8AF; font-size:12.5px; margin-top:10px; padding:6px 8px; }
    .logout-link:hover{ color:#fff; }

    .main{ flex:1; min-width:0; }
    .topbar{
        display:flex; align-items:center; justify-content:space-between; padding:22px 34px;
        border-bottom:1px solid var(--line); background:var(--paper);
    }
    .topbar-greeting{ font-size:12.5px; color:var(--ink-faint); }
    .content{ padding:28px 34px 60px; max-width:1180px; }

    /* ---------- page head ---------- */
    .page-head{ display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:26px; flex-wrap:wrap; }
    .page-head h1{ font-size:26px; margin-bottom:5px; }
    .page-head p{ color:var(--ink-soft); margin:0; max-width:52ch; font-size:14px; }

    /* ---------- buttons ---------- */
    .btn{
        display:inline-flex; align-items:center; gap:7px; font-family:'Public Sans',sans-serif; font-weight:600; font-size:13.5px;
        padding:11px 20px; border-radius:999px; border:1.5px solid transparent; cursor:pointer; transition:transform .12s,box-shadow .12s,background .12s;
        white-space:nowrap;
    }
    .btn:hover{ transform:translateY(-1px); }
    .btn-accent{ background:var(--accent); color:var(--primary-deep); box-shadow:0 6px 16px -8px rgba(224,164,56,.7); }
    .btn-accent:hover{ background:#EDB456; color:var(--primary-deep); }
    .btn-primary{ background:var(--primary); color:#fff; }
    .btn-primary:hover{ background:var(--primary-deep); color:#fff; }
    .btn-ghost{ background:transparent; border-color:var(--line); color:var(--ink); }
    .btn-ghost:hover{ border-color:var(--primary); color:var(--primary); }
    .btn-link{ background:none; border:none; color:var(--primary); font-weight:600; font-size:13.5px; padding:0; }
    .btn:disabled{ opacity:.45; cursor:not-allowed; transform:none; }
    .btn svg{ width:15px; height:15px; }

    /* ---------- cards / grid ---------- */
    .grid{ display:grid; gap:16px; }
    .grid-3{ grid-template-columns:repeat(3,1fr); }
    .grid-4{ grid-template-columns:repeat(4,1fr); }
    .grid-2{ grid-template-columns:repeat(2,1fr); }
    @media (max-width:920px){ .grid-3,.grid-4{ grid-template-columns:repeat(2,1fr); } }
    @media (max-width:640px){ .grid-3,.grid-4,.grid-2{ grid-template-columns:1fr; } }

    .card{ background:var(--card); border:1px solid var(--line); border-radius:var(--radius-lg); box-shadow:var(--shadow); }
    .card-pad{ padding:22px 24px; }
    .stat-card{ background:var(--card); border:1px solid var(--line); border-radius:var(--radius); padding:18px 20px; box-shadow:var(--shadow); }
    .stat-card .eyebrow{ display:block; margin-bottom:6px; }
    .stat-card strong{ font-family:'Fraunces',serif; font-size:24px; font-weight:600; display:block; }
    .stat-card .stat-hint{ font-size:11.5px; color:var(--ink-faint); margin-top:4px; }

    .section-title{ font-size:12px; text-transform:uppercase; letter-spacing:.06em; color:var(--ink-faint); font-weight:700; margin:30px 0 12px; }

    /* ---------- journey bar (signature element) ---------- */
    .journey{ position:relative; height:6px; border-radius:999px; margin:14px 2px 6px;
        background:repeating-linear-gradient(90deg, var(--line) 0 6px, transparent 6px 10px); }
    .journey-fill{ position:absolute; top:0; left:0; height:100%; border-radius:999px; background:linear-gradient(90deg,var(--primary),var(--accent)); }
    .journey-marker{ position:absolute; top:50%; width:15px; height:15px; border-radius:50%; background:var(--card); border:3px solid var(--primary); transform:translate(-50%,-50%); box-shadow:0 0 0 3px var(--card); }
    .journey-labels{ display:flex; justify-content:space-between; font-size:10.5px; color:var(--ink-faint); margin-top:6px; }

    .stepper{ display:flex; align-items:flex-start; gap:0; margin:18px 0 8px; }
    .step{ flex:1; text-align:center; position:relative; }
    .step:not(:last-child):after{ content:''; position:absolute; top:12px; left:56%; width:88%; height:2px; background:var(--line); }
    .step.done:not(:last-child):after{ background:var(--primary); }
    .step-dot{ width:24px; height:24px; border-radius:50%; background:var(--card); border:2px solid var(--line); margin:0 auto 8px; display:flex; align-items:center; justify-content:center; position:relative; z-index:1; }
    .step.done .step-dot{ background:var(--primary); border-color:var(--primary); }
    .step.current .step-dot{ background:var(--accent); border-color:var(--accent); }
    .step-dot svg{ width:12px; height:12px; stroke:#fff; }
    .step-label{ font-size:11px; font-weight:600; color:var(--ink-soft); }
    .step.done .step-label,.step.current .step-label{ color:var(--ink); }

    /* ---------- pills / badges ---------- */
    .pill{ display:inline-flex; align-items:center; gap:5px; padding:3px 11px; border-radius:999px; font-size:11px; font-weight:700; letter-spacing:.02em; text-transform:capitalize; }
    .pill:before{ content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
    .pill--good{ background:var(--primary-tint); color:var(--primary); }
    .pill--warn{ background:var(--accent-tint); color:var(--accent-deep); }
    .pill--bad{ background:var(--danger-tint); color:var(--danger); }
    .pill--info{ background:var(--info-tint); color:var(--info); }
    .pill--muted{ background:var(--paper); color:var(--ink-faint); border:1px solid var(--line); }

    /* ---------- tables ---------- */
    .table-wrap{ overflow-x:auto; }
    table.ledger{ width:100%; border-collapse:collapse; font-size:13.5px; }
    table.ledger th{ text-align:left; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; color:var(--ink-faint); font-weight:700; padding:0 14px 10px; border-bottom:1px solid var(--line); }
    table.ledger td{ padding:14px; border-bottom:1px solid var(--line); vertical-align:middle; }
    table.ledger tr:last-child td{ border-bottom:none; }
    table.ledger tr:hover td{ background:var(--primary-tint); }
    table.ledger a.ref{ font-family:'IBM Plex Mono',monospace; font-weight:600; color:var(--primary); }

    /* ---------- forms ---------- */
    .field{ margin-bottom:16px; }
    .field label{ display:block; font-size:12px; font-weight:700; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
    .field .hint{ font-size:12px; color:var(--ink-faint); margin-top:5px; text-transform:none; letter-spacing:0; font-weight:400; }
    .field .hint.error{ color:var(--danger); font-weight:600; }
    .input, select.input, textarea.input{
        width:100%; padding:12px 13px; border:1.5px solid var(--line); border-radius:var(--radius-sm);
        font-family:'Public Sans',sans-serif; font-size:14px; color:var(--ink); background:var(--card);
        transition:border-color .12s, box-shadow .12s;
    }
    .input:focus, select.input:focus, textarea.input:focus{ border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-tint); outline:none; }
    .check-row{ display:flex; align-items:center; gap:8px; font-size:13px; color:var(--ink-soft); }

    /* ---------- empty state ---------- */
    .empty{ text-align:center; padding:52px 24px; color:var(--ink-soft); }
    .empty svg{ width:44px; height:44px; stroke:var(--primary); opacity:.55; margin-bottom:14px; }
    .empty h3{ font-size:17px; margin-bottom:6px; }
    .empty p{ font-size:13.5px; max-width:38ch; margin:0 auto 18px; color:var(--ink-faint); }

    /* ---------- alerts ---------- */
    .alert{ border-radius:var(--radius-sm); padding:13px 16px; font-size:13.5px; margin-bottom:18px; border:1px solid transparent; }
    .alert-danger{ background:var(--danger-tint); color:#7A2F19; border-color:#F1C3B0; }
    .alert-info{ background:var(--info-tint); color:#194A5F; border-color:#BFDCE8; }

    /* ---------- auth split screen ---------- */
    .auth-shell{ min-height:100vh; display:flex; }
    .auth-brand{
        width:44%; background:var(--primary-deep); color:#DCE9E4; padding:50px 46px; display:flex; flex-direction:column;
        justify-content:space-between; position:relative; overflow:hidden;
    }
    .auth-brand:before{
        content:''; position:absolute; inset:0; opacity:.5;
        background-image:radial-gradient(circle at 85% 12%, rgba(224,164,56,.16), transparent 40%),
                          radial-gradient(circle at 10% 85%, rgba(224,164,56,.10), transparent 45%);
    }
    .auth-brand > *{ position:relative; z-index:1; }
    .auth-brand .brand{ padding:0; margin-bottom:0; }
    .auth-headline{ font-size:30px; line-height:1.2; margin:34px 0 16px; max-width:15ch; }
    .auth-sub{ color:#B9CFC7; font-size:14px; max-width:36ch; margin-bottom:26px; }
    .auth-points{ display:flex; flex-direction:column; gap:14px; }
    .auth-point{ display:flex; gap:10px; align-items:flex-start; font-size:13px; color:#DCE9E4; }
    .auth-point svg{ width:16px; height:16px; flex-shrink:0; margin-top:2px; stroke:var(--accent); }
    .auth-foot{ font-size:11.5px; color:#7FA096; }
    .auth-form-side{ flex:1; display:flex; align-items:center; justify-content:center; padding:40px 24px; background:var(--paper); }
    .auth-card{ width:100%; max-width:480px; }
    .auth-card .eyebrow{ color:var(--primary); }
    .auth-card h1{ font-size:25px; margin:6px 0 8px; }
    .auth-card > p{ color:var(--ink-soft); font-size:13.5px; margin:0 0 26px; }

    @media (max-width:900px){
        .shell{ flex-direction:column; }
        .sidebar{ position:relative; height:auto; width:100%; flex-direction:row; align-items:center; padding:14px 16px; }
        .sidebar-foot{ margin-top:0; margin-left:auto; padding-top:0; border-top:none; }
        .nav-group{ display:flex; gap:2px; }
        .content{ padding:22px 18px 50px; }
        .topbar{ padding:16px 18px; }
        .auth-shell{ flex-direction:column; }
        .auth-brand{ width:100%; padding:32px 24px; }
    }
</style>