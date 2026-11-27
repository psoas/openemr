<?php

/**
 * What the upload check found: the dataset fields a template reads and any problems.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

final readonly class ScanResult
{
    /**
     * @param list<string> $usedFields
     * @param list<TemplateIssue> $issues
     */
    public function __construct(
        public array $usedFields,
        public array $issues,
    ) {
    }

    public function isValid(): bool
    {
        return $this->issues === [];
    }
}
