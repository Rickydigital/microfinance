<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>@yield('document-title')</title>
<style>
    @page { margin: 20mm 18mm 20mm; }
    body { font-family: "Times New Roman", Times, serif; font-size: 12px; line-height: 1.5; color: #000; margin: 0; }
    .pdf-watermark { position: fixed; top: 32%; left: 20%; width: 60%; text-align: center; opacity: .045; z-index: -1000; }
    .pdf-watermark img { width: 300px; }
    .institution-header { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
    .institution-header td { vertical-align: middle; padding: 0; }
    .header-side { width: 20%; }
    .header-side img { width: 90px; height: auto; }
    .header-center { width: 60%; text-align: center; }
    .institution-name { font-size: 14px; font-weight: bold; text-transform: uppercase; }
    .contact { font-size: 10px; }
    .document-title { text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; text-decoration: underline; margin: 18px 0 12px; }
    .document-meta { text-align: center; font-size: 10px; margin-bottom: 20px; }
    h2 { padding: 7px 8px; margin: 14px 0 9px; border: 1px solid #b5b5b5; background: #f5f5f5; text-align: center; font-size: 12px; text-transform: uppercase; page-break-after: avoid; }
    .grid, .summary { width: 100%; border-collapse: collapse; margin: 12px 0 16px; table-layout: fixed; }
    .grid th, .grid td, .summary td { border: 1px solid #b5b5b5; padding: 4px 5px; font-size: 9px; line-height: 1.3; vertical-align: top; overflow-wrap: break-word; }
    .grid th { background: #f5f5f5; text-align: left; text-transform: uppercase; }
    .summary strong { display: block; font-size: 9px; text-transform: uppercase; }
    .number { text-align: right !important; }
    tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
    .badge { font-weight: bold; }
    .page-break { page-break-before: always; }
    .document-footer { position: fixed; bottom: -12mm; left: 0; right: 0; border-top: 1px solid #b5b5b5; padding-top: 5px; text-align: center; font-size: 9px; color: #333; }
    .page-number:after { content: counter(page); }
</style>
</head>
<body>
@php
    $logoPath = public_path('images/logo.png');
    $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
@endphp
@if ($logoData)
<div class="pdf-watermark"><img src="data:image/png;base64,{{ $logoData }}" alt=""></div>
@endif
<div class="document-footer">{{ config('microfinance.institution_name') }} - Official System Generated Document - Page <span class="page-number"></span></div>
<table class="institution-header">
    <tr>
        <td class="header-side">@if ($logoData)<img src="data:image/png;base64,{{ $logoData }}" alt="Institution logo">@endif</td>
        <td class="header-center">
            <div class="institution-name">{{ config('microfinance.institution_name') }}</div>
            @if (config('microfinance.tagline'))<div>{{ config('microfinance.tagline') }}</div>@endif
            <div class="contact">
                @if (config('microfinance.address')){{ config('microfinance.address') }}<br>@endif
                @if (config('microfinance.phone'))Tel: {{ config('microfinance.phone') }}<br>@endif
                @if (config('microfinance.email')){{ config('microfinance.email') }}<br>@endif
                {{ config('microfinance.website') }}
            </div>
        </td>
        <td class="header-side"></td>
    </tr>
</table>
<div class="document-title">@yield('document-title')</div>
<div class="document-meta">@yield('document-tag', 'Official Document')<br>@yield('document-ref')<br>Generated {{ now()->format('d M Y, H:i') }}</div>
@yield('content')
</body>
</html>
