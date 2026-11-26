<?php

/**
 * Dashboard template manager.
 *
 * Entry point only: wires UserTemplateManagerController, which holds the
 * page logic, with its markup in templates/super/user_templates.html.twig.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

require_once('../globals.php');

use OpenEMR\BC\ServiceContainer;
use OpenEMR\Common\Http\CurrentRequest;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Controllers\Interface\Super\UserTemplateManagerController;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Patient\Cards\TemplateCardFactory;
use OpenEMR\UserTemplates\AclMainAccessChecker;
use OpenEMR\UserTemplates\Dataset\DashboardCardDataset;
use OpenEMR\UserTemplates\SandboxedTemplateRenderer;
use OpenEMR\UserTemplates\TemplateFieldScanner;
use OpenEMR\UserTemplates\UserTemplateRepository;

$globals = OEGlobalsBag::getInstance();
$renderer = new SandboxedTemplateRenderer();

(new UserTemplateManagerController(
    new UserTemplateRepository(),
    new DashboardCardDataset(new AclMainAccessChecker(), ServiceContainer::getClock()),
    new TemplateFieldScanner($renderer),
    $renderer,
    ServiceContainer::getTwig(),
    SessionWrapperFactory::getInstance()->getActiveSession(),
    ServiceContainer::getLogger(),
    $globals->getString('css_header'),
    $globals->getBoolean(TemplateCardFactory::GLOBAL_SETTING),
))->dispatchAction(CurrentRequest::get())->send();
