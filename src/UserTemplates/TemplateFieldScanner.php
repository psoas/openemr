<?php

/**
 * The upload check: finds which dataset fields a template reads and reports
 * anything the template may not do, each with its line number.
 *
 * It parses the template into Twig's node tree and walks it, so problems
 * show up in the template manager when the template is saved, not on a
 * patient's chart. The list of used fields is stored with the template.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

use Twig\Error\Error as TwigError;
use Twig\Node\Expression\Binary\RangeBinary;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Node;

final readonly class TemplateFieldScanner
{
    /** Variables Twig itself provides inside a for loop. */
    private const BUILT_IN_VARIABLES = ['loop'];

    public function __construct(private SandboxedTemplateRenderer $renderer)
    {
    }

    public function scan(string $source, TemplateDataset $dataset): ScanResult
    {
        try {
            $tree = $this->renderer->parse($source);
        } catch (TwigError $e) {
            return new ScanResult([], [new TemplateIssue($e->getRawMessage(), $e->getTemplateLine())]);
        }

        $known = [];
        foreach ($dataset->fields() as $field) {
            $known[$field->name] = true;
        }

        $assigned = [];
        $this->collectAssignedNames($tree, $assigned);

        $used = [];
        $issues = [];
        $this->walk($tree, null, $dataset->rootName(), $known, $assigned, $used, $issues);

        // Blocked tags, filters, functions and tests are reported by the sandbox itself.
        if ($issues === []) {
            try {
                $this->renderer->compile($source);
                // HTML written in the template itself that cleaning would remove.
                $removed = $this->renderer->findRemovedHtml($source, [$dataset->rootName() => $dataset->sample()]);
                foreach ($removed as $message) {
                    $issues[] = new TemplateIssue('Not allowed in the output: ' . $message, null);
                }
            } catch (TwigError $e) {
                $issues[] = new TemplateIssue($e->getRawMessage(), $e->getTemplateLine());
            } catch (TemplateOutputTooLargeException) {
                $issues[] = new TemplateIssue('The template produces more output than allowed with the sample data', null);
            }
        }

        $usedFields = array_keys($used);
        sort($usedFields);
        return new ScanResult($usedFields, $issues);
    }

    /**
     * @param array<string, true> $assigned
     */
    private function collectAssignedNames(Node $node, array &$assigned): void
    {
        if ($node instanceof AssignContextVariable) {
            $name = $node->getAttribute('name');
            if (is_string($name)) {
                $assigned[$name] = true;
            }
        }
        foreach ($node as $child) {
            $this->collectAssignedNames($child, $assigned);
        }
    }

    /**
     * @param array<string, true> $known
     * @param array<string, true> $assigned
     * @param array<string, true> $used
     * @param list<TemplateIssue> $issues
     */
    private function walk(
        Node $node,
        ?Node $parent,
        string $root,
        array $known,
        array $assigned,
        array &$used,
        array &$issues,
    ): void {
        $line = $node->getTemplateLine();

        if ($node instanceof RangeBinary) {
            $issues[] = new TemplateIssue('Ranges such as 1..10 are not allowed', $line);
        }

        if ($node instanceof GetAttrExpression) {
            $target = $node->getNode('node');
            if ($target instanceof ContextVariable && $target->getAttribute('name') === $root) {
                $attribute = $node->getNode('attribute');
                $field = $attribute instanceof ConstantExpression ? $attribute->getAttribute('value') : null;
                if (!is_string($field)) {
                    $issues[] = new TemplateIssue(sprintf('Write the field name out, like %s.dob', $root), $line);
                } elseif (!isset($known[$field])) {
                    $issues[] = new TemplateIssue(sprintf('Unknown field %s.%s', $root, $field), $line);
                } else {
                    $used[$field] = true;
                }
            }
        }

        if ($node instanceof ContextVariable && !$node instanceof AssignContextVariable) {
            $name = $node->getAttribute('name');
            if ($name === $root) {
                if (!$parent instanceof GetAttrExpression || $parent->getNode('node') !== $node) {
                    $issues[] = new TemplateIssue(sprintf('Use a field, like %s.dob, not %s on its own', $root, $root), $line);
                }
            } elseif (is_string($name) && !isset($assigned[$name]) && !in_array($name, self::BUILT_IN_VARIABLES, true)) {
                $issues[] = new TemplateIssue(sprintf('Unknown variable %s', $name), $line);
            }
        }

        foreach ($node as $child) {
            $this->walk($child, $node, $root, $known, $assigned, $used, $issues);
        }
    }
}
