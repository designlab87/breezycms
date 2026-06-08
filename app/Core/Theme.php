<?php

namespace App\Core;

/**
 * Site theme: turns the admin's saved choices (3 colors, 3 font roles, corner
 * style) into CSS custom properties that override the defaults in site.css.
 *
 * Text-on-color values (--on-*) are computed automatically from each color's
 * WCAG relative luminance, so labels on buttons / colored bands stay legible
 * regardless of the picked color. The admin panel is unaffected — only the
 * public site consumes these variables.
 */
class Theme
{
    /** Corner presets: [card/image radius, button radius]. */
    private const CORNERS = [
        'square'  => ['0px', '0px'],
        'rounded' => ['12px', '8px'],
        'pill'    => ['22px', '999px'],
    ];

    private const DEFAULTS = [
        'color_primary' => '#2563eb',
        'color_accent'  => '#10b981',
        'color_base'    => '#f7f8fa',
        'font_h1'       => 'system',
        'font_h2'       => 'system',
        'font_body'     => 'system',
        'size_h1'       => '',
        'size_h2'       => '',
        'size_h3'       => '',
        'size_body'     => '',
        'corners'       => 'rounded',
    ];

    /** Allowed px range for the optional font-size overrides. */
    public const SIZE_MIN = 8;
    public const SIZE_MAX = 160;

    private array $theme;
    private array $fonts;

    public function __construct(array $themeSettings, array $fontsRegistry)
    {
        $this->theme = array_merge(self::DEFAULTS, array_filter($themeSettings, fn ($v) => $v !== null && $v !== ''));
        $this->fonts = $fontsRegistry;
    }

    public function defaults(): array
    {
        return self::DEFAULTS;
    }

    public function get(string $key, $default = null)
    {
        return $this->theme[$key] ?? $default;
    }

    public function cornerPresets(): array
    {
        return array_keys(self::CORNERS);
    }

    /** The font registry (for admin dropdowns / Quill whitelist). */
    public function fontRegistry(): array
    {
        return $this->fonts;
    }

    /** Resolve a font key to its CSS family stack (falls back to system). */
    public function fontFamily(string $key): string
    {
        return $this->fonts[$key]['family'] ?? $this->fonts['system']['family'];
    }

    /**
     * Build the :root override block injected into the public <head>.
     */
    public function cssVariables(): string
    {
        $primary = $this->normalizeHex($this->theme['color_primary']) ?? self::DEFAULTS['color_primary'];
        $accent  = $this->normalizeHex($this->theme['color_accent'])  ?? self::DEFAULTS['color_accent'];
        $base    = $this->normalizeHex($this->theme['color_base'])    ?? self::DEFAULTS['color_base'];

        $corners = self::CORNERS[$this->theme['corners']] ?? self::CORNERS['rounded'];

        $vars = [
            '--brand'       => $primary,
            '--brand-dark'  => $this->darken($primary, 0.14),
            '--on-brand'    => $this->contrastColor($primary),
            '--accent'      => $accent,
            '--accent-dark' => $this->darken($accent, 0.14),
            '--on-accent'   => $this->contrastColor($accent),
            '--base'        => $base,
            '--on-base'     => $this->contrastColor($base),
            '--base-border' => $this->darken($base, 0.08),
            '--radius'      => $corners[0],
            '--btn-radius'  => $corners[1],
            '--font'        => $this->fontFamily($this->theme['font_body']),
            '--font-h1'     => $this->fontFamily($this->theme['font_h1']),
            '--font-h2'     => $this->fontFamily($this->theme['font_h2']),
        ];

        // Optional px font-size overrides. Only emitted when set; otherwise the
        // responsive defaults in site.css (:root) remain in effect.
        $sizes = [
            '--size-h1'   => $this->theme['size_h1'],
            '--size-h2'   => $this->theme['size_h2'],
            '--size-h3'   => $this->theme['size_h3'],
            '--size-body' => $this->theme['size_body'],
        ];
        foreach ($sizes as $k => $raw) {
            $px = $this->sizePx($raw);
            if ($px !== null) {
                $vars[$k] = $px;
            }
        }

        $lines = '';
        foreach ($vars as $k => $v) {
            $lines .= "  {$k}: {$v};\n";
        }
        return ":root {\n{$lines}}\n";
    }

    /** Normalize a px font-size to "Npx" within range, or null to use default. */
    public function sizePx($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $n = (int) $value;
        if ($n < self::SIZE_MIN || $n > self::SIZE_MAX) {
            return null;
        }
        return $n . 'px';
    }

    // --- Color math --------------------------------------------------------

    /** Validate/normalize a #rgb or #rrggbb value to lowercase #rrggbb. */
    private function normalizeHex($value): ?string
    {
        $v = strtolower(trim((string) $value));
        if (preg_match('/^#([0-9a-f]{3})$/', $v, $m)) {
            $c = $m[1];
            return '#' . $c[0] . $c[0] . $c[1] . $c[1] . $c[2] . $c[2];
        }
        if (preg_match('/^#[0-9a-f]{6}$/', $v)) {
            return $v;
        }
        return null;
    }

    /** @return array{0:int,1:int,2:int} */
    private function rgb(string $hex): array
    {
        $hex = $this->normalizeHex($hex) ?? '#000000';
        return [
            hexdec(substr($hex, 1, 2)),
            hexdec(substr($hex, 3, 2)),
            hexdec(substr($hex, 5, 2)),
        ];
    }

    /** WCAG relative luminance (0=black .. 1=white). */
    private function luminance(string $hex): float
    {
        [$r, $g, $b] = $this->rgb($hex);
        $chan = function ($c) {
            $c /= 255;
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };
        return 0.2126 * $chan($r) + 0.7152 * $chan($g) + 0.0722 * $chan($b);
    }

    /** Pick near-black or white text for best contrast on the given color. */
    private function contrastColor(string $hex): string
    {
        // Contrast ratio vs white and vs near-black; choose the higher.
        $l = $this->luminance($hex);
        $withWhite = (1.0 + 0.05) / ($l + 0.05);
        $ink = '#111827';
        $lInk = $this->luminance($ink);
        $withInk = ($l + 0.05) / ($lInk + 0.05);
        return $withInk >= $withWhite ? $ink : '#ffffff';
    }

    /** Darken a hex color by a 0..1 fraction (multiplicative). */
    private function darken(string $hex, float $amount): string
    {
        [$r, $g, $b] = $this->rgb($hex);
        $f = max(0.0, 1.0 - $amount);
        return sprintf('#%02x%02x%02x', (int) round($r * $f), (int) round($g * $f), (int) round($b * $f));
    }
}
