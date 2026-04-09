<?php

namespace App\Controllers\API;

use App\Controllers\ApiController;
use App\Models\M_Menu;
use App\Models\M_User;
use App\Models\M_WActivity;
use App\Services\WActivityServices as ServicesWActivityServices;

class WActivity extends ApiController
{
    public function index()
    {
        $model = new M_WActivity($this->request);
        $mMenu = new M_Menu($this->request);
        $mUser = new M_User($this->request);

        $list = $model->getActivity(null, null, $this->jwt->sys_user_id);

        $allUser = array_column($mUser->where('isactive', 'Y')->findAll(), null, 'sys_user_id');

        $data = [];
        foreach ($list as $value) {
            $ID = $value->sys_wfactivity_id;
            $record_id = $value->record_id;
            $table = $value->table;
            $menu = $value->menu;
            $tableLine = $value->tableline;
            $recordLine_id = $value->recordline_id;

            $menuName = ucwords($mMenu->getMenuBy($menu));

            if ($tableLine)
                $trx = $model->getDataTrx($table, $recordLine_id, $tableLine);
            else
                $trx = $model->getDataTrx($table, $record_id);

            $node = "Approval {$menuName}";
            $created_at = "";

            if ($trx && is_null($tableLine)) {
                $created_at = format_dmytime($trx->created_at, "-");
                $summary = "{$menuName} {$trx->documentno} : {$trx->usercreated_by}";
            } else if ($trx && $tableLine) {
                $created_at = format_dmytime($trx->created_at, "-");

                if ($value->table == "trx_overtime") {
                    $date = format_dmy($trx->startdate, "-");
                } else {
                    $date = format_dmy($trx->date, "-");
                }
                $summary = "{$menuName} {$trx->documentno} [$trx->value / {$date}] : {$trx->usercreated_by}";
            } else {
                $summary = "{$menuName} {$record_id}";
            }

            $responsible = $value->wfresponsible;
            $scenario = $value->scenario;
            $scenario = "{$scenario} [{$responsible}]";

            $data[] = [
                'sys_wfactivity_id' => (int) $ID,
                'documentno' => $trx->documentno,
                'node' => $node,
                'created_by' => $allUser[$trx->created_by]->name,
                'created_at' => $created_at,
                'scenario' => $scenario,
                'summary' => $summary
            ];
        }

        return $this->respond(apiResponse(true, "success", $data));
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        $status_code = null;
        try {
            if (!empty($data)) {
                $services = new ServicesWActivityServices($this->jwt->sys_user_id);

                $result = $services->processActivity($id, $data);

                if (!$result['status'])
                    $status_code = 409;

                $response = apiResponse($result['status'], $result['status'] ? 'Berhasil disimpan' : $result['message']);
            } else {
                $response = apiResponse(false, "Unsupported Media");
                $status_code = 415;
            }
        } catch (\Exception $e) {
            log_message('error', 'WAcitivty [update] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response    = apiResponse(false, "Internal Server Error");
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }
}
