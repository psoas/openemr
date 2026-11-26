<?php

/**
 * Template manager: lets an administrator replace a dashboard card with a
 * sandboxed Twig template.
 *
 * The admin edits a template, checks it against the dataset's field list,
 * previews it with made-up data, saves it as a draft and publishes it.
 * Staff keep seeing the current card until a draft is published, and
 * "Use the core card" goes back to the card OpenEMR ships.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher
 * @copyright Copyright (c) 2026 David Eschelbacher
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Controllers\Interface\Super;

use OpenEMR\Common\Acl\AccessDeniedHelper;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\UserTemplates\Dataset\DashboardCardDataset;
use OpenEMR\UserTemplates\SandboxedTemplateRenderer;
use OpenEMR\UserTemplates\ScanResult;
use OpenEMR\UserTemplates\TemplateFieldScanner;
use OpenEMR\UserTemplates\TemplateOutputTooLargeException;
use OpenEMR\UserTemplates\UserTemplate;
use OpenEMR\UserTemplates\UserTemplateRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;
use Twig\Error\Error as TwigError;

final readonly class UserTemplateManagerController
{
    public const TEMPLATE = 'super/user_templates.html.twig';

    /** The only card the proof of concept can replace. */
    public const SLOT = 'demographics';

    /** Largest template source accepted, in bytes. */
    private const MAX_SOURCE_BYTES = 50_000;

    public function __construct(
        private UserTemplateRepository $repository,
        private DashboardCardDataset $dataset,
        private TemplateFieldScanner $scanner,
        private SandboxedTemplateRenderer $renderer,
        private Environment $twig,
        private SessionInterface $session,
        private LoggerInterface $logger,
        private string $themeStylesheet,
        private bool $featureEnabled,
    ) {
    }

    public function dispatchAction(Request $request): Response
    {
        if (!AclMain::aclCheckCore('admin', 'super')) {
            AccessDeniedHelper::denyWithTemplate(
                'ACL check failed for admin/super: Template Manager',
                xl('Template Manager')
            );
        }

        $source = null;
        $scan = null;
        $message = null;

        if ($request->getMethod() === 'POST') {
            if (!CsrfUtils::verifyCsrfToken($request->request->getString('csrf_token_form'), $this->session)) {
                CsrfUtils::csrfNotVerified();
            }
            $action = $request->request->getString('action');
            $source = $request->request->getString('source');
            if (strlen($source) > self::MAX_SOURCE_BYTES) {
                $message = xl('The template is too large.');
                $source = substr($source, 0, self::MAX_SOURCE_BYTES);
            } else {
                [$scan, $message] = match ($action) {
                    'check' => [$this->scanner->scan($source, $this->dataset), null],
                    'save_draft' => $this->saveDraft($source),
                    'publish' => $this->publish($request->request->getInt('draft_id')),
                    'revert' => $this->revert(),
                    default => [null, null],
                };
            }
        }

        $published = $this->repository->findPublished($this->dataset->scope(), self::SLOT);
        $draft = $this->repository->findPendingDraft($this->dataset->scope(), self::SLOT);
        $source ??= $draft->source ?? $published->source ?? self::starterTemplate();
        $scan ??= $this->scanner->scan($source, $this->dataset);

        return new Response(
            $this->twig->render(self::TEMPLATE, [
                'csrfToken' => CsrfUtils::collectCsrfToken($this->session),
                'featureEnabled' => $this->featureEnabled,
                'slot' => self::SLOT,
                'source' => $source,
                'message' => $message,
                'published' => $published === null ? null : self::describe($published),
                'draft' => $draft === null ? null : self::describe($draft),
                'issues' => array_map(
                    static fn($issue): array => ['message' => $issue->message, 'line' => $issue->line],
                    $scan->issues
                ),
                'usedFields' => $scan->usedFields,
                'fields' => array_map(
                    fn($field): array => [
                        'name' => $this->dataset->rootName() . '.' . $field->name,
                        'label' => $field->label,
                        'type' => $field->type->name,
                        'sample' => self::sampleText($this->dataset->sample()[$field->name] ?? null),
                    ],
                    $this->dataset->fields()
                ),
                'previewDocument' => $scan->isValid() ? $this->previewDocument($source) : null,
                'datasetVersion' => $this->dataset->version(),
            ]),
            Response::HTTP_OK,
            ['Content-Type' => 'text/html; charset=utf-8']
        );
    }

    /**
     * @return array{ScanResult, string}
     */
    private function saveDraft(string $source): array
    {
        $scan = $this->scanner->scan($source, $this->dataset);
        if (!$scan->isValid()) {
            return [$scan, xl('Fix the problems below before saving.')];
        }
        $this->repository->saveDraft(
            $this->dataset->scope(),
            self::SLOT,
            $source,
            $scan->usedFields,
            $this->dataset->version(),
            $this->currentUserId(),
        );
        return [$scan, xl('Draft saved. Staff still see the current card until you publish.')];
    }

    /**
     * @return array{?ScanResult, string}
     */
    private function publish(int $draftId): array
    {
        $draft = $this->repository->find($draftId);
        if ($draft === null || $draft->slot !== self::SLOT) {
            return [null, xl('That draft no longer exists.')];
        }
        // Check again: the dataset may have changed since the draft was saved.
        $scan = $this->scanner->scan($draft->source, $this->dataset);
        if (!$scan->isValid()) {
            return [$scan, xl('The draft no longer passes the check, so it was not published.')];
        }
        $this->repository->publish($draft);
        $this->logger->info('Dashboard template published', ['template_id' => $draft->id, 'slot' => $draft->slot]);
        return [$scan, xl('Published. The dashboard now uses this template.')];
    }

    /**
     * @return array{null, string}
     */
    private function revert(): array
    {
        $this->repository->retirePublished($this->dataset->scope(), self::SLOT);
        $this->logger->info('Dashboard template retired; core card restored', ['slot' => self::SLOT]);
        return [null, xl('The dashboard uses the core card again.')];
    }

    /**
     * A full HTML page for the preview frame. The frame is sandboxed with no
     * scripts allowed, and the page carries its own content security policy.
     */
    private function previewDocument(string $source): ?string
    {
        try {
            $body = $this->renderer->render($source, [$this->dataset->rootName() => $this->dataset->sample()]);
        } catch (TwigError | TemplateOutputTooLargeException $e) {
            $this->logger->warning('Template preview failed', ['exception' => $e]);
            return null;
        }
        return '<!doctype html><html><head><meta charset="utf-8">'
            . '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; '
            . 'style-src \'self\' http: https: \'unsafe-inline\'; font-src \'self\' http: https: data:; img-src data:">'
            . '<link rel="stylesheet" href="' . attr($this->themeStylesheet) . '">'
            . '</head><body class="bg-white p-2"><section class="card"><div class="card-body p-1">'
            . '<div class="clearfix pt-2">' . $body . '</div></div></section></body></html>';
    }

    private function currentUserId(): int
    {
        $id = filter_var($this->session->get('authUserID'), FILTER_VALIDATE_INT);
        if (!is_int($id)) {
            throw new \LogicException('No signed-in user');
        }
        return $id;
    }

    /**
     * @return array{id: int, by: string, at: string, usedFields: list<string>}
     */
    private static function describe(UserTemplate $template): array
    {
        return [
            'id' => $template->id,
            'by' => $template->createdByName,
            'at' => $template->createdAt->format('Y-m-d H:i'),
            'usedFields' => $template->usedFields,
        ];
    }

    private static function sampleText(string|int|float|bool|null $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }

    private static function starterTemplate(): string
    {
        return <<<'TWIG'
<div class="container-fluid small">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <strong class="h6 mb-0">{{ patient.full_name }}</strong>
      <span class="text-muted ml-1">{{ patient.age }} yrs &middot; {{ patient.sex }}</span>
    </div>
    {% if patient.portal_enabled %}
      <span class="badge badge-success">Portal</span>
    {% endif %}
  </div>
  <div class="row">
    <div class="col-4 text-muted">DOB</div>
    <div class="col-8">{{ patient.dob|date('m/d/Y') }} &middot; MRN {{ patient.mrn|default('none') }}</div>
    <div class="col-4 text-muted">Cell</div>
    <div class="col-8">{{ patient.phone_cell|phone|default('none') }}</div>
    <div class="col-4 text-muted">Email</div>
    <div class="col-8">{{ patient.email|default('none') }}</div>
    <div class="col-4 text-muted">Language</div>
    <div class="col-8">
      {{ patient.language|default('Not recorded') }}
      {% if patient.interpreter_needed == 'Yes' %}<span class="badge badge-warning">Interpreter</span>{% endif %}
    </div>
    <div class="col-4 text-muted">PCP</div>
    <div class="col-8">{{ patient.pcp|default('none') }}</div>
    <div class="col-4 text-muted">Pharmacy</div>
    <div class="col-8">{{ patient.pharmacy|default('none') }}</div>
    <div class="col-4 text-muted">Insurance</div>
    <div class="col-8">{{ patient.primary_insurance|default('Self-pay') }}</div>
    <div class="col-4 text-muted">Next visit</div>
    <div class="col-8">{{ patient.next_appointment ? patient.next_appointment|date('D m/d g:i A') : 'none scheduled' }}</div>
    {% if patient.balance is not null and patient.balance > 0 %}
      <div class="col-4 text-muted">Balance</div>
      <div class="col-8 text-danger">${{ patient.balance|number_format(2) }}</div>
    {% endif %}
  </div>
</div>
TWIG;
    }
}
