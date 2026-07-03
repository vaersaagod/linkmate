<?php

namespace vaersaagod\linkmate\helpers;

/**
 * Link Helper
 *
 * @author    Værsågod
 * @package   vaersaagod\linkmate\helpers
 */
class LinkHelper
{
    /**
     * URL schemes that are safe to emit in an `href` attribute.
     *
     * Anything with a scheme not on this list (e.g. `javascript:`, `data:`,
     * `vbscript:`) is rejected. Scheme-less values (relative paths, root-relative
     * paths, protocol-relative `//host` URLs, `#anchors` and `?query` strings) are
     * always allowed.
     */
    public const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * Returns the given URL if it is safe to use as an `href`, or NULL if it
     * carries a disallowed scheme (e.g. `javascript:`).
     *
     * The check is deliberately strict about how a scheme is detected: leading
     * whitespace and control characters are stripped before matching, since
     * browsers ignore those when resolving a URL (so `java\tscript:…` and
     * `  javascript:…` are equivalent to `javascript:…`). Scheme comparison is
     * case-insensitive.
     *
     * @param string|null $url
     *
     * @return string|null
     */
    public static function sanitizeUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        // Strip whitespace and control characters, then lowercase, so scheme
        // detection can't be bypassed with padding or embedded control chars.
        $normalized = strtolower(preg_replace('/[\x00-\x20]+/', '', $url));

        // scheme = ALPHA *( ALPHA / DIGIT / "+" / "-" / "." ) ":" (per RFC 3986)
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/', $normalized, $matches)) {
            if (!in_array($matches[1], self::ALLOWED_SCHEMES, true)) {
                return null;
            }
        }

        return $url;
    }
}
