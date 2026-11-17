<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Institution Identity
    |--------------------------------------------------------------------------
    */
    'institution_name' => env('MFI_INSTITUTION_NAME', 'MyCash microfinance'),
    'currency' => env('MFI_CURRENCY', 'TZS'),

    /*
    |--------------------------------------------------------------------------
    | PDF / Document Branding
    |--------------------------------------------------------------------------
    | Used by resources/views/pdf/layout.blade.php (header, footer, receipts).
    */
    'tagline' => env('MFI_TAGLINE', 'Empowering enterprise, one loan at a time'),

    'phone' => env('MFI_PHONE', '+255 700 000 000'),
    'email' => env('MFI_EMAIL', 'info@mycash.co.tz'),
    'website' => env('MFI_WEBSITE', 'www.mycash.co.tz'),
    'address' => env('MFI_ADDRESS', 'Plot 12, Samora Avenue, Dar es Salaam, Tanzania'),

    'registration_number' => env('MFI_REGISTRATION_NUMBER', null),
    'tin_number' => env('MFI_TIN_NUMBER', null),
    'license_number' => env('MFI_LICENSE_NUMBER', null),

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    | Physical file must exist at public/images/logo.png (or override below).
    | Read directly and base64-inlined by the PDF layout for dompdf reliability.
    */
    'logo_path' => env('MFI_LOGO_PATH', public_path('images/logo.png')),
    'secondary_logo_path' => env('MFI_SECONDARY_LOGO_PATH', public_path('images/logo-secondary.png')),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'email_enabled' => env('CUSTOMER_EMAIL_NOTIFICATIONS', true),
        'sms_enabled' => env('CUSTOMER_SMS_NOTIFICATIONS', true),
        'loan_reminder_days' => array_map('intval', explode(',', env('LOAN_REMINDER_DAYS', '30,14,7,3,1,0'))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'institution' => ['code' => 'MYCASH', 'name' => 'MyCash microfinance', 'currency' => 'TZS', 'timezone' => 'Africa/Dar_es_Salaam'],
        'branch' => ['code' => 'HO', 'name' => 'Head Office'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        ['label' => 'Dashboard', 'icon' => 'mdi-view-dashboard-outline', 'route' => 'dashboard', 'permission' => 'dashboard.view'],
        ['label' => 'Customer management', 'icon' => 'mdi-account-group-outline', 'items' => [
            ['label' => 'All customers', 'route' => 'customers.index', 'permission' => 'customer.view'],
            ['label' => 'Guarantors', 'route' => 'guarantors.index', 'permission' => 'guarantor.view'],
            ['label' => 'Collateral', 'route' => 'collaterals.index', 'permission' => 'collateral.view'],
        ]],
        ['label' => 'Loan management', 'icon' => 'mdi-cash-multiple', 'items' => [
            ['label' => 'Applications', 'route' => 'loan-applications.index', 'permission' => 'loan.application.view'],
            ['label' => 'Approval queue', 'route' => 'loan-approvals.index', 'permission' => 'loan.approval.view'],
            ['label' => 'Disbursements', 'route' => 'loan-disbursements.index', 'permission' => 'loan.disbursement.view'],
            ['label' => 'Active loans', 'route' => 'loans.index', 'permission' => 'loan.view'],
            ['label' => 'Loan products', 'route' => 'loan-products.index', 'permission' => 'loan-product.view'],
            ['label' => 'Loan calculator', 'route' => 'loan-calculator.index', 'permission' => 'loan.application.view'],
        ]],
        ['label' => 'Loan calendar', 'icon' => 'mdi-calendar-month-outline', 'route' => 'loan-operations.calendar', 'permission' => 'loan.view'],
        ['label' => 'Loan ageing', 'icon' => 'mdi-timeline-alert-outline', 'route' => 'loan-operations.ageing', 'permission' => 'loan.view'],
        ['label' => 'BOT Returns', 'icon' => 'mdi-bank-outline', 'route' => 'bot-returns.index', 'permission' => 'report.portfolio'],
        ['label' => 'Collections & repayments', 'icon' => 'mdi-cash-sync', 'items' => [
            ['label' => 'Collection cases', 'route' => 'collection-cases.index', 'permission' => 'collection.case.view'],
            ['label' => 'Repayment records', 'route' => 'repayments.index', 'permission' => 'repayment.view'],
            ['label' => 'Payment approvals', 'route' => 'payment-submissions.index', 'permission' => 'repayment.approve'],
            ['label' => 'Till operations', 'route' => 'till-sessions.index', 'permission' => 'till.view'],
        ]],
        ['label' => 'Finance', 'icon' => 'mdi-book-open-page-variant', 'items' => [
            ['label' => 'Chart of accounts', 'route' => 'finance.accounts.index', 'permission' => 'accounting.account.view'],
            ['label' => 'Journal entries', 'route' => 'finance.journals.index', 'permission' => 'accounting.journal.view'],
        ]],
        ['label' => 'Reports', 'icon' => 'mdi-chart-box-outline', 'items' => [
            ['label' => 'Regulatory Reports', 'route' => 'reports.index', 'parameters' => ['report' => 'bot-periodic-return'], 'permission' => 'report.portfolio'],
            ['label' => 'Financial Reports', 'route' => 'reports.index', 'parameters' => ['report' => 'balance-sheet'], 'permission' => 'report.financial'],
            ['label' => 'Loan Reports', 'route' => 'reports.index', 'parameters' => ['report' => 'portfolio-summary'], 'permission' => 'report.portfolio'],
            ['label' => 'Customer Reports', 'route' => 'reports.index', 'parameters' => ['report' => 'customer-summary'], 'permission' => 'report.operational'],
            ['label' => 'Operational Reports', 'route' => 'reports.index', 'parameters' => ['report' => 'branch-performance'], 'permission' => 'report.operational'],
            ['label' => 'Audit & Compliance', 'route' => 'reports.index', 'parameters' => ['report' => 'audit-trail'], 'permission' => 'report.audit'],
        ]],
        ['label' => 'Administration', 'icon' => 'mdi-cog-outline', 'items' => [
            ['label' => 'Approval rules', 'route' => 'approval-rules.index', 'permission' => 'loan.approval.view'],
            ['label' => 'Employees', 'route' => 'users.index', 'permission' => 'admin.user.view'],
            ['label' => 'Roles & permissions', 'route' => 'roles.index', 'permission' => 'admin.role.view'],
            ['label' => 'Branches', 'route' => 'branches.index', 'permission' => 'admin.branch.view'],
            ['label' => 'Settings', 'route' => 'settings.index', 'permission' => 'admin.configuration.view'],
            ['label' => 'Reference data', 'route' => 'reference-data.index', 'permission' => 'admin.configuration.update'],
        ]],
    ],

    'reports' => [
        'regulatory' => [
            'label' => 'Regulatory Reports',
            'permission' => 'report.portfolio',
            'reports' => [
                'bot-periodic-return' => ['label' => 'BoT Periodic Return', 'dataset' => 'portfolio'],
                'loan-portfolio-return' => ['label' => 'Loan Portfolio Return', 'dataset' => 'portfolio'],
                'portfolio-quality-par' => ['label' => 'Portfolio Quality / PAR', 'dataset' => 'risk'],
                'npl-return' => ['label' => 'NPL Return', 'dataset' => 'risk'],
                'capital-position' => ['label' => 'Capital Position', 'dataset' => 'financial'],
                'customer-borrower-statistics' => ['label' => 'Customer/Borrower Statistics', 'dataset' => 'operational'],
                'complaints-return' => ['label' => 'Complaints Return', 'dataset' => 'collections'],
                'credit-reference-crb' => ['label' => 'Credit Reference / CRB', 'dataset' => 'audit'],
            ],
        ],
        'financial' => [
            'label' => 'Financial Reports',
            'permission' => 'report.financial',
            'reports' => [
                'balance-sheet' => ['label' => 'Balance Sheet', 'dataset' => 'financial'],
                'income-statement' => ['label' => 'Income Statement', 'dataset' => 'financial'],
                'cash-flow-statement' => ['label' => 'Cash Flow Statement', 'dataset' => 'financial'],
                'trial-balance' => ['label' => 'Trial Balance', 'dataset' => 'financial'],
                'general-ledger' => ['label' => 'General Ledger', 'dataset' => 'financial'],
            ],
        ],
        'loan' => [
            'label' => 'Loan Reports',
            'permission' => 'report.portfolio',
            'reports' => [
                'portfolio-summary' => ['label' => 'Portfolio Summary', 'dataset' => 'portfolio'],
                'active-loans' => ['label' => 'Active Loans', 'dataset' => 'portfolio'],
                'disbursements' => ['label' => 'Disbursements', 'dataset' => 'portfolio'],
                'repayments' => ['label' => 'Repayments', 'dataset' => 'collections'],
                'outstanding-loans' => ['label' => 'Outstanding Loans', 'dataset' => 'portfolio'],
                'arrears' => ['label' => 'Arrears', 'dataset' => 'risk'],
                'par' => ['label' => 'PAR', 'dataset' => 'risk'],
                'npl' => ['label' => 'NPL', 'dataset' => 'risk'],
                'write-offs' => ['label' => 'Write-offs', 'dataset' => 'risk'],
                'loan-aging' => ['label' => 'Loan Aging', 'dataset' => 'risk'],
            ],
        ],
        'customer' => [
            'label' => 'Customer Reports',
            'permission' => 'report.operational',
            'reports' => [
                'customer-summary' => ['label' => 'Customer Summary', 'dataset' => 'operational'],
                'active-borrowers' => ['label' => 'Active Borrowers', 'dataset' => 'portfolio'],
                'gender' => ['label' => 'Gender', 'dataset' => 'operational'],
                'age' => ['label' => 'Age', 'dataset' => 'operational'],
                'location' => ['label' => 'Location', 'dataset' => 'operational'],
                'kyc-status' => ['label' => 'KYC Status', 'dataset' => 'operational'],
            ],
        ],
        'operational' => [
            'label' => 'Operational Reports',
            'permission' => 'report.operational',
            'reports' => [
                'branch-performance' => ['label' => 'Branch Performance', 'dataset' => 'operational'],
                'loan-officer-performance' => ['label' => 'Loan Officer Performance', 'dataset' => 'operational'],
                'product-performance' => ['label' => 'Product Performance', 'dataset' => 'operational'],
                'collection-performance' => ['label' => 'Collection Performance', 'dataset' => 'collections'],
                'complaints' => ['label' => 'Complaints', 'dataset' => 'collections'],
            ],
        ],
        'audit-compliance' => [
            'label' => 'Audit & Compliance',
            'permission' => 'report.audit',
            'reports' => [
                'audit-trail' => ['label' => 'Audit Trail', 'dataset' => 'audit'],
                'approval-history' => ['label' => 'Approval History', 'dataset' => 'audit'],
                'reversed-transactions' => ['label' => 'Reversed Transactions', 'dataset' => 'audit'],
                'kyc-exceptions' => ['label' => 'KYC Exceptions', 'dataset' => 'audit'],
                'crb-submission' => ['label' => 'CRB Submission', 'dataset' => 'audit'],
            ],
        ],
    ],

];
