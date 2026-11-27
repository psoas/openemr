<?php

/**
 * Cleans the HTML an admin-uploaded template produces.
 *
 * Autoescaping protects patient data, but not HTML typed into the template
 * itself. This removes anything outside a short allow-list of layout tags
 * and attributes: scripts, event handlers, iframes, forms, images, inline
 * styles, javascript: and external links, and the Bootstrap classes that
 * would let a card cover the rest of the page.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

use HTMLPurifier;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_ErrorCollector;

final readonly class TemplateHtmlCleaner
{
    /** Tags a template may produce. */
    public const ALLOWED_ELEMENTS = [
        'div', 'span', 'p', 'br', 'hr', 'strong', 'b', 'em', 'i', 'u', 'small',
        'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'dl', 'dt', 'dd',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'a', 'abbr',
    ];

    /** Attributes a template may produce: bare names on any allowed tag, `tag.name` on that tag only. */
    public const ALLOWED_ATTRIBUTES = [
        'class', 'title', 'a.href', 'th.colspan', 'th.rowspan', 'th.scope', 'td.colspan', 'td.rowspan',
    ];

    /** Classes that would take the card out of its place on the page. */
    public const FORBIDDEN_CLASSES = [
        'fixed-top', 'fixed-bottom', 'sticky-top',
        'position-fixed', 'position-absolute', 'position-sticky',
        'modal', 'modal-backdrop',
    ];

    public function clean(string $html): string
    {
        return $this->purifier(false)->purify($html);
    }

    /**
     * Describe what clean() would remove, for the upload check.
     *
     * Only removals are reported. Harmless rewrites (closing an open tag,
     * turning an entity into its character) are not.
     *
     * @return list<string>
     */
    public function findRemovals(string $html): array
    {
        $purifier = $this->purifier(true);
        $purifier->purify($html);
        $context = $purifier->context;
        if (!$context instanceof HTMLPurifier_Context) {
            return [];
        }
        $collector = $context->get('ErrorCollector');
        if (!$collector instanceof HTMLPurifier_ErrorCollector) {
            return [];
        }
        $errors = $collector->getRaw();
        if (!is_array($errors)) {
            return [];
        }
        $removals = [];
        foreach ($errors as $error) {
            if (is_array($error) && ($error[1] ?? null) === E_ERROR && is_string($error[2] ?? null)) {
                $removals[$error[2]] = true;
            }
        }
        return array_keys($removals);
    }

    private function purifier(bool $collectErrors): HTMLPurifier
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        // The proof of concept doesn't cache HTMLPurifier's definitions.
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.AllowedElements', self::ALLOWED_ELEMENTS);
        $config->set('HTML.AllowedAttributes', self::ALLOWED_ATTRIBUTES);
        $config->set('Attr.ForbiddenClasses', self::FORBIDDEN_CLASSES);
        // No links to or resources from other sites, so a template can't send
        // patient data out in a URL.
        $config->set('URI.DisableExternal', true);
        $config->set('URI.DisableExternalResources', true);
        $config->set('Core.CollectErrors', $collectErrors);
        return new HTMLPurifier($config);
    }
}
