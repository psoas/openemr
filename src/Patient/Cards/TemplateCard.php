<?php

/**
 * A dashboard card drawn from an admin-uploaded template.
 *
 * Wraps the core card it replaces: the card frame (title, collapse state,
 * edit button, ACL) comes from the core card, and only the body comes from
 * the sandboxed template. If the template fails (a Twig error, bad data,
 * a database error), the error is logged and the core card is shown
 * instead, so a broken template never breaks the dashboard.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Patient\Cards;

use OpenEMR\Common\Database\SqlQueryException;
use OpenEMR\Events\Patient\Summary\Card\CardInterface;
use OpenEMR\UserTemplates\Dataset\DashboardCardDataset;
use OpenEMR\UserTemplates\SandboxedTemplateRenderer;
use OpenEMR\UserTemplates\TemplateOutputTooLargeException;
use OpenEMR\UserTemplates\UserTemplate;
use Psr\Log\LoggerInterface;
use Twig\Error\Error as TwigError;

final class TemplateCard implements CardInterface
{
    public const TEMPLATE_FILE = 'patient/card/user_template.html.twig';

    /** The rendered body, false once rendering failed, null before the first try. */
    private string|false|null $html = null;

    public function __construct(
        private readonly int $pid,
        private readonly UserTemplate $template,
        private readonly CardInterface $coreCard,
        private readonly DashboardCardDataset $dataset,
        private readonly SandboxedTemplateRenderer $renderer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getBackgroundColorClass(): string
    {
        return $this->coreCard->getBackgroundColorClass();
    }

    public function getTextColorClass(): string
    {
        return $this->coreCard->getTextColorClass();
    }

    public function getTitle(): string
    {
        return $this->coreCard->getTitle();
    }

    public function getIdentifier(): string
    {
        return $this->coreCard->getIdentifier();
    }

    /**
     * @return array<mixed>
     */
    public function getAcl(): array
    {
        return $this->coreCard->getAcl();
    }

    public function isInitiallyCollapsed(): bool
    {
        return $this->coreCard->isInitiallyCollapsed();
    }

    public function canAdd(): bool
    {
        return $this->coreCard->canAdd();
    }

    public function canEdit(): bool
    {
        return $this->coreCard->canEdit();
    }

    public function canCollapse(): bool
    {
        return $this->coreCard->canCollapse();
    }

    public function getTemplateFile(): string
    {
        return $this->renderBody() === false ? $this->coreCard->getTemplateFile() : self::TEMPLATE_FILE;
    }

    /**
     * @return array<mixed>
     */
    public function getTemplateVariables(): array
    {
        $html = $this->renderBody();
        if ($html === false) {
            return $this->coreCard->getTemplateVariables();
        }
        return array_merge($this->coreCard->getTemplateVariables(), [
            'userTemplateHtml' => $html,
            'userTemplateId' => $this->template->id,
        ]);
    }

    private function renderBody(): string|false
    {
        if ($this->html !== null) {
            return $this->html;
        }
        try {
            $this->html = $this->renderer->render(
                $this->template->source,
                [$this->dataset->rootName() => $this->dataset->build($this->pid)]
            );
        } catch (TwigError | TemplateOutputTooLargeException | SqlQueryException $e) {
            $this->logger->error('Dashboard template failed; showing the core card instead', [
                'template_id' => $this->template->id,
                'slot' => $this->template->slot,
                'exception' => $e,
            ]);
            $this->html = false;
        }
        return $this->html;
    }
}
