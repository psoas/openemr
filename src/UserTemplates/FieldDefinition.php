<?php

/**
 * One field a template dataset promises to provide. The list of these is the
 * contract between OpenEMR and the templates written against the dataset.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

final readonly class FieldDefinition
{
    public function __construct(
        public string $name,
        public string $label,
        public FieldType $type,
        public ?AclRequirement $acl = null,
    ) {
    }
}
