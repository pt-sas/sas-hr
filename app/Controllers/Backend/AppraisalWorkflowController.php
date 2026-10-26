<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalAuditLog;
use App\Services\AppraisalWorkflowService;
use App\Services\AppraisalScoringService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Exception;

class AppraisalWorkflowController extends BaseController
{
    protected AppraisalWorkflowService $workflowService;
    protected AppraisalScoringService $scoringService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->model           = new M_Appraisal();
        $this->workflowService = new AppraisalWorkflowService();
        $this->scoringService  = new AppraisalScoringService();
    }

    /**
     * POST /sas/performance-appraisal/workflow/submitEvaluator/(:num)
     * Evaluator submits completed draft.
     */
    public function submitEvaluator($id = null)
    {
        if (strpos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                if (isset($json['responses']) && is_array($json['responses'])) {
                    $json['responses'] = json_encode($json['responses']);
                }
                $_POST = $json;
            }
        }

        $post             = $this->request->getPost();
        $evaluatorComment = $post['evaluator_comment'] ?? null;
        $developmentPlan  = $post['development_plan'] ?? null;

        try {
            $userId = (int) ($this->access->getSessionUser() ?? 1);

            // Optional save responses if provided
            $responseList = [];
            if (! empty($post['responses'])) {
                $responseList = is_string($post['responses']) ? json_decode($post['responses'], true) : $post['responses'];
            } elseif (! empty($post['table'])) {
                $responseList = is_string($post['table']) ? json_decode($post['table'], true) : $post['table'];
            }

            if (is_array($responseList) && ! empty($responseList)) {
                $this->scoringService->saveResponses((int) $id, $responseList, $userId, 'EVALUATOR');
            }

            $appraisal = $this->workflowService->submitByEvaluator((int) $id, $userId, $evaluatorComment, $developmentPlan);

            return $this->response->setJSON(message('success', true, 'Evaluasi berhasil disubmit ke tahapan berikutnya.', $appraisal));
        } catch (Exception $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * POST /sas/performance-appraisal/workflow/reviewGm/(:num)
     * GM reviews and forwards form to employee.
     */
    public function reviewGm($id = null)
    {
        if (strpos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                if (isset($json['responses']) && is_array($json['responses'])) {
                    $json['responses'] = json_encode($json['responses']);
                }
                $_POST = $json;
            }
        }

        $post      = $this->request->getPost();
        $gmComment = $post['gm_comment'] ?? $this->request->getVar('gm_comment');

        try {
            $userId = (int) ($this->access->getSessionUser() ?? 1);

            // Optional save revised responses by GM if provided
            $responseList = [];
            if (! empty($post['responses'])) {
                $responseList = is_string($post['responses']) ? json_decode($post['responses'], true) : $post['responses'];
            } elseif (! empty($post['table'])) {
                $responseList = is_string($post['table']) ? json_decode($post['table'], true) : $post['table'];
            }

            if (is_array($responseList) && ! empty($responseList)) {
                $this->scoringService->saveResponses((int) $id, $responseList, $userId, 'GM');
            }

            $appraisal = $this->workflowService->reviewByGm((int) $id, $userId, $gmComment);

            return $this->response->setJSON(message('success', true, 'Review evaluasi oleh GM berhasil disimpan.', $appraisal));
        } catch (Exception $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * POST /penilaian/kickbackGm/(:num)
     * POST /performance-appraisal/workflow/kickbackGm/(:any)
     * GM kicks back form to evaluator with revision notes.
     */
    public function kickbackGm($id = null)
    {
        if (strpos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                $_POST = $json;
            }
        }

        $post           = $this->request->getPost();
        $kickbackReason = $post['kickback_reason'] ?? $post['gm_comment'] ?? $post['notes'] ?? $this->request->getVar('kickback_reason');

        try {
            $userId    = (int) ($this->access->getSessionUser() ?? 1);
            $appraisal = $this->workflowService->kickbackByGm((int) $id, $userId, (string) $kickbackReason);

            return $this->response->setJSON(message('success', true, 'Form penilaian berhasil dikembalikan ke Penilai untuk direvisi.', $appraisal));
        } catch (Exception $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * POST /sas/performance-appraisal/workflow/acknowledgeEmployee/(:num)
     * Employee acknowledges form to finalize workflow.
     */
    public function acknowledgeEmployee($id = null)
    {
        if (strpos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                $_POST = $json;
            }
        }

        $post            = $this->request->getPost();
        $employeeComment = $post['employee_comment'] ?? $this->request->getVar('employee_comment');

        try {
            $userId    = (int) ($this->access->getSessionUser() ?? 1);
            $appraisal = $this->workflowService->acknowledgeByEmployee((int) $id, $userId, $employeeComment);

            return $this->response->setJSON(message('success', true, 'Penilaian kinerja berhasil ditandatangani dan difinalisasi.', $appraisal));
        } catch (Exception $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * GET/POST /sas/performance-appraisal/workflow/auditLogs/(:num)
     * View full audit history log for an appraisal.
     */
    public function auditLogs($id = null)
    {
        try {
            $logs = $this->workflowService->getAuditLogs((int) $id);

            return $this->response->setJSON(message('success', true, 'Audit log berhasil dimuat.', $logs));
        } catch (Exception $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }
}