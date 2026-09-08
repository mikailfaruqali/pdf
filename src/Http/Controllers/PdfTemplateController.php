<?php

declare(strict_types=1);

namespace PDF\Http\Controllers;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use PDF\Facades\Pdf;
use PDF\Services\ViewFinderService;
use Throwable;
use Traversable;

class PdfTemplateController extends Controller
{
    public function index(): Response
    {
        $templates = DB::table('pdf_templates')
            ->orderBy('view')
            ->orderBy('locale')
            ->get()
            ->map(function ($t) {
                $t->options = is_array($t->options) ? $t->options : ((array) (json_decode((string) $t->options, TRUE) ?: []));

                return $t;
            });

        $availableViews = ViewFinderService::getAvailableViews();
        $supportedLocales = $this->resolveSupportedLocales();

        $paperSizes = [
            'A4', 'Letter', 'Legal', 'A3', 'A5', 'A6', 'A0', 'A1', 'A2',
            'B4', 'B5', 'Tabloid', 'Ledger', 'Executive',
        ];

        return response()->view('pdf::templates.index', [
            'templates' => $templates,
            'availableViews' => $availableViews,
            'supportedLocales' => $supportedLocales,
            'paperSizes' => $paperSizes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'view' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:16'],
            'options' => ['nullable', 'array'],
        ]);

        $options = $request->input('options', []);
        $optionErrors = $this->validateTemplateOptions($options);
        if ($optionErrors !== []) {
            return response()->json([
                'success' => FALSE,
                'message' => $optionErrors[0],
                'errors' => $optionErrors,
            ], 422);
        }

        $exists = DB::table('pdf_templates')
            ->where('view', $validated['view'])
            ->where('locale', $validated['locale'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => FALSE,
                'message' => "A template for view '{$validated['view']}' and locale '{$validated['locale']}' already exists.",
            ], 422);
        }

        $options = $this->sanitizeOptions($options);
        $now = date('Y-m-d H:i:s');

