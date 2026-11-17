<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>@yield('document-title', 'Official Document')</title>
<style>
    @page { margin: 21mm 17mm 20mm; }
    * { box-sizing: border-box; }
    body { margin: 0; padding: 0; color: #111; font-family: "Times New Roman", Times, serif; font-size: 11px; line-height: 1.45; }
    p { margin: 0 0 10px; text-align: justify; }
    .pdf-watermark { position: fixed; top: 30%; left: 20%; z-index: -1000; width: 60%; text-align: center; opacity: .035; }
    .pdf-watermark img { width: 285px; height: auto; }
    .official-header { width: 100%; margin: 0 0 10px; border-collapse: collapse; border-bottom: 2px solid #185f50; }
    .official-header td { padding: 0 0 12px; vertical-align: middle; }
    .header-left, .header-right { width: 21%; }
    .header-left { text-align: left; }
    .header-right { text-align: right; }
    .header-center { width: 58%; text-align: center; }
    .primary-logo { max-width: 92px; max-height: 82px; }
    .secondary-logo { max-width: 112px; max-height: 72px; }
    .institution-name { font-size: 15px; font-weight: bold; line-height: 1.25; text-transform: uppercase; }
    .institution-tagline { margin-top: 2px; font-size: 10px; font-style: italic; }
    .institution-contact { margin-top: 5px; font-size: 8px; line-height: 1.35; }
    .identity-box { display: inline-block; min-width: 105px; padding: 7px 8px; border: 1px solid #b8c8c2; text-align: left; font-size: 8px; line-height: 1.4; }
    .identity-box strong { display: block; color: #185f50; font-size: 8px; text-transform: uppercase; }
    .document-classification { margin-top: 4px; text-align: right; color: #64736d; font-size: 7px; font-weight: bold; letter-spacing: .08em; text-transform: uppercase; }
    .document-title { margin: 14px 0 5px; text-align: center; font-size: 14px; font-weight: bold; text-decoration: underline; text-transform: uppercase; }
    .document-meta { margin-bottom: 18px; text-align: center; color: #333; font-size: 9px; line-height: 1.45; }
    .document-meta .reference { font-weight: bold; }
    h2, .section-heading { margin: 14px 0 8px; padding: 7px 8px; border: 1px solid #b8c0bc; background: #f2f5f3; text-align: center; font-size: 10px; font-weight: bold; text-transform: uppercase; page-break-after: avoid; }
    h3 { margin: 11px 0 6px; color: #185f50; font-size: 10px; text-transform: uppercase; page-break-after: avoid; }
    .grid, .summary, .info-table { width: 100%; margin: 10px 0 15px; border-collapse: collapse; table-layout: fixed; }
    .grid th, .grid td, .summary th, .summary td, .info-table th, .info-table td { padding: 5px 6px; border: 1px solid #b8c0bc; font-size: 8px; line-height: 1.3; vertical-align: top; overflow-wrap: break-word; }
    .grid th, .summary th, .info-table th { background: #edf2ef; font-weight: bold; text-align: left; text-transform: uppercase; }
    .summary td { background: #fff; }
    .summary strong { display: block; margin-bottom: 2px; color: #52605a; font-size: 7px; letter-spacing: .04em; text-transform: uppercase; }
    .info-table th { width: 30%; }
    .number { text-align: right !important; white-space: nowrap; }
    .text-center { text-align: center !important; }
    .text-right { text-align: right !important; }
    .text-bold { font-weight: bold; }
    .badge { display: inline-block; padding: 2px 5px; border: 1px solid #9ba7a1; font-size: 7px; font-weight: bold; text-transform: uppercase; }
    .badge-green { border-color: #6fae95; background: #e8f5ef; color: #145f49; }
    .badge-red { border-color: #d9939b; background: #fbecee; color: #9f2837; }
    .badge-amber { border-color: #d9b66c; background: #fff7e3; color: #8a5a00; }
    .badge-gray { background: #eff2f1; color: #56635d; }
    .notice { margin: 10px 0; padding: 8px 9px; border: 1px solid #b9cbc4; border-left: 3px solid #185f50; background: #f4f8f6; font-size: 9px; }
    .signature-area { width: 100%; margin-top: 30px; border-collapse: collapse; page-break-inside: avoid; }
    .signature-area td { width: 45%; padding-top: 5px; border-top: 1px solid #222; font-size: 9px; vertical-align: top; }
    .signature-area td.gap { width: 10%; border: 0; }
    .keep-together, tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
    tfoot { display: table-row-group; }
    .page-break { page-break-before: always; }
    .document-footer { position: fixed; right: 0; bottom: -12mm; left: 0; padding-top: 5px; border-top: 1px solid #aeb7b2; color: #4c5852; text-align: center; font-size: 8px; }
    .page-number:after { content: counter(page); }
</style>
@stack('pdf-styles')
</head>
<body>
@php
    $primaryLogoPath = config('microfinance.logo_path', public_path('images/logo.png'));
    $secondaryLogoPath = config('microfinance.secondary_logo_path', public_path('images/logo-secondary.png'));
    $primaryLogoData = is_string($primaryLogoPath) && file_exists($primaryLogoPath) ? base64_encode(file_get_contents($primaryLogoPath)) : null;
    $secondaryLogoData = is_string($secondaryLogoPath) && file_exists($secondaryLogoPath) ? base64_encode(file_get_contents($secondaryLogoPath)) : null;
@endphp

@if ($primaryLogoData)
    <div class="pdf-watermark"><img src="data:image/png;base64,{{ $primaryLogoData }}" alt=""></div>
@endif

<div class="document-footer">
    {{ config('microfinance.institution_name') }} &bull; Official system-generated document &bull; Page <span class="page-number"></span>
</div>

<table class="official-header">
    <tr>
        <td class="header-left">
            @if ($primaryLogoData)<img class="primary-logo" src="data:image/png;base64,{{ $primaryLogoData }}" alt="Institution logo">@endif
        </td>
        <td class="header-center">
            <div class="institution-name">{{ config('microfinance.institution_name') }}</div>
            @if (config('microfinance.tagline'))<div class="institution-tagline">{{ config('microfinance.tagline') }}</div>@endif
            <div class="institution-contact">
                @if (config('microfinance.address')){{ config('microfinance.address') }}<br>@endif
                @if (config('microfinance.phone'))Tel: {{ config('microfinance.phone') }}@endif
                @if (config('microfinance.email')) &nbsp;|&nbsp; {{ config('microfinance.email') }}@endif
                @if (config('microfinance.website'))<br>{{ config('microfinance.website') }}@endif
            </div>
        </td>
        <td class="header-right">
            @if ($secondaryLogoData)
                <img class="secondary-logo" src="data:image/png;base64,{{ $secondaryLogoData }}" alt="Secondary institution logo">
            @else
                <div class="identity-box">
                    <strong>Institution identity</strong>
                    @if (config('microfinance.license_number'))Licence: {{ config('microfinance.license_number') }}<br>@endif
                    @if (config('microfinance.registration_number'))Registration: {{ config('microfinance.registration_number') }}<br>@endif
                    @if (config('microfinance.tin_number'))TIN: {{ config('microfinance.tin_number') }}@endif
                </div>
            @endif
            <div class="document-classification">@yield('document-classification', 'Official document')</div>
        </td>
    </tr>
</table>

<div class="document-title">@yield('document-title', 'Official Document')</div>
<div class="document-meta">
    <strong>@yield('document-tag', 'Official Document')</strong><br>
    <span class="reference">@yield('document-ref')</span><br>
    Generated {{ now()->timezone(config('app.timezone'))->format('d M Y, H:i') }}
</div>

<main class="document-content">@yield('content')</main>
</body>
</html>
