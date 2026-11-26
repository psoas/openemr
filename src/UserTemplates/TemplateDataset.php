<?php

/**
 * The data a family of templates may use. Each dataset declares its fields
 * and a version; templates are checked against that list when uploaded, so
 * a template can only rely on what the dataset promises.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\UserTemplates;

interface TemplateDataset
{
    /**
     * Where templates for this dataset are used, e.g. dashboard_card.
     */
    public function scope(): string;

    /**
     * The variable the template reads fields from, e.g. "patient" for patient.dob.
     */
    public function rootName(): string;

    /**
     * Raised whenever a field is removed or changes meaning.
     */
    public function version(): int;

    /**
     * @return list<FieldDefinition>
     */
    public function fields(): array;

    /**
     * Made-up values for every field, used to preview a template without a real patient.
     *
     * @return array<string, string|int|float|bool|null>
     */
    public function sample(): array;
}