        $id = DB::table('pdf_templates')->insertGetId([
            'view' => $validated['view'],
            'locale' => $validated['locale'],
            'options' => json_encode($options, JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $template = (object) [
            'id' => $id,
            'view' => $validated['view'],
            'locale' => $validated['locale'],
            'options' => $options,
        ];

        return response()->json([
            'success' => TRUE,
            'message' => 'PDF Template created successfully.',
            'data' => $template,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $template = DB::table('pdf_templates')->where('id', $id)->first();

        if (! $template) {
            return response()->json([
                'success' => FALSE,
                'message' => 'Template not found.',
            ], 404);
        }

        $template->options = is_array($template->options)
            ? $template->options
            : ((array) (json_decode((string) $template->options, TRUE) ?: []));

        return response()->json([
            'success' => TRUE,
            'data' => $template,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $template = DB::table('pdf_templates')->where('id', $id)->first();

        if (! $template) {
            return response()->json([
                'success' => FALSE,
                'message' => 'Template not found.',
            ], 404);
        }

        $validated = $request->validate([
            'view' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:16'],
            'options' => ['nullable', 'array'],
        ]);

        $options = $request->input('options', []);
        $optionErrors = $this->validateTemplateOptions($options);
        if ($optionErrors !== []) {
            return response()->json([
                'success' => FALSE,
                'message' => $optionErrors[0],
                'errors' => $optionErrors,
            ], 422);
        }

        $exists = DB::table('pdf_templates')
            ->where('view', $validated['view'])
            ->where('locale', $validated['locale'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => FALSE,
                'message' => "Another template for view '{$validated['view']}' and locale '{$validated['locale']}' already exists.",
            ], 422);
        }

        $options = $this->sanitizeOptions($options);

        DB::table('pdf_templates')->where('id', $id)->update([
            'view' => $validated['view'],
            'locale' => $validated['locale'],
            'options' => json_encode($options, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $template = (object) [
            'id' => $id,
            'view' => $validated['view'],
            'locale' => $validated['locale'],
            'options' => $options,
        ];

        return response()->json([
            'success' => TRUE,
            'message' => 'PDF Template updated successfully.',
            'data' => $template,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::table('pdf_templates')->where('id', $id)->delete();

        return response()->json([
            'success' => TRUE,
            'message' => 'PDF Template deleted successfully.',
        ]);
    }

    public function preview(Request $request): Response
    {
        $viewName = $request->input('view', '');
        $rawOptions = $request->input('options', []);
        $options = is_string($rawOptions) ? (json_decode($rawOptions, TRUE) ?: []) : (array) $rawOptions;

        $optionErrors = $this->validateTemplateOptions($options);
        if ($optionErrors !== []) {
            return response("<div style='font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; padding: 24px; color: #d1242f; background: #fff5f5; border: 1px solid #ffcccc; border-radius: 6px; margin: 24px;'><h3>Validation Error</h3><p>" . htmlspecialchars($optionErrors[0]) . "</p></div>", 422);
        }

        $options = $this->sanitizeOptions($options);

        $pdf = Pdf::make();

        if (blank($options['contentHtml'] ?? NULL)) {
            if ($viewName !== '' && view()->exists($viewName)) {
                try {
                    $pdf->content(view($viewName, []));
                } catch (Throwable) {
                    $pdf->content("<div style='font-family: sans-serif; padding: 30px; text-align: center; border: 2px dashed #ccc;'><h2>View: {$viewName}</h2><p>Previewing template layout</p></div>");
                }
            } else {
                $pdf->content("<div style='font-family: sans-serif; padding: 40px; text-align: center;'><h2>Sample Document Preview</h2><p>Configure options on the left to see live preview.</p></div>");
            }
        }

        try {
            $pdf->applyTemplateOptions($options);

            return $pdf->inline('template-preview.pdf');
        } catch (Throwable $throwable) {
            return response("<div style='font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; padding: 24px; color: #d1242f; background: #fff5f5; border: 1px solid #ffcccc; border-radius: 6px; margin: 24px;'><h3>PDF Engine Error</h3><p><strong>Message:</strong> " . htmlspecialchars($throwable->getMessage()) . "</p></div>", 422);
        }
    }

    /**
     * Validate options against engine constraints to prevent engine errors.
     *
     * @return array<int, string> List of error messages (empty if valid)
     */
    protected function validateTemplateOptions(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        $errors = [];

        $hasWidth = isset($options['pageWidth']) && trim((string) $options['pageWidth']) !== '';
        $hasHeight = isset($options['pageHeight']) && trim((string) $options['pageHeight']) !== '';

        if ($hasWidth xor $hasHeight) {
            $errors[] = 'Both custom page width and custom page height must be provided together.';
        }

        $dimRegex = '/^\s*[0-9]+(\.[0-9]+)?\s*(mm|cm|in|pt|px|pc)?\s*$/i';

        $dimFields = [
            'pageWidth' => 'Custom page width',
            'pageHeight' => 'Custom page height',
            'marginTop' => 'Top margin',
            'marginBottom' => 'Bottom margin',
            'marginLeft' => 'Left margin',
            'marginRight' => 'Right margin',
            'headerHeight' => 'Header height',
            'headerSpacing' => 'Header spacing',
            'headerOffset' => 'Header offset',
            'footerHeight' => 'Footer height',
            'footerSpacing' => 'Footer spacing',
            'footerOffset' => 'Footer offset',
        ];

        foreach ($dimFields as $field => $label) {
            if (isset($options[$field]) && trim((string) $options[$field]) !== '') {
                $val = trim((string) $options[$field]);
                if (! preg_match($dimRegex, $val)) {
                    $errors[] = "{$label} '{$val}' is invalid. Please use a positive number with optional unit (e.g. 10mm, 0.5in, 20px, 15pt).";
                } elseif ($this->parseDimensionToInches($val) < 0) {
                    $errors[] = "{$label} must not be negative.";
                }
            }
        }

        if ($hasWidth && $hasHeight && preg_match($dimRegex, (string) $options['pageWidth']) && preg_match($dimRegex, (string) $options['pageHeight'])) {
            if ($this->parseDimensionToInches((string) $options['pageWidth']) <= 0 || $this->parseDimensionToInches((string) $options['pageHeight']) <= 0) {
                $errors[] = 'Custom page width and height must both be greater than zero.';
            }
        }

        if (isset($options['scale']) && trim((string) $options['scale']) !== '') {
            $scale = (float) $options['scale'];
            if ($scale < 0.1 || $scale > 2.0) {
                $errors[] = "Scale must be between 0.1 and 2.0 (got {$scale}).";
            }
        }

        if (isset($options['watermarkOpacity']) && trim((string) $options['watermarkOpacity']) !== '') {
            $op = (float) $options['watermarkOpacity'];
            if ($op < 0.0 || $op > 1.0) {
                $errors[] = "Watermark opacity must be between 0.0 and 1.0 (got {$op}).";
            }
        }

        return $errors;
    }

    protected function parseDimensionToInches(string $dim): float
    {
        $dim = strtolower(trim($dim));
        if ($dim === '') {
            return 0.0;
        }

        $units = [
            'mm' => 25.4,
            'cm' => 2.54,
            'in' => 1.0,
            'pt' => 72.0,
            'px' => 96.0,
            'pc' => 6.0,
        ];

        foreach ($units as $suffix => $divisor) {
            if (str_ends_with($dim, $suffix)) {
                $num = (float) trim(substr($dim, 0, -strlen($suffix)));

                return $num / $divisor;
            }
        }

        return ((float) $dim) / 25.4;
    }

    protected function resolveSupportedLocales(): array
    {
        $locales = config('pdf.locales', ['en', 'ar', 'ckb', 'ku', 'fr', 'de', 'es', 'tr', 'fa']);

        if (is_callable($locales)) {
            $locales = app()->call($locales);
        } elseif (is_string($locales) && (class_exists($locales) || str_contains($locales, '@') || str_contains($locales, '::'))) {
            $locales = app()->call($locales);
        } elseif (is_array($locales) && count($locales) === 2 && is_string($locales[0]) && is_string($locales[1]) && (class_exists($locales[0]) || method_exists($locales[0], $locales[1]))) {
            $locales = app()->call($locales);
        }

        if ($locales instanceof Arrayable) {
            $locales = $locales->toArray();
        } elseif ($locales instanceof Traversable) {
            $locales = iterator_to_array($locales);
        } else {
            $locales = (array) $locales;
        }

        $cleanLocales = [];
        foreach ($locales as $key => $value) {
            if ($value === '*' || $key === '*') {
                continue;
            }

            $cleanLocales[$key] = $value;
        }

        if (array_is_list($cleanLocales)) {
            array_unshift($cleanLocales, '*');

            return $cleanLocales;
        }

        return ['*' => '*'] + $cleanLocales;
    }

    /**
     * Accept either a { name: value } map or a list of { name, value } rows
     * (as posted by the template studio) and return a normalized map keyed by
     * '--custom-property'.
     */
    private function sanitizeCssVariables(mixed $variables): array
    {
        if (is_string($variables)) {
            $variables = json_decode($variables, TRUE);
        }

        if (! is_array($variables)) {
            return [];
        }

        $clean = [];

        foreach ($variables as $key => $entry) {
            [$name, $value] = is_array($entry)
                ? [$entry['name'] ?? '', $entry['value'] ?? '']
                : [$key, $entry];

            if (! is_string($name) || is_array($value)) {
                continue;
            }

            $safeName = trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', ltrim(trim($name), '-')) ?? '', '-');
            $safeValue = trim(preg_replace('/[\r\n]+/', ' ', str_replace(['</', '{', '}', ';'], '', (string) $value)) ?? '');

            if ($safeName === '' || $safeValue === '') {
                continue;
            }

            $clean['--' . $safeName] = $safeValue;
        }

        return $clean;
    }

    private function sanitizeOptions(array $options): array
    {
        $clean = [];

        $stringFields = [
            'paper', 'pageWidth', 'pageHeight', 'orientation', 'margin', 'marginTop', 'marginBottom',
            'marginLeft', 'marginRight', 'headerHeight', 'footerHeight',
            'headerSpacing', 'footerSpacing', 'headerOffset', 'footerOffset',
            'title', 'author', 'subject', 'keywords', 'baseUrl', 'theme',
            'dir', 'icon', 'fontFamily', 'fontStack', 'fontPath',
            'headerHtml', 'footerHtml', 'watermarkHtml', 'contentHtml',
        ];

        foreach ($stringFields as $stringField) {
            if (isset($options[$stringField]) && trim((string) $options[$stringField]) !== '') {
                $clean[$stringField] = (string) $options[$stringField];
            }
        }

        if (isset($clean['pageWidth']) xor isset($clean['pageHeight'])) {
            unset($clean['pageWidth'], $clean['pageHeight']);
        }

        if (isset($options['watermarkOpacity']) && is_numeric($options['watermarkOpacity'])) {
            $clean['watermarkOpacity'] = (float) $options['watermarkOpacity'];
        }

        if (isset($options['scale']) && is_numeric($options['scale'])) {
            $clean['scale'] = (float) $options['scale'];
        }

        if (isset($options['pageOffset']) && is_numeric($options['pageOffset'])) {
            $clean['pageOffset'] = (int) $options['pageOffset'];
        }

        if (isset($options['totalOffset']) && is_numeric($options['totalOffset'])) {
            $clean['totalOffset'] = (int) $options['totalOffset'];
        }

        if (isset($options['timeout']) && is_numeric($options['timeout'])) {
            $clean['timeout'] = (int) $options['timeout'];
        }

        $cssVariables = $this->sanitizeCssVariables($options['cssVariables'] ?? NULL);

        if ($cssVariables !== []) {
            $clean['cssVariables'] = $cssVariables;
        }

        $boolFields = [
            'watermarkBehind', 'smartShrinking', 'preferCssPageSize', 'withViewer', 'quiet',
            'disableHeader', 'disableFooter', 'disableWatermark', 'disableMargins',
        ];

        foreach ($boolFields as $boolField) {
            if (isset($options[$boolField])) {
                $clean[$boolField] = filter_var($options[$boolField], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $clean;
    }
}
