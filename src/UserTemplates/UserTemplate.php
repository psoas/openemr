<?php

/**
 * One saved version of an admin-uploaded template.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

final readonly class UserTemplate
{
    /**
     * @param list<string> $usedFields
     */
    public function __construct(
        public int $id,
        public string $scope,
        public string $slot,
        public string $name,
        public TemplateStatus $status,
        public bool $isActive,
        public string $source,
        public array $usedFields,
        public int $datasetVersion,
        public int $createdBy,
        public string $createdByName,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
