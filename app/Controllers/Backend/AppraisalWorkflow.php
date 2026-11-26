<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Entities\Appraisal;
use App\Exceptions\UnAuthorizedException;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalAuditLog;
use App\Services\AppraisalWorkflowServices;
use App\Services\AppraisalScoringServices;
use App\Services\AppraisalCycleServices;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AppraisalWorkflow extends BaseController
{
    protected AppraisalWorkflowServices $workflowService;
    protected AppraisalScoringServices $scoringService;
    protected AppraisalCycleServices $cycleService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->model           = new M_Appraisal();
        $this->workflowService = new AppraisalWorkflowServices();
        $this->scoringService  = new AppraisalScoringServices();
        $this->cycleService    = new AppraisalCycleServices();
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
            $appraisal = $this->model->find((int) $id);
            if (! $appraisal) {
                return $this->response->setJSON(message('error', false, 'Form penilaian tidak ditemukan.'));
            }

            $this->cycleService->assertCycleModifiable((int) $appraisal->md_appraisal_cycle_id);

            $userId = $this->assertWorkflowActor($appraisal, 'EVALUATOR');

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
        } catch (\Throwable $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * POST /sas/penilaian/reviewReviewer/(:num)
     * Reviewer reviews and forwards form to employee.
     */
    public function reviewReviewer($id = null)
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

        $post            = $this->request->getPost();
        $reviewerComment = $post['reviewer_comment'] ?? $this->request->getVar('reviewer_comment');

        try {
            $appraisal = $this->model->find((int) $id);
            if (! $appraisal) {
                return $this->response->setJSON(message('error', false, 'Form penilaian tidak ditemukan.'));
            }

            $this->cycleService->assertCycleModifiable((int) $appraisal->md_appraisal_cycle_id);

            $userId = $this->assertWorkflowActor($appraisal, 'REVIEWER');

            // Optional save revised responses by Reviewer if provided
            $responseList = [];
            if (! empty($post['responses'])) {
                $responseList = is_string($post['responses']) ? json_decode($post['responses'], true) : $post['responses'];
            } elseif (! empty($post['table'])) {
                $responseList = is_string($post['table']) ? json_decode($post['table'], true) : $post['table'];
            }

            if (is_array($responseList) && ! empty($responseList)) {
                $this->scoringService->saveResponses((int) $id, $responseList, $userId, 'REVIEWER');
            }

            $appraisal = $this->workflowService->reviewByReviewer((int) $id, $userId, $reviewerComment);

            return $this->response->setJSON(message('success', true, 'Review evaluasi oleh Peninjau berhasil disimpan.', $appraisal));
        } catch (\Throwable $e) {
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
            $appraisal = $this->model->find((int) $id);
            if (! $appraisal) {
                return $this->response->setJSON(message('error', false, 'Data penilaian tidak ditemukan.'));
            }

            $userId    = $this->assertWorkflowActor($appraisal, 'EMPLOYEE');
            $appraisal = $this->workflowService->acknowledgeByEmployee((int) $id, $userId, $employeeComment);

            return $this->response->setJSON(message('success', true, 'Penilaian kinerja berhasil ditandatangani dan difinalisasi.', $appraisal));
        } catch (\Throwable $e) {
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
            $appraisal = $this->model->find((int) $id);
            if (! $appraisal) {
                return $this->response->setJSON(message('error', false, 'Form penilaian tidak ditemukan.'));
            }

            $userId       = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
            $currentEmpId = (int) ($this->session->get('md_employee_id') ?? 0);
            if ($currentEmpId <= 0 && $userId > 0) {
                $userRow = \Config\Database::connect()->table('sys_user')
                    ->select('md_employee_id')
                    ->where('sys_user_id', $userId)
                    ->get()
                    ->getRowArray();
                $currentEmpId = (int) ($userRow['md_employee_id'] ?? 0);
            }
            $role         = $this->access->getRole();
            $roleName     = $role ? $role->getName() : '';
            $isAdmin      = (bool) $this->access->getUserRoleName($userId, 'W_Emp_All_Data')
                || (bool) $this->access->getUserRoleName($userId, 'W_Emp_Admin')
                || in_array($roleName, ['Admin', 'Super HRD', 'DEVELOPER', 'ADMINISTRATOR'], true);

            $isParticipant = $currentEmpId > 0 && in_array($currentEmpId, [
                (int) $appraisal->md_employee_id,
                (int) $appraisal->snapshot_evaluator_id,
                (int) $appraisal->snapshot_reviewer_id,
            ], true);
            $isDuta = ! $isParticipant && $this->workflowService->isDutaForEmployeeWithoutUser($userId, (int) $appraisal->md_employee_id);

            if (! $isAdmin && ! $isParticipant && ! $isDuta) {
                throw new UnAuthorizedException('Anda tidak memiliki hak akses untuk melihat riwayat log penilaian ini.');
            }

            $logs = $this->workflowService->getAuditLogs((int) $id);

            $res = message('success', true, 'Audit log berhasil dimuat.');
            $res[0]['data'] = $logs;

            return $this->response->setJSON($res);
        } catch (\Throwable $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * Verify that the current user is authorized for the given workflow stage.
     *
     * @throws UnAuthorizedException
     */
    private function assertWorkflowActor(Appraisal $appraisal, string $requiredRole): int
    {
        $userId       = (int) ($this->access->getSessionUser() ?? 1);
        $currentEmpId = (int) ($this->session->get('md_employee_id') ?? 0);
        $role         = $this->access->getRole();
        $roleName     = $role ? $role->getName() : '';
        $isAdmin      = $this->access->getUserRoleName($userId, 'W_Emp_All_Data')
            || in_array($roleName, ['Admin', 'Super HRD', 'DEVELOPER', 'ADMINISTRATOR'], true);

        if ($isAdmin) {
            return $userId;
        }

        if ($requiredRole === 'EVALUATOR' && $currentEmpId > 0 && $currentEmpId === (int) $appraisal->snapshot_evaluator_id) {
            return $userId;
        }

        if ($requiredRole === 'REVIEWER' && $currentEmpId > 0 && $currentEmpId === (int) $appraisal->snapshot_reviewer_id) {
            return $userId;
        }

        if ($requiredRole === 'EMPLOYEE') {
            $targetEmpId = (int) $appraisal->md_employee_id;
            $isDirectEmp = ($currentEmpId > 0 && $currentEmpId === $targetEmpId);
            $isDuta      = (! $isDirectEmp && $this->workflowService->isDutaForEmployeeWithoutUser($userId, $targetEmpId));
            if ($isDirectEmp || $isDuta) {
                return $userId;
            }
            throw new UnAuthorizedException('Anda tidak memiliki hak akses untuk menandatangani penilaian ini.');
        }

        throw new UnAuthorizedException('Anda tidak memiliki hak akses untuk memproses tahapan penilaian ini.');
    }
}