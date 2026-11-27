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

use OpenEMR\UserTemplates\SandboxedTemplateRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Error\Error as TwigError;
use Twig\Sandbox\SecurityError;

final class SandboxedTemplateRendererTest extends TestCase
{
    public function testRendersFieldsFromPlainData(): void
    {
        $html = (new SandboxedTemplateRenderer())->render(
            '<b>{{ patient.full_name|upper }}</b> {{ patient.dob|date("m/d/Y") }}',
            ['patient' => ['full_name' => 'Ann Sample', 'dob' => '1958-03-14']]
        );

        $this->assertSame('<b>ANN SAMPLE</b> 03/14/1958', $html);
    }

    public function testEscapesValuesAsHtml(): void
    {
        $html = (new SandboxedTemplateRenderer())->render(
            '<p>{{ patient.full_name }}</p>',
            ['patient' => ['full_name' => '<script>alert(1)</script>']]
        );

        $this->assertSame('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>', $html);
    }

    public function testCleansHtmlWrittenInTheTemplate(): void
    {
        $html = (new SandboxedTemplateRenderer())->render(
            '<p onclick="alert(1)">{{ patient.full_name }}</p><script>alert(2)</script>',
            ['patient' => ['full_name' => 'Ann']]
        );

        $this->assertSame('<p>Ann</p>', $html);
    }

    #[DataProvider('blockedTemplateProvider')]
    public function testBlocksWhatThePolicyDoesNotAllow(string $source): void
    {
        $this->expectException(SecurityError::class);

        (new SandboxedTemplateRenderer())->render($source, ['patient' => ['full_name' => 'Ann']]);
    }

    /**
     * @return array<string, array{string}>
     *
     * @codeCoverageIgnore Data providers run before coverage instrumentation starts.
     */
    public static function blockedTemplateProvider(): array
    {
        return [
            'raw filter' => ['{{ patient.full_name|raw }}'],
            'include tag' => ['{% include "other.twig" %}'],
            'source function' => ['{{ source("/etc/passwd") }}'],
            'attribute function' => ['{{ attribute(patient, "full_name") }}'],
            'macro tag' => ['{% macro x() %}{% endmacro %}'],
            'map filter with arrow' => ['{{ [1]|map(v => v)|length }}'],
        ];
    }

    public function testReportsSyntaxErrorsWithTheLine(): void
    {
        try {
            (new SandboxedTemplateRenderer())->compile("line one\n{% if %}");
            $this->fail('Expected a syntax error');
        } catch (TwigError $e) {
            $this->assertSame(2, $e->getTemplateLine());
        }
    }

    #[DataProvider('phoneProvider')]
    public function testFormatsPhoneNumbers(string $input, string $expected): void
    {
        $this->assertSame($expected, SandboxedTemplateRenderer::formatPhone($input));
    }

    /**
     * @return array<string, array{string, string}>
     *
     * @codeCoverageIgnore Data providers run before coverage instrumentation starts.
     */
    public static function phoneProvider(): array
    {
        return [
            'ten digits' => ['5555550142', '(555) 555-0142'],
            'with country code' => ['1-555-555-0142', '(555) 555-0142'],
            'already formatted' => ['(555) 555-0142', '(555) 555-0142'],
            'not a US number' => ['+44 20 7946 0958', '+44 20 7946 0958'],
        ];
    }
}
