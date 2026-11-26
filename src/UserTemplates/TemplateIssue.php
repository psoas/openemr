<?php

/**
 * One problem the upload check found in a template, with its line number.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

final readonly class TemplateIssue
{
    public function __construct(
        public string $message,
        public int $line,
    ) {
    }
}
