<?php

/**
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\UserTemplates;

use OpenEMR\UserTemplates\TemplateHtmlCleaner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TemplateHtmlCleanerTest extends TestCase
{
    public function testKeepsLayoutMarkupAndClasses(): void
    {
        $html = '<div class="row text-muted"><strong class="h6 mb-0">Ann</strong> '
            . '<span class="badge badge-success" title="Portal">Portal</span></div>';

        $this->assertSame($html, (new TemplateHtmlCleaner())->clean($html));
    }

    public function testKeepsDetailsAndSummaryForShowAndHide(): void
    {
        $html = '<details class="mt-1"><summary class="btn btn-link p-0">More</summary><div class="row">x</div></details>';

        $this->assertSame($html, (new TemplateHtmlCleaner())->clean($html));
    }

    public function testKeepsTheOpenAttributeOnDetails(): void
    {
        $html = (new TemplateHtmlCleaner())->clean('<details open><summary>More</summary>x</details>');

        $this->assertStringContainsString('<details open', $html);
    }

    #[DataProvider('removedHtmlProvider')]
    public function testRemovesWhatIsNotAllowed(string $html, string $expected): void
    {
        $this->assertSame($expected, (new TemplateHtmlCleaner())->clean($html));
    }

    /**
     * @return array<string, array{string, string}>
     *
     * @codeCoverageIgnore Data providers run before coverage instrumentation starts.
     */
    public static function removedHtmlProvider(): array
    {
        return [
            'script' => ['<b><script>alert(1)</script></b>', '<b></b>'],
            'event handler' => ['<div onclick="alert(1)">x</div>', '<div>x</div>'],
            'iframe' => ['<iframe src="https://example.com"></iframe>ok', 'ok'],
            'javascript link' => ['<a href="javascript:alert(1)">x</a>', '<a>x</a>'],
            'external link' => ['<a href="https://example.com/?n=Ann">x</a>', '<a>x</a>'],
            'image' => ['<img src="https://example.com/x.png">', ''],
            'form' => ['<form action="/x"><input name="a"></form>', ''],
            'toggle handler on details' => ['<details ontoggle="alert(1)"><summary>M</summary></details>', '<details><summary>M</summary></details>'],
            'inline style' => ['<div style="color:red">x</div>', '<div>x</div>'],
            'page-covering class' => ['<div class="position-fixed fixed-top text-danger">x</div>', '<div class="text-danger">x</div>'],
        ];
    }

    public function testReportsRemovals(): void
    {
        $removals = (new TemplateHtmlCleaner())->findRemovals('<p>ok</p><div onclick="alert(1)">x</div>');

        $this->assertCount(1, $removals);
        $this->assertStringContainsString('onclick', $removals[0]);
    }

    public function testReportsNothingForAllowedMarkup(): void
    {
        $this->assertSame([], (new TemplateHtmlCleaner())->findRemovals('<p class="mb-0">Ann &middot; 67</p>'));
    }
}
