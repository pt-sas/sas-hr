<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;

class UnprocessedDocuments extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
    }

    public function index()
    {
        return $this->template->render('unprocessdocument/v_unprocess_document');
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = 'v_all_document_draft';
            $select = "*";
            $join = [];
            $order = [];
            $search = [];
            $sort = ['v_all_document_draft.doctype' => 'ASC', 'v_all_document_draft.documentno' => 'ASC'];
            $where = ["v_all_document_draft.docstatus = '{$this->DOCSTATUS_Drafted}'"];

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) {
                $row = [];
                $number++;

                $row[] = $number;
                $row[] = $value->doctype;
                $row[] = $value->documentno;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = $value->created_by;
                $row[] = $this->template->buttonRecordInfo($value->header_id, $value->url);


                $data[] = $row;
            }

            $result = [
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }
}
