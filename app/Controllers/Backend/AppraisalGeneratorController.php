<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Entities\Appraisal;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalResponse;
use App\Services\AppraisalGeneratorService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Exception;

class AppraisalGeneratorController extends BaseController
{
    protected AppraisalGeneratorService $generatorService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->model            = new M_Appraisal();
        $this->modelDetail      = new M_AppraisalResponse();
        $this->entity           = new Appraisal();
        $this->generatorService = new AppraisalGeneratorService();
    }

    /**
     * Datatable fetch for generated appraisals
     */
    public function showAll()
    {
        $list = $this->datatable->getDatatables();
        return $this->response->setJSON($list);
    }

    /**
     * POST /sas/performance-appraisal/generate
     * Batch generate forms for a specific open cycle.
     */
    public function generate()
    {
        $cycleId = (int) ($this->request->getVar('appraisal_cycle_id') ?? 0);

        if (empty($cycleId)) {
            return $this->response->setJSON(message('error', false, 'appraisal_cycle_id is required.'));
        }

        try {
            $userId = $this->access->getSessionUser();
            $result = $this->generatorService->generateBatchForCycle($cycleId, $userId);

            return $this->response->setJSON([
                'status'  => true,
                'message' => "Appraisal batch processing completed. Generated: {$result['generated']}, Skipped: {$result['skipped']}.",
                'data'    => $result,
            ]);
        } catch (Exception $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }
}