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
    private const SELECT = 'SELECT t.`id`, t.`scope`, t.`slot`, t.`name`, t.`status`, t.`is_active`, t.`source`,'
        . ' t.`used_fields`, t.`dataset_version`, t.`created_by`, t.`created_at`,'
        . ' CONCAT_WS(" ", u.`fname`, u.`lname`) AS created_by_name'
        . ' FROM `user_templates` t LEFT JOIN `users` u ON u.`id` = t.`created_by`';

    /**
     * The published version shown on the dashboard for this slot, if any.
     */
    public function findActive(string $scope, string $slot): ?UserTemplate
    {
        return $this->findOne(
            self::SELECT . ' WHERE t.`scope` = ? AND t.`slot` = ? AND t.`status` = ? AND t.`is_active` = 1'
            . ' ORDER BY t.`id` DESC LIMIT 1',
            [$scope, $slot, TemplateStatus::Published->value]
        );
    }

    /**
     * Names of the templates saved for this slot, in alphabetical order.
     *
     * @return list<string>
     */
    public function listNames(string $scope, string $slot): array
    {
        $names = QueryUtils::fetchTableColumn(
            'SELECT DISTINCT `name` FROM `user_templates` WHERE `scope` = ? AND `slot` = ? ORDER BY `name`',
            'name',
            [$scope, $slot]
        );
        return array_values(array_filter($names, is_string(...)));
    }

    public function nameExists(string $scope, string $slot, string $name): bool
    {
        return QueryUtils::fetchSingleValue(
            'SELECT 1 AS found FROM `user_templates` WHERE `scope` = ? AND `slot` = ? AND `name` = ? LIMIT 1',
            'found',
            [$scope, $slot, $name]
        ) !== null;
    }

    /**
     * The published version of one named template, if it has one.
     */
    public function findPublished(string $scope, string $slot, string $name): ?UserTemplate
    {
        return $this->findOne(
            self::SELECT . ' WHERE t.`scope` = ? AND t.`slot` = ? AND t.`name` = ? AND t.`status` = ?'
            . ' ORDER BY t.`id` DESC LIMIT 1',
            [$scope, $slot, $name, TemplateStatus::Published->value]
        );
    }

    /**
     * The newest draft of one named template saved after its published version, if any.
     */
    public function findPendingDraft(string $scope, string $slot, string $name): ?UserTemplate
    {
        $published = $this->findPublished($scope, $slot, $name);
        return $this->findOne(
            self::SELECT . ' WHERE t.`scope` = ? AND t.`slot` = ? AND t.`name` = ? AND t.`status` = ? AND t.`id` > ?'
            . ' ORDER BY t.`id` DESC LIMIT 1',
            [$scope, $slot, $name, TemplateStatus::Draft->value, $published->id ?? 0]
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
        string $name,
        string $source,
        array $usedFields,
        int $datasetVersion,
        int $userId,
    ): int {
        return QueryUtils::sqlInsert(
            'INSERT INTO `user_templates`'
            . ' (`scope`, `slot`, `name`, `status`, `source`, `used_fields`, `dataset_version`, `created_by`)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $scope,
                $slot,
                $name,
                TemplateStatus::Draft->value,
                $source,
                json_encode($usedFields, JSON_THROW_ON_ERROR),
                $datasetVersion,
                $userId,
            ]
        );
    }

    /**
     * Make a draft the published version of its template and retire the
     * version it replaces. If that template is the active one, the new
     * version becomes active too, so the dashboard shows it right away.
     */
    public function publish(UserTemplate $draft): void
    {
        QueryUtils::inTransaction(function () use ($draft): void {
            $previous = $this->findPublished($draft->scope, $draft->slot, $draft->name);
            QueryUtils::sqlStatementThrowException(
                'UPDATE `user_templates` SET `status` = ?, `is_active` = 0'
                . ' WHERE `scope` = ? AND `slot` = ? AND `name` = ? AND `status` = ?',
                [TemplateStatus::Retired->value, $draft->scope, $draft->slot, $draft->name, TemplateStatus::Published->value]
            );
            QueryUtils::sqlStatementThrowException(
                'UPDATE `user_templates` SET `status` = ?, `is_active` = ? WHERE `id` = ?',
                [TemplateStatus::Published->value, $previous?->isActive === true ? 1 : 0, $draft->id]
            );
        });
    }

    /**
     * Show this published version on the dashboard instead of whatever was shown before.
     */
    public function activate(UserTemplate $published): void
    {
        QueryUtils::inTransaction(function () use ($published): void {
            $this->deactivate($published->scope, $published->slot);
            QueryUtils::sqlStatementThrowException(
                'UPDATE `user_templates` SET `is_active` = 1 WHERE `id` = ? AND `status` = ?',
                [$published->id, TemplateStatus::Published->value]
            );
        });
    }

    /**
     * Stop showing any uploaded template for this slot, so the core card shows
     * again. The templates stay saved and can be made active later.
     */
    public function deactivate(string $scope, string $slot): void
    {
        QueryUtils::sqlStatementThrowException(
            'UPDATE `user_templates` SET `is_active` = 0 WHERE `scope` = ? AND `slot` = ? AND `is_active` = 1',
            [$scope, $slot]
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
            self::string($row, 'name'),
            TemplateStatus::from(self::string($row, 'status')),
            self::int($row, 'is_active') === 1,
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
