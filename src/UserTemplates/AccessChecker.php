<?php

/**
 * Answers whether the current user holds an ACL permission. Lets datasets
 * leave out fields the viewer can't see without calling AclMain directly.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

interface AccessChecker
{
    public function allows(AclRequirement $requirement): bool;
}
