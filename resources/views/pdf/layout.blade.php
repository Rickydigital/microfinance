<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    /*
     * Institution details are pulled from config/microfinance.php.
     * Add these keys if they don't already exist:
     *   'institution_name' => 'Trust Microfinance Ltd',
     *   'tagline'           => 'Empowering enterprise, one loan at a time',
     *   'phone'             => '+255 700 000 000',
     *   'email'             => 'info@trustmfi.co.tz',
     *   'address'           => 'Plot 12, Samora Avenue, Dar es Salaam, Tanzania',
     *   'website'           => 'www.trustmfi.co.tz',
     *   'currency'          => 'TZS',
     * Logo file expected at: public/images/logo.png (recommended min 300x300px, transparent PNG)
     */
    @page { margin: 40mm 14mm 18mm 14mm; }

    * { box-sizing: border-box; }

    body {
        font-family: "DejaVu Sans", sans-serif;
        font-size: 9px;
        color: #1f2937;
        line-height: 1.45;
    }

    h1 { font-size: 18px; margin: 0 0 2px; color: #0f2942; letter-spacing: .3px; }
    h2 {
        font-size: 11.5px;
        margin: 16px 0 6px;
        color: #0f2942;
        text-transform: uppercase;
        letter-spacing: .6px;
        border-bottom: 1.5px solid #c9a227;
        padding-bottom: 4px;
    }

    /* ---------- fixed running header ---------- */
    #page-header {
        position: fixed;
        top: -32mm;
        left: 0;
        right: 0;
        height: 30mm;
    }
    .brand-table { width: 100%; border-collapse: collapse; }
    .brand-table td { border: none; padding: 0; vertical-align: middle; }
    .brand-logo-cell { width: 20mm; }
    .brand-logo-cell img { width: 18mm; height: 18mm; object-fit: contain; }
    .brand-name-cell { padding-left: 8px; }
    .brand-name { font-size: 16px; font-weight: bold; color: #0f2942; margin: 0; }
    .brand-tagline { font-size: 8px; color: #64748b; font-style: italic; margin: 1px 0 4px; }
    .brand-contact { font-size: 7.5px; color: #475569; }
    .brand-contact span { margin-right: 10px; white-space: nowrap; }
    .brand-contact span:last-child { margin-right: 0; }

    .header-rule {
        margin-top: 8px;
        border-top: 2.5px solid #0f2942;
        border-bottom: 1px solid #c9a227;
        height: 0;
        font-size: 0;
        line-height: 0;
    }

    .doc-title-band {
        margin-top: 8px;
        width: 100%;
        border-collapse: collapse;
    }
    .doc-title-band td { border: none; padding: 0; vertical-align: bottom; }
    .doc-title-text { font-size: 12px; font-weight: bold; color: #0f2942; }
    .doc-meta { font-size: 7.5px; color: #64748b; text-align: right; }
    .doc-tag {
        display: inline-block;
        font-size: 6.5px;
        font-weight: bold;
        letter-spacing: .5px;
        color: #ffffff;
        background: #0f2942;
        padding: 2px 6px;
        border-radius: 2px;
        text-transform: uppercase;
    }

    /* ---------- fixed running footer ---------- */
    #page-footer {
        position: fixed;
        bottom: -14mm;
        left: 0;
        right: 0;
        height: 12mm;
        border-top: .75px solid #cbd5e1;
        padding-top: 4px;
    }
    .footer-table { width: 100%; border-collapse: collapse; }
    .footer-table td { border: none; padding: 0; font-size: 7.5px; color: #64748b; }
    .footer-left { text-align: left; }
    .footer-center { text-align: center; }
    .footer-right { text-align: right; }
    .footer-page:after { content: counter(page) " / " counter(pages); }

    /* ---------- shared content styles ---------- */
    .grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .grid th, .grid td { border: 1px solid #cbd5e1; padding: 5px; text-align: left; vertical-align: top; }
    .grid th { background: #eef2f6; color: #0f2942; text-transform: uppercase; font-size: 8px; letter-spacing: .3px; }
    .grid tr:nth-child(even) td { background: #fafbfc; }
    .number { text-align: right !important; font-family: "DejaVu Sans Mono", monospace; }

    .summary { width: 100%; margin: 8px 0; border-collapse: collapse; }
    .summary td {
        padding: 8px 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 2px;
    }
    .summary strong { display: block; font-size: 7px; text-transform: uppercase; letter-spacing: .4px; color: #64748b; margin-bottom: 2px; }

    .badge { display: inline-block; padding: 2px 7px; border-radius: 9px; font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .3px; }
    .badge-green  { background: #dcfce7; color: #166534; }
    .badge-red    { background: #fee2e2; color: #991b1b; }
    .badge-amber  { background: #fef3c7; color: #92400e; }
    .badge-blue   { background: #dbeafe; color: #1e40af; }
    .badge-gray   { background: #e2e8f0; color: #475569; }

    .page-break { page-break-before: always; }
</style>
</head>
<body>

<div id="page-header">
    @php
        $logoPath = public_path('images/logo.png');
        $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
    @endphp
    <table class="brand-table">
        <tr>
            <td class="brand-logo-cell">
                @if($logoData)
                    <img src="data:image/png;base64,{{ $logoData }}" alt="Logo">
                @endif
            </td>
            <td class="brand-name-cell">
                <p class="brand-name">{{ config('microfinance.institution_name') }}</p>
                @if(config('microfinance.tagline'))
                    <p class="brand-tagline">{{ config('microfinance.tagline') }}</p>
                @endif
                <div class="brand-contact">
                    @if(config('microfinance.phone'))<span>Tel: {{ config('microfinance.phone') }}</span>@endif
                    @if(config('microfinance.email'))<span>Email: {{ config('microfinance.email') }}</span>@endif
                    @if(config('microfinance.website'))<span>{{ config('microfinance.website') }}</span>@endif
                    @if(config('microfinance.address'))<span>{{ config('microfinance.address') }}</span>@endif
                </div>
            </td>
        </tr>
    </table>
    <div class="header-rule"></div>
    <table class="doc-title-band">
        <tr>
            <td><span class="doc-title-text">@yield('document-title')</span> &nbsp; <span class="doc-tag">@yield('document-tag', 'Official Document')</span></td>
            <td class="doc-meta">Generated {{ now()->format('d M Y, H:i') }}<br>@yield('document-ref', '')</td>
        </tr>
    </table>
</div>

<div id="page-footer">
    <table class="footer-table">
        <tr>
            <td class="footer-left">{{ config('microfinance.institution_name') }}</td>
            <td class="footer-center">This is a system-generated document and is confidential.</td>
            <td class="footer-right footer-page"></td>
        </tr>
    </table>
</div>

@yield('content')

</body>
</html>