<?php

/**
 * Stores admin-uploaded templates in the user_templates table.
 *
 * Every save is a new row, so earlier versions are kept. At most one row per
 * scope and slot is published; publishing a version retires the one before it.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

use OpenEMR\Common\Database\QueryUtils;

final class UserTemplateRepository
{
    private const SELECT = 'SELECT t.`id`, t.`scope`, t.`slot`, t.`status`, t.`source`, t.`used_fields`,'
        . ' t.`dataset_version`, t.`created_by`, t.`created_at`,'
        . ' CONCAT_WS(" ", u.`fname`, u.`lname`) AS created_by_name'
        . ' FROM `user_templates` t LEFT JOIN `users` u ON u.`id` = t.`created_by`';

    public function findPublished(string $scope, string $slot): ?UserTemplate
    {
        return $this->findOne(
            self::SELECT . ' WHERE t.`scope` = ? AND t.`slot` = ? AND t.`status` = ? ORDER BY t.`id` DESC LIMIT 1',
            [$scope, $slot, TemplateStatus::Published->value]
        );
    }

    /**
     * The newest draft saved after the published version, if any.
     */
    public function findPendingDraft(string $scope, string $slot): ?UserTemplate
    {
        $published = $this->findPublished($scope, $slot);
        return $this->findOne(
            self::SELECT . ' WHERE t.`scope` = ? AND t.`slot` = ? AND t.`status` = ? AND t.`id` > ?'
            . ' ORDER BY t.`id` DESC LIMIT 1',
            [$scope, $slot, TemplateStatus::Draft->value, $published?->id ?? 0]
        );
    }

    public function find(int $id): ?UserTemplate
    {
        return $this->findOne(self::SELECT . ' WHERE t.`id` = ?', [$id]);
    }

    /**
     * @param list<string> $usedFields
     */
    public function saveDraft(
        string $scope,
        string $slot,
        string $source,
        array $usedFields,
        int $datasetVersion,
        int $userId,
    ): int {
        return QueryUtils::sqlInsert(
            'INSERT INTO `user_templates` (`scope`, `slot`, `status`, `source`, `used_fields`, `dataset_version`, `created_by`)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$scope, $slot, TemplateStatus::Draft->value, $source, json_encode($usedFields, JSON_THROW_ON_ERROR), $datasetVersion, $userId]
        );
    }

    /**
     * Make one saved version the live one and retire whatever was live before.
     */
    public function publish(UserTemplate $template): void
    {
        QueryUtils::inTransaction(function () use ($template): void {
            $this->retirePublished($template->scope, $template->slot);
            QueryUtils::sqlStatementThrowException(
                'UPDATE `user_templates` SET `status` = ? WHERE `id` = ?',
                [TemplateStatus::Published->value, $template->id]
            );
        });
    }

    /**
     * Stop using any uploaded template for this slot, so the core card shows again.
     */
    public function retirePublished(string $scope, string $slot): void
    {
        QueryUtils::sqlStatementThrowException(
            'UPDATE `user_templates` SET `status` = ? WHERE `scope` = ? AND `slot` = ? AND `status` = ?',
            [TemplateStatus::Retired->value, $scope, $slot, TemplateStatus::Published->value]
        );
    }

    /**
     * @param list<string|int> $binds
     */
    private function findOne(string $sql, array $binds): ?UserTemplate
    {
        $row = QueryUtils::querySingleRow($sql, $binds);
        return is_array($row) ? self::hydrate($row) : null;
    }

    /**
     * @param array<mixed> $row
     */
    private static function hydrate(array $row): UserTemplate
    {
        $usedFields = json_decode(self::string($row, 'used_fields'), true, 4, JSON_THROW_ON_ERROR);
        if (!is_array($usedFields) || !array_is_list($usedFields)) {
            throw new \UnexpectedValueException('user_templates.used_fields is not a JSON list');
        }
        $fields = array_values(array_filter($usedFields, is_string(...)));

        return new UserTemplate(
            self::int($row, 'id'),
            self::string($row, 'scope'),
            self::string($row, 'slot'),
            TemplateStatus::from(self::string($row, 'status')),
            self::string($row, 'source'),
            $fields,
            self::int($row, 'dataset_version'),
            self::int($row, 'created_by'),
            trim(is_string($row['created_by_name'] ?? null) ? $row['created_by_name'] : ''),
            new \DateTimeImmutable(self::string($row, 'created_at')),
        );
    }

    /**
     * @param array<mixed> $row
     */
    private static function string(array $row, string $key): string
    {
        $value = $row[$key] ?? null;
        if (!is_string($value)) {
            throw new \UnexpectedValueException(sprintf('user_templates.%s is not a string', $key));
        }
        return $value;
    }

    /**
     * @param array<mixed> $row
     */
    private static function int(array $row, string $key): int
    {
        $value = $row[$key] ?? null;
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }
        throw new \UnexpectedValueException(sprintf('user_templates.%s is not an integer', $key));
    }
}
