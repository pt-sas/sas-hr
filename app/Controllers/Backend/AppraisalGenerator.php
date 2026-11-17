<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Entities\Appraisal;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalResponse;
use App\Services\AppraisalGeneratorServices;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Exception;

class AppraisalGenerator extends BaseController
{
    protected AppraisalGeneratorServices $generatorService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->model            = new M_Appraisal();
        $this->modelDetail      = new M_AppraisalResponse();
        $this->entity           = new Appraisal();
        $this->generatorService = new AppraisalGeneratorServices();
    }

    /**
     * Datatable fetch for generated appraisals
     */
    public function showAll()
    {
        if ($this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method Not Allowed']);
        }

        $table         = $this->model->table;
        $select        = $table . '.*';
        $column_order  = [null, 'md_appraisal_id', 'md_appraisal_cycle_id', 'md_employee_id', 'status', 'final_score', 'rating'];
        $order         = ['md_appraisal_id' => 'DESC'];
        $column_search = ['status', 'rating'];

        $list   = $this->datatable->getDatatables($table, $select, $column_order, $order, $column_search);
        $output = [
            'draw'            => (int) ($this->request->getPost('draw') ?? 0),
            'recordsTotal'    => $this->datatable->countAll($table, $select, $column_order, $order, $column_search),
            'recordsFiltered' => $this->datatable->countFiltered($table, $select, $column_order, $order, $column_search),
            'data'            => $list,
        ];

        return $this->response->setJSON($output);
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

            return $this->response->setJSON(message(
                'success',
                true,
                "Appraisal batch processing completed. Generated: {$result['generated']}, Skipped: {$result['skipped']}.",
                $result
            ));
        } catch (Exception $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }
}