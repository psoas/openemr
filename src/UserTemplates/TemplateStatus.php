<?php

/**
 * Lifecycle of a stored template row. Backed because it is persisted.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

enum TemplateStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';
}
