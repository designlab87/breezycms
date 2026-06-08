<?php

/**
 * Curated, self-hosted font registry for the site theme.
 *
 * `key`    => stable id stored in settings + used as the Quill font class
 *             (ql-font-<key>) and the font face files (assets/fonts/<key>-*.woff2)
 * `label`  => human label shown in the admin + Quill dropdown
 * `family` => the CSS font-family stack (first name must match @font-face in
 *             public/assets/css/fonts.css)
 *
 * The "system" entry uses the OS UI font and needs no downloaded files; it is
 * the default for every role and is Quill's default (no class).
 *
 * To add a font: download its woff2 (latin) into public/assets/fonts, add an
 * @font-face to fonts.css, and add an entry here. Nothing else to change.
 */

return [
    'system'           => ['label' => 'System default', 'family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif'],
    'inter'            => ['label' => 'Inter',           'family' => '"Inter", sans-serif'],
    'poppins'          => ['label' => 'Poppins',         'family' => '"Poppins", sans-serif'],
    'montserrat'       => ['label' => 'Montserrat',      'family' => '"Montserrat", sans-serif'],
    'oswald'           => ['label' => 'Oswald',          'family' => '"Oswald", sans-serif'],
    'average-sans'     => ['label' => 'Average Sans',    'family' => '"Average Sans", sans-serif'],
    'playfair-display' => ['label' => 'Playfair Display','family' => '"Playfair Display", serif'],
    'lora'             => ['label' => 'Lora',            'family' => '"Lora", serif'],
    'merriweather'     => ['label' => 'Merriweather',    'family' => '"Merriweather", serif'],
    'roboto-slab'      => ['label' => 'Roboto Slab',     'family' => '"Roboto Slab", serif'],
    'dancing-script'   => ['label' => 'Dancing Script',  'family' => '"Dancing Script", cursive'],
];
