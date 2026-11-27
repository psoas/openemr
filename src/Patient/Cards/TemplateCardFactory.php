<?php

/**
 * Decides whether a dashboard card is drawn by core or by an
 * admin-uploaded template.
 *
 * Returns the core card unchanged unless the experimental global is on and
 * a template is published for the card's slot.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Patient\Cards;

use OpenEMR\BC\ServiceContainer;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Events\Patient\Summary\Card\CardInterface;
use OpenEMR\UserTemplates\AclMainAccessChecker;
use OpenEMR\UserTemplates\Dataset\DashboardCardDataset;
use OpenEMR\UserTemplates\SandboxedTemplateRenderer;
use OpenEMR\UserTemplates\UserTemplateRepository;

final class TemplateCardFactory
{
    public const GLOBAL_SETTING = 'enable_user_templates';

    public static function forSlot(string $slot, mixed $pid, CardInterface $coreCard): CardInterface
    {
        if (!OEGlobalsBag::getInstance()->getBoolean(self::GLOBAL_SETTING)) {
            return $coreCard;
        }
        $patientId = filter_var($pid, FILTER_VALIDATE_INT);
        if (!is_int($patientId) || $patientId <= 0) {
            return $coreCard;
        }
        $template = (new UserTemplateRepository())->findActive(DashboardCardDataset::SCOPE, $slot);
        if ($template === null) {
            return $coreCard;
        }
        return new TemplateCard(
            $patientId,
            $template,
            $coreCard,
            new DashboardCardDataset(new AclMainAccessChecker(), ServiceContainer::getClock()),
            new SandboxedTemplateRenderer(),
            ServiceContainer::getLogger(),
        );
    }
}
