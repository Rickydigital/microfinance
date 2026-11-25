@once
    @push('styles')
        <style>
            .admin-shell { --admin-ink: #17342f; --admin-green: #176b5b; --admin-soft: #eef6f1; }
            .admin-hero { background: linear-gradient(125deg, #123f37, #23826f); border-radius: 24px; color: #fff; padding: 1.5rem 1.75rem; box-shadow: 0 18px 42px rgba(18,63,55,.16); }
            .admin-hero h1 { color: #fff; font-family: Georgia, serif; }
            .admin-hero p { color: rgba(255,255,255,.78); }
            .admin-stat { border: 1px solid #dce9e2; border-radius: 18px; background: #fff; padding: 1rem 1.15rem; height: 100%; }
            .admin-stat span { color: #778b85; font-size: .76rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
            .admin-stat strong { display: block; color: var(--admin-ink); font-size: 1.55rem; margin-top: .25rem; }
            .admin-panel { border: 1px solid #dce7e1; border-radius: 22px; background: #fff; box-shadow: 0 12px 30px rgba(23,52,47,.07); overflow: hidden; }
            .admin-toolbar { background: #f8fbf9; border-bottom: 1px solid #e4ece7; padding: 1rem; }
            .admin-panel .table thead th { color: #71827d; background: #fbfcfb; border-bottom-color: #e5ece8; font-size: .74rem; letter-spacing: .06em; padding: .9rem 1rem; text-transform: uppercase; }
            .admin-panel .table td { border-color: #edf1ef; padding: 1rem; }
            .admin-action { width: 38px; height: 38px; border: 1px solid #cfe0d7; color: var(--admin-green); border-radius: 50%; display: inline-grid; place-items: center; background: #fff; }
            .admin-modal .modal-content { border: 2px solid #28a888; border-radius: 24px; overflow: hidden; }
            .admin-modal .modal-header, .admin-modal .modal-footer { border-color: #e6ece9; padding: 1.15rem 1.5rem; }
            .admin-modal .modal-body { padding: 1.5rem; max-height: 70vh; overflow-y: auto; }
            .admin-modal .form-control, .admin-modal .form-select { border-color: #cfddd5; border-radius: 12px; min-height: 46px; }
            .admin-modal .btn-primary { background: var(--admin-green); border-color: var(--admin-green); }
        </style>
    @endpush
@endonce
