<?php

/**
 * AccessChecker backed by the current user's ACL through AclMain.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

use OpenEMR\Common\Acl\AclMain;

final class AclMainAccessChecker implements AccessChecker
{
    public function allows(AclRequirement $requirement): bool
    {
        return AclMain::aclCheckCore($requirement->section, $requirement->value);
    }
}
