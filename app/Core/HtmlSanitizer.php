<?php

namespace App\Core;

/**
 * Allowlist HTML sanitizer for untrusted rich text (page/block content).
 *
 * Only safe tags and attributes survive; URL attributes are restricted to
 * http, https, mailto and tel schemes (relative URLs are kept). Event
 * handlers, scripts, styles and other active content are removed.
 */
class HtmlSanitizer
{
    /** Tags removed together with their content. */
    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'noscript',
        'template', 'svg', 'math', 'title', 'head', 'form', 'base', 'meta',
        'link', 'applet', 'frame', 'frameset', 'xmp', 'plaintext',
    ];

    /** Tags kept as-is. Unknown tags are unwrapped (children kept). */
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins',
        'sub', 'sup', 'small', 'mark', 'abbr', 'cite', 'q', 'span', 'div',
        'blockquote', 'pre', 'code', 'kbd', 'samp', 'var',
        'ul', 'ol', 'li', 'dl', 'dt', 'dd',
        'a', 'img', 'figure', 'figcaption', 'picture', 'source',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'caption', 'colgroup', 'col',
        'video', 'audio', 'track', 'details', 'summary', 'time', 'address',
    ];

    /** Attributes allowed on every element. */
    private const GLOBAL_ATTRS = ['class', 'title', 'lang', 'dir'];

    /** Attributes allowed per tag (merged with the global set). */
    private const TAG_ATTRS = [
        'a' => ['href', 'target', 'rel', 'name', 'hreflang'],
        'img' => ['src', 'srcset', 'sizes', 'alt', 'width', 'height', 'loading', 'decoding'],
        'source' => ['src', 'srcset', 'sizes', 'media', 'type'],
        'video' => ['src', 'poster', 'width', 'height', 'controls', 'preload', 'muted', 'loop'],
        'audio' => ['src', 'controls', 'preload', 'loop'],
        'track' => ['src', 'kind', 'srclang', 'label'],
        'ol' => ['start', 'reversed', 'type'],
        'li' => ['value'],
        'th' => ['colspan', 'rowspan', 'scope', 'headers', 'abbr'],
        'td' => ['colspan', 'rowspan', 'headers'],
        'col' => ['span'],
        'colgroup' => ['span'],
        'time' => ['datetime'],
        'abbr' => ['title'],
        'blockquote' => ['cite'],
        'q' => ['cite'],
        'del' => ['cite', 'datetime'],
        'ins' => ['cite', 'datetime'],
        'details' => ['open'],
    ];

    /** URL-carrying attributes that must pass the scheme check. */
    private const URL_ATTRS = ['href', 'src', 'cite', 'poster', 'srcset'];

    public static function sanitize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        // The XML prolog forces UTF-8 parsing regardless of meta tags.
        $ok = $doc->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        if (!$ok) {
            // Fail closed: escaped text is always safe.
            return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
        }

        $out = '';
        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body !== null) {
            $children = iterator_to_array($body->childNodes ?? []);
            foreach ($children as $child) {
                self::sanitizeNode($child);
            }
            foreach ($body->childNodes as $child) {
                $out .= $doc->saveHTML($child);
            }
        }

        return trim($out);
    }

    /**
     * Sanitize the rich-text fields ('html', 'text') of a content_blocks
     * JSON document. Non-string values and other keys pass through.
     * The JSON is re-encoded WITHOUT JSON_UNESCAPED_SLASHES so forward
     * slashes (e.g. "</script>") are emitted as "<\/script>".
     */
    public static function sanitizeBlocksJson(string $json): string
    {
        $blocks = json_decode($json, true);
        if (!is_array($blocks)) {
            return '[]';
        }

        $walk = function (array &$arr) use (&$walk): void {
            foreach ($arr as $key => &$value) {
                if (is_array($value)) {
                    $walk($value);
                } elseif (is_string($value) && in_array($key, ['html', 'text'], true)) {
                    $value = self::sanitize($value);
                }
            }
            unset($value);
        };
        $walk($blocks);

        return json_encode($blocks, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    private static function sanitizeNode(\DOMNode $node): void
    {
        if ($node->nodeType === XML_COMMENT_NODE) {
            $node->parentNode?->removeChild($node);
            return;
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        $tag = strtolower($node->nodeName);

        if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
            $node->parentNode?->removeChild($node);
            return;
        }

        if (!in_array($tag, self::ALLOWED_TAGS, true)) {
            // Unknown tag: unwrap so inline content survives, then keep
            // sanitizing the (now re-parented) children.
            $children = iterator_to_array($node->childNodes ?? []);
            self::unwrap($node);
            foreach ($children as $child) {
                self::sanitizeNode($child);
            }
            return;
        }

        self::sanitizeAttributes($node, $tag);

        // Iterate a copy: children may be removed while walking.
        $children = iterator_to_array($node->childNodes ?? []);
        foreach ($children as $child) {
            self::sanitizeNode($child);
        }
    }

    private static function sanitizeAttributes(\DOMElement $node, string $tag): void
    {
        $allowed = array_merge(self::GLOBAL_ATTRS, self::TAG_ATTRS[$tag] ?? []);

        foreach (iterator_to_array($node->attributes ?? []) as $attr) {
            $name = strtolower($attr->nodeName);
            $value = (string) $attr->nodeValue;

            // Strip every event handler and anything namespaced.
            if (str_starts_with($name, 'on') || !in_array($name, $allowed, true)) {
                $node->removeAttributeNode($attr);
                continue;
            }

            if (in_array($name, self::URL_ATTRS, true)) {
                if (!self::isSafeUrl($value)) {
                    $node->removeAttributeNode($attr);
                    continue;
                }
                if ($name === 'href' && $node->getAttribute('target') === '_blank') {
                    $node->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }
    }

    /**
     * Accept only http, https, mailto, tel or scheme-less (relative) URLs.
     */
    public static function isSafeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        // Control characters and whitespace are ignored by some parsers
        // when resolving schemes; strip them before inspecting.
        $clean = preg_replace('/[\x00-\x20\x7f]/', '', $url) ?? '';
        if ($clean === '') {
            return false;
        }

        if (str_contains($clean, ':')) {
            $scheme = strtolower(substr($clean, 0, strpos($clean, ':')));
            return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
        }

        // "//host" inherits the page scheme; everything else is relative.
        return true;
    }

    private static function unwrap(\DOMNode $node): void
    {
        $parent = $node->parentNode;
        if ($parent === null) {
            return;
        }

        $children = iterator_to_array($node->childNodes ?? []);
        foreach ($children as $child) {
            $parent->insertBefore($child, $node);
        }
        $parent->removeChild($node);
    }
}
