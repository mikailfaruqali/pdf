<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | PDF Binary Path
    |--------------------------------------------------------------------------
    |
    | Absolute path to the pdf executable binary. If set to null, the package
    | will search in storage_path('pdf/pdf'), base_path('pdf'), or PATH.
    | You can download the binary automatically using 'php artisan pdf:install'.
    |
    */
    'binary_path' => env('PDF_BINARY_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Headless Chrome Path Override
    |--------------------------------------------------------------------------
    |
    | By default, pdf automatically detects Google Chrome, Chromium, Brave,
    | or Microsoft Edge installed on your system. Set an explicit path if you
    | want to override the detected executable.
    |
    */
    'chrome_path' => env('PDF_CHROME_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Execution Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum execution timeout in seconds for the PDF rendering process.
    |
    */
    'timeout' => (int) env('PDF_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Temp Directory
    |--------------------------------------------------------------------------
    |
    | Storage path for temporary HTML snippets (content, header, footer).
    | If null, sys_get_temp_dir() is used.
    |
    */
    'temp_path' => env('PDF_TEMP_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Template UI Routes Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable the template configuration routes at /pdf-templates.
    |
    */
    'routes_enabled' => env('PDF_ROUTES_ENABLED', TRUE),

    /*
    |--------------------------------------------------------------------------
    | Template UI Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URI prefix for template studio management routes (default: pdf-templates).
    |
    */
    'route_prefix' => env('PDF_ROUTE_PREFIX', 'pdf-templates'),

    /*
    |--------------------------------------------------------------------------
    | Template UI Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware stack to apply to the template management interface routes.
    | You can pass strings or array of middleware (e.g. ['web', 'auth', 'can:manage-pdf-templates']).
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Default PDF Generation Options
    |--------------------------------------------------------------------------
    |
    | Default options applied to every generated PDF instance unless explicitly
    | overridden via fluent builder methods or database templates (pdf_templates table).
    |
    | Every feature available in the template management modal is configurable here:
    |
    |   1. Page Setup & Dimensions:
    |      - 'paper'             => 'A4' (A0-A6, B4, B5, Letter, Legal, Tabloid, etc.)
    |      - 'pageWidth'         => null (e.g. '210mm', '8.5in', '500px')
    |      - 'pageHeight'        => null (e.g. '297mm', '11in', '800px')
    |      - 'orientation'       => 'portrait' | 'landscape'
    |      - 'scale'             => 1.0 (float, 0.1 to 2.0)
    |      - 'smartShrinking'    => false (bool, shrink overflowing content to printable width)
    |      - 'preferCssPageSize' => false (bool, honor @page CSS rule instead of paper size)
    |
    |   2. Margins:
    |      - 'margin'            => '0mm' (applies to all 4 sides if individual sides not set)
    |      - 'marginTop'         => null (e.g. '5mm', '10mm', '0.5in')
    |      - 'marginBottom'      => null
    |      - 'marginLeft'        => null
    |      - 'marginRight'       => null
    |      - 'disableMargins'    => false (bool, full bleed zero margins)
    |
    |   3. Header & Footer (HTML fragments, support {page}, {pages}, {page+1}):
    |      - 'headerHtml'        => null (raw HTML string or Blade markup)
    |      - 'headerHeight'      => null (e.g. '20mm')
    |      - 'headerSpacing'     => null (e.g. '4mm')
    |      - 'headerOffset'      => null (e.g. '0mm')
    |      - 'disableHeader'     => false (bool, suppress/disable header)
    |      - 'footerHtml'        => null (raw HTML string or Blade markup)
    |      - 'footerHeight'      => null (e.g. '15mm')
    |      - 'footerSpacing'     => null (e.g. '4mm')
    |      - 'footerOffset'      => null (e.g. '0mm')
    |      - 'disableFooter'     => false (bool, suppress/disable footer)
    |
    |   4. Watermark & Content Override:
    |      - 'watermarkHtml'     => null (raw HTML or text)
    |      - 'watermarkOpacity'  => 0.3 (float, 0.0 to 1.0)
    |      - 'watermarkBehind'   => true (bool, draw under content vs over)
    |      - 'disableWatermark'  => false (bool, suppress/disable watermark)
    |      - 'contentHtml'       => null (raw HTML to override entire view)
    |
    |   5. Document Metadata & Page Offsets:
    |      - 'title'             => null (PDF document title metadata)
    |      - 'author'            => null (PDF document author metadata)
    |      - 'subject'           => null (PDF document subject metadata)
    |      - 'keywords'          => null (PDF document keywords metadata)
    |      - 'baseUrl'           => null (base URL/directory for relative assets)
    |      - 'pageOffset'        => 0 (int, offset added to current page number)
    |      - 'totalOffset'       => 0 (int, offset added to total page count)
    |
    |   6. Viewer UI & Fonts:
    |      - 'withViewer'        => true (bool, use built-in custom PDF viewer on inline preview by default)
    |      - 'theme'             => 'dark' ('dark' | 'light' | 'auto')
    |      - 'dir'               => 'ltr' ('ltr' | 'rtl' | 'auto')
    |      - 'icon'              => null (emoji '📄', URL, data: URI, or image file path)
    |      - 'fontFamily'        => null (font family name, e.g. 'Noto Sans', 'Rabar')
    |      - 'fontPath'          => null (path to .ttf/.otf/.woff/.woff2 font file)
    |      - 'fontStack'         => null (CSS font stack string)
    |
    |   7. Runtime CSS Variables & Styles:
    |      - 'cssVariables'      => [] (key-value array of custom properties, e.g. ['--sn-accent' => '#58a6ff'])
    |      - 'cssFiles'          => [] (array of CSS file paths to inline into document fragments)
    |      - 'cssUrls'           => [] (array of CSS stylesheet URLs to link)
    |
    */
    'options' => [
        // Page Setup & Dimensions
        'paper' => env('PDF_DEFAULT_PAPER', 'A4'),
        'orientation' => env('PDF_DEFAULT_ORIENTATION', 'portrait'),
        // 'pageWidth' => null,
        // 'pageHeight' => null,
        // 'scale' => 1.0,
        // 'smartShrinking' => false,
        // 'preferCssPageSize' => false,

        // Margins
        'margin' => env('PDF_DEFAULT_MARGIN', '0mm'),
        // 'marginTop' => null,
        // 'marginBottom' => null,
        // 'marginLeft' => null,
        // 'marginRight' => null,
        // 'disableMargins' => false,

        // Header & Footer
        // 'disableHeader' => false,
        // 'headerHtml' => null,
        // 'headerHeight' => null,
        // 'headerSpacing' => null,
        // 'headerOffset' => null,
        // 'disableFooter' => false,
        // 'footerHtml' => null,
        // 'footerHeight' => null,
        // 'footerSpacing' => null,
        // 'footerOffset' => null,

        // Watermark & Content Override
        // 'disableWatermark' => false,
        // 'watermarkHtml' => null,
        // 'watermarkOpacity' => 0.3,
        // 'watermarkBehind' => true,
        // 'contentHtml' => null,

        // Metadata & Page Offsets
        // 'title' => null,
        // 'author' => null,
        // 'subject' => null,
        // 'keywords' => null,
        // 'baseUrl' => null,
        // 'pageOffset' => 0,
        // 'totalOffset' => 0,

        // Viewer UI & Fonts
        'withViewer' => env('PDF_WITH_VIEWER', TRUE),
        // 'theme' => 'dark',
        // 'dir' => 'ltr',
        // 'icon' => null,
        // 'fontFamily' => null,
        // 'fontPath' => null,
        // 'fontStack' => null,

        // Runtime CSS Variables & External Styles
        // 'cssVariables' => [],
        // 'cssFiles' => [],
        // 'cssUrls' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Template Locales
    |--------------------------------------------------------------------------
    |
    | Locales available in the template management dropdown selector.
    |
    */
    'locales' => ['*', 'en', 'ar', 'ckb', 'ku', 'fr', 'de', 'es', 'tr', 'fa'],
];
