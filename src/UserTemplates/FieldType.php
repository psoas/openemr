<?php

/**
 * The kind of value a template dataset field holds. Drives the sample value
 * the template manager previews with.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

enum FieldType
{
    case Text;
    case Date;
    case DateTime;
    case Phone;
    case Email;
    case Money;
    case Boolean;
}
