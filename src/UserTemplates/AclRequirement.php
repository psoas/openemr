<?php

/**
 * An ACL section and value a user needs before a dataset field is filled in.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

final readonly class AclRequirement
{
    public function __construct(
        public string $section,
        public string $value,
    ) {
    }
}
