<?php

/**
 * The data a dashboard card template may use, read through `patient.<field>`.
 *
 * This list is the contract with template authors: a template can only use
 * the fields declared here, and the upload check rejects anything else.
 * Fields the viewer has no permission for are left out (null) before the
 * template sees the data.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates\Dataset;

use OpenEMR\Billing\PatientBalance\PatientBalanceLoader;
use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\UserTemplates\AccessChecker;
use OpenEMR\UserTemplates\AclRequirement;
use OpenEMR\UserTemplates\FieldDefinition;
use OpenEMR\UserTemplates\FieldType;
use OpenEMR\UserTemplates\TemplateDataset;
use Psr\Clock\ClockInterface;

final readonly class DashboardCardDataset implements TemplateDataset
{
    public const SCOPE = 'dashboard_card';

    public function __construct(
        private AccessChecker $access,
        private ClockInterface $clock,
        private PatientBalanceLoader $balanceLoader = new PatientBalanceLoader(),
    ) {
    }

    public function scope(): string
    {
        return self::SCOPE;
    }

    public function rootName(): string
    {
        return 'patient';
    }

    public function version(): int
    {
        return 1;
    }

    /**
     * @return list<FieldDefinition>
     */
    public function fields(): array
    {
        $demo = new AclRequirement('patients', 'demo');
        $appointments = new AclRequirement('patients', 'appt');
        $billing = new AclRequirement('acct', 'bill');

        return [
            new FieldDefinition('first_name', 'First name', FieldType::Text, $demo),
            new FieldDefinition('last_name', 'Last name', FieldType::Text, $demo),
            new FieldDefinition('full_name', 'Full name', FieldType::Text, $demo),
            new FieldDefinition('mrn', 'External ID (MRN)', FieldType::Text, $demo),
            new FieldDefinition('dob', 'Date of birth', FieldType::Date, $demo),
            new FieldDefinition('age', 'Age in years', FieldType::Text, $demo),
            new FieldDefinition('sex', 'Sex', FieldType::Text, $demo),
            new FieldDefinition('phone_cell', 'Cell phone', FieldType::Phone, $demo),
            new FieldDefinition('phone_home', 'Home phone', FieldType::Phone, $demo),
            new FieldDefinition('email', 'Email', FieldType::Email, $demo),
            new FieldDefinition('language', 'Language', FieldType::Text, $demo),
            new FieldDefinition('interpreter_needed', 'Interpreter needed', FieldType::Text, $demo),
            new FieldDefinition('portal_enabled', 'Portal access allowed', FieldType::Boolean, $demo),
            new FieldDefinition('pcp', 'Primary care provider', FieldType::Text, $demo),
            new FieldDefinition('pharmacy', 'Pharmacy', FieldType::Text, $demo),
            new FieldDefinition('primary_insurance', 'Primary insurance', FieldType::Text, $demo),
            new FieldDefinition('last_visit', 'Last encounter date', FieldType::Date, $appointments),
            new FieldDefinition('next_appointment', 'Next appointment', FieldType::DateTime, $appointments),
            new FieldDefinition('balance', 'Patient balance due', FieldType::Money, $billing),
        ];
    }

    /**
     * @return array<string, string|int|float|bool|null>
     */
    public function sample(): array
    {
        return [
            'first_name' => 'Ann',
            'last_name' => 'Sample',
            'full_name' => 'Ann Sample',
            'mrn' => 'S-1042',
            'dob' => '1958-03-14',
            'age' => '67',
            'sex' => 'Female',
            'phone_cell' => '5555550142',
            'phone_home' => '5555550199',
            'email' => 'ann.sample@example.com',
            'language' => 'Spanish',
            'interpreter_needed' => 'Yes',
            'portal_enabled' => true,
            'pcp' => 'Dr. Lee Example',
            'pharmacy' => 'Main Street Pharmacy',
            'primary_insurance' => 'Example Health Plan',
            'last_visit' => '2026-10-02',
            'next_appointment' => '2026-12-08 09:30',
            'balance' => '45.00',
        ];
    }

    /**
     * Load the declared fields for one patient. Fields the current user may
     * not see come back as null.
     *
     * @return array<string, string|int|float|bool|null>
     */
    public function build(int $pid): array
    {
        $row = QueryUtils::querySingleRow(
            'SELECT p.`fname`, p.`lname`, p.`pubpid`, p.`DOB`, p.`phone_cell`, p.`phone_home`, p.`email`,'
            . ' p.`allow_patient_portal`,'
            . ' sex.`title` AS sex_title, lang.`title` AS language_title, interp.`title` AS interpreter_title,'
            . ' CONCAT_WS(" ", u.`fname`, u.`lname`) AS pcp_name, ph.`name` AS pharmacy_name'
            . ' FROM `patient_data` p'
            . ' LEFT JOIN `list_options` sex ON sex.`list_id` = "sex" AND sex.`option_id` = p.`sex`'
            . ' LEFT JOIN `list_options` lang ON lang.`list_id` = "language" AND lang.`option_id` = p.`language`'
            . ' LEFT JOIN `list_options` interp ON interp.`list_id` = "yes_no_unknown"'
            . ' AND interp.`option_id` = p.`interpreter_needed`'
            . ' LEFT JOIN `users` u ON u.`id` = p.`providerID`'
            . ' LEFT JOIN `pharmacies` ph ON ph.`id` = p.`pharmacy_id`'
            . ' WHERE p.`pid` = ?',
            [$pid]
        );
        if (!is_array($row)) {
            return array_fill_keys(array_map(static fn(FieldDefinition $f): string => $f->name, $this->fields()), null);
        }

        $today = $this->clock->now()->format('Y-m-d');
        $first = self::text($row['fname'] ?? null);
        $last = self::text($row['lname'] ?? null);
        $dob = self::text($row['DOB'] ?? null);

        $values = [
            'first_name' => $first,
            'last_name' => $last,
            'full_name' => trim(($first ?? '') . ' ' . ($last ?? '')),
            'mrn' => self::text($row['pubpid'] ?? null),
            'dob' => $dob,
            'age' => $this->age($dob),
            'sex' => self::text($row['sex_title'] ?? null),
            'phone_cell' => self::text($row['phone_cell'] ?? null),
            'phone_home' => self::text($row['phone_home'] ?? null),
            'email' => self::text($row['email'] ?? null),
            'language' => self::text($row['language_title'] ?? null),
            'interpreter_needed' => self::text($row['interpreter_title'] ?? null),
            'portal_enabled' => ($row['allow_patient_portal'] ?? '') === 'YES',
            'pcp' => self::text($row['pcp_name'] ?? null),
            'pharmacy' => self::text($row['pharmacy_name'] ?? null),
            'primary_insurance' => $this->primaryInsurance($pid, $today),
            'last_visit' => $this->lastVisit($pid),
            'next_appointment' => $this->nextAppointment($pid),
            // Only load the balance for users who may see it.
            'balance' => $this->canSee('balance') ? $this->balanceLoader->load($pid)->balance(false) : null,
        ];

        return $this->withoutHiddenFields($values);
    }

    /**
     * @param array<string, string|int|float|bool|null> $values
     * @return array<string, string|int|float|bool|null>
     */
    private function withoutHiddenFields(array $values): array
    {
        foreach ($this->fields() as $field) {
            if ($field->acl !== null && !$this->access->allows($field->acl)) {
                $values[$field->name] = null;
            }
        }
        return $values;
    }

    private function canSee(string $fieldName): bool
    {
        foreach ($this->fields() as $field) {
            if ($field->name === $fieldName) {
                return $field->acl === null || $this->access->allows($field->acl);
            }
        }
        return false;
    }

    private function age(?string $dob): ?string
    {
        if ($dob === null || $dob === '0000-00-00') {
            return null;
        }
        $born = \DateTimeImmutable::createFromFormat('!Y-m-d', $dob);
        if ($born === false) {
            return null;
        }
        return (string) $born->diff($this->clock->now())->y;
    }

    private function primaryInsurance(int $pid, string $today): ?string
    {
        $name = QueryUtils::fetchSingleValue(
            'SELECT ic.`name` FROM `insurance_data` i'
            . ' JOIN `insurance_companies` ic ON ic.`id` = i.`provider`'
            . ' WHERE i.`pid` = ? AND i.`type` = "primary"'
            . ' AND (i.`date` IS NULL OR i.`date` <= ?)'
            . ' AND (i.`date_end` IS NULL OR i.`date_end` >= ?)'
            . ' ORDER BY i.`date` DESC LIMIT 1',
            'name',
            [$pid, $today, $today]
        );
        return self::text($name);
    }

    private function lastVisit(int $pid): ?string
    {
        $date = QueryUtils::fetchSingleValue(
            'SELECT DATE(MAX(`date`)) AS last_visit FROM `form_encounter` WHERE `pid` = ?',
            'last_visit',
            [$pid]
        );
        return self::text($date);
    }

    /**
     * The next appointment from now that isn't canceled or a no-show.
     */
    private function nextAppointment(int $pid): ?string
    {
        $now = $this->clock->now();
        $next = QueryUtils::fetchSingleValue(
            'SELECT CONCAT(`pc_eventDate`, " ", LEFT(`pc_startTime`, 5)) AS next_appt'
            . ' FROM `openemr_postcalendar_events`'
            . ' WHERE `pc_pid` = ?'
            . ' AND (`pc_eventDate` > ? OR (`pc_eventDate` = ? AND `pc_startTime` >= ?))'
            . ' AND `pc_apptstatus` NOT IN ("x", "%", "?")'
            . ' ORDER BY `pc_eventDate`, `pc_startTime` LIMIT 1',
            'next_appt',
            [$pid, $now->format('Y-m-d'), $now->format('Y-m-d'), $now->format('H:i:s')]
        );
        return self::text($next);
    }

    private static function text(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value === '' ? null : $value;
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        return null;
    }
}
