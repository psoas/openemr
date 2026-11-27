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

use Lcobucci\Clock\FrozenClock;
use OpenEMR\UserTemplates\AccessChecker;
use OpenEMR\UserTemplates\AclRequirement;
use OpenEMR\UserTemplates\Dataset\DashboardCardDataset;
use OpenEMR\UserTemplates\SandboxedTemplateRenderer;
use OpenEMR\UserTemplates\TemplateFieldScanner;
use OpenEMR\UserTemplates\TemplateIssue;
use PHPUnit\Framework\TestCase;

final class TemplateFieldScannerTest extends TestCase
{
    private TemplateFieldScanner $scanner;

    private DashboardCardDataset $dataset;

    protected function setUp(): void
    {
        $this->scanner = new TemplateFieldScanner(new SandboxedTemplateRenderer());
        $allowAll = new class implements AccessChecker {
            public function allows(AclRequirement $requirement): bool
            {
                return true;
            }
        };
        $this->dataset = new DashboardCardDataset($allowAll, new FrozenClock(new \DateTimeImmutable("2026-11-26")));
    }

    public function testListsTheFieldsATemplateUses(): void
    {
        $result = $this->scanner->scan(
            "{% if patient.portal_enabled %}Portal{% endif %}\n{{ patient.full_name }} {{ patient.dob|date('m/d/Y') }} {{ patient.full_name }}",
            $this->dataset
        );

        $this->assertTrue($result->isValid());
        $this->assertSame(['dob', 'full_name', 'portal_enabled'], $result->usedFields);
    }

    public function testRejectsAFieldTheDatasetDoesNotDeclare(): void
    {
        $result = $this->scanner->scan("{{ patient.full_name }}\n\n{{ patient.ssn }}", $this->dataset);

        $this->assertSame(['Unknown field patient.ssn on line 3'], self::describe($result->issues));
    }

    public function testRejectsUnknownVariables(): void
    {
        $result = $this->scanner->scan('{{ app.user }}', $this->dataset);

        $this->assertSame(['Unknown variable app on line 1'], self::describe($result->issues));
    }

    public function testAllowsVariablesTheTemplateSetsItself(): void
    {
        $result = $this->scanner->scan('{% set label = patient.full_name|upper %}{{ label }}', $this->dataset);

        $this->assertTrue($result->isValid());
        $this->assertSame(['full_name'], $result->usedFields);
    }

    public function testRejectsTheWholePatientObject(): void
    {
        $result = $this->scanner->scan('{{ patient|length }}', $this->dataset);

        $this->assertSame(['Use a field, like patient.dob, not patient on its own on line 1'], self::describe($result->issues));
    }

    public function testRejectsComputedFieldNames(): void
    {
        $result = $this->scanner->scan('{% set f = "ssn" %}{{ patient[f] }}', $this->dataset);

        $this->assertSame(['Write the field name out, like patient.dob on line 1'], self::describe($result->issues));
    }

    public function testRejectsRanges(): void
    {
        $result = $this->scanner->scan("{% for i in 1..100000000 %}x{% endfor %}", $this->dataset);

        $this->assertContains('Ranges such as 1..10 are not allowed on line 1', self::describe($result->issues));
    }

    public function testReportsWhatTheSandboxBlocks(): void
    {
        $result = $this->scanner->scan("ok\n{{ patient.full_name|raw }}", $this->dataset);

        $this->assertFalse($result->isValid());
        $this->assertSame(2, $result->issues[0]->line);
        $this->assertStringContainsString('raw', $result->issues[0]->message);
    }

    public function testReportsHtmlThatCleaningRemoves(): void
    {
        $result = $this->scanner->scan('<p>{{ patient.full_name }}</p><script>alert(1)</script>', $this->dataset);

        $this->assertFalse($result->isValid());
        $this->assertNull($result->issues[0]->line);
        $this->assertStringContainsString('script', $result->issues[0]->message);
    }

    public function testReportsSyntaxErrors(): void
    {
        $result = $this->scanner->scan("{{ patient.full_name }}\n{% if %}", $this->dataset);

        $this->assertFalse($result->isValid());
        $this->assertSame(2, $result->issues[0]->line);
    }

    public function testSampleDataCoversEveryDeclaredField(): void
    {
        $names = array_map(static fn($field): string => $field->name, $this->dataset->fields());

        $this->assertSame($names, array_keys($this->dataset->sample()));
    }

    /**
     * @param list<TemplateIssue> $issues
     * @return list<string>
     */
    private static function describe(array $issues): array
    {
        return array_map(static fn(TemplateIssue $issue): string => $issue->message . ' on line ' . $issue->line, $issues);
    }
}
