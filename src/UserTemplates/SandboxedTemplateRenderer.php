<?php

/**
 * Renders admin-uploaded Twig templates inside Twig's sandbox.
 *
 * Uses its own Twig environment, never the core one: core runs with
 * autoescape off and trusts its templates, while this environment escapes
 * every value as HTML and only allows a short list of tags, filters,
 * functions and tests (strict security policy, the Twig 4 behavior).
 * Templates receive plain arrays of scalars, so there are no objects whose
 * methods or properties a template could reach. Autoescaping covers the
 * data; TemplateHtmlCleaner then cleans the HTML the template itself writes.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\ModuleNode;
use Twig\Sandbox\Sandbox;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;
use Twig\TwigFilter;

final readonly class SandboxedTemplateRenderer
{
    /** Largest output a template may produce, in bytes. */
    public const MAX_OUTPUT_BYTES = 100_000;

    public const ALLOWED_TAGS = ['if', 'for', 'set'];

    public const ALLOWED_FILTERS = [
        'escape', 'e', 'date', 'upper', 'lower', 'title', 'capitalize',
        'default', 'length', 'trim', 'number_format', 'phone',
    ];

    public const ALLOWED_FUNCTIONS = [];

    public const ALLOWED_TESTS = ['defined', 'empty', 'null', 'none', 'same as', 'odd', 'even'];

    private Environment $environment;

    private Sandbox $sandbox;

    public function __construct(private TemplateHtmlCleaner $cleaner = new TemplateHtmlCleaner())
    {
        $this->environment = new Environment(new ArrayLoader(), [
            'autoescape' => 'html',
            'cache' => false,
            'strict_variables' => false,
        ]);
        $this->environment->addFilter(new TwigFilter('phone', self::formatPhone(...)));

        $policy = new SecurityPolicy(
            self::ALLOWED_TAGS,
            self::ALLOWED_FILTERS,
            [],
            [],
            self::ALLOWED_FUNCTIONS,
            self::ALLOWED_TESTS,
        );
        $policy->setStrict(true);
        $this->sandbox = new Sandbox($this->environment, $policy);
    }

    /**
     * Render a template with plain data and clean the resulting HTML.
     *
     * @param array<string, array<string, string|int|float|bool|null>|string|int|float|bool|null> $context
     * @throws \Twig\Error\Error when the template doesn't parse or uses something the sandbox blocks
     * @throws TemplateOutputTooLargeException when the output is larger than MAX_OUTPUT_BYTES
     */
    public function render(string $source, array $context): string
    {
        return $this->cleaner->clean($this->renderUncleaned($source, $context));
    }

    /**
     * Render with plain data and describe the HTML that cleaning removes, so
     * the upload check can report it. Only the branches the data reaches are
     * seen; render() cleans every branch regardless.
     *
     * @param array<string, array<string, string|int|float|bool|null>|string|int|float|bool|null> $context
     * @return list<string>
     * @throws \Twig\Error\Error
     * @throws TemplateOutputTooLargeException
     */
    public function findRemovedHtml(string $source, array $context): array
    {
        return $this->cleaner->findRemovals($this->renderUncleaned($source, $context));
    }

    /**
     * @param array<string, array<string, string|int|float|bool|null>|string|int|float|bool|null> $context
     * @throws \Twig\Error\Error
     * @throws TemplateOutputTooLargeException
     */
    private function renderUncleaned(string $source, array $context): string
    {
        $output = $this->sandbox->createTemplate($source, 'user template')->render($context);
        if (strlen($output) > self::MAX_OUTPUT_BYTES) {
            throw new TemplateOutputTooLargeException('The template produced more output than allowed');
        }
        return $output;
    }

    /**
     * Compile the template and render it once with no data, so syntax errors
     * and blocked tags, filters, functions and tests are reported. The
     * sandbox checks its policy when a template renders, not when it compiles.
     *
     * @throws \Twig\Error\Error
     */
    public function compile(string $source): void
    {
        $this->sandbox->createTemplate($source, 'user template')->render([]);
    }

    /**
     * Parse the template into Twig's node tree, for the upload check.
     *
     * @throws \Twig\Error\SyntaxError
     */
    public function parse(string $source): ModuleNode
    {
        return $this->environment->parse($this->environment->tokenize(new Source($source, 'user template')));
    }

    /**
     * Show a 10-digit US number as (555) 123-4567 and leave anything else as it was.
     */
    public static function formatPhone(string|int|float|null $value): string
    {
        $text = (string) $value;
        $digits = preg_replace('/\D/', '', $text) ?? '';
        if (strlen($digits) === 11 && $digits[0] === '1') {
            $digits = substr($digits, 1);
        }
        if (strlen($digits) !== 10) {
            return $text;
        }
        return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
    }
}
