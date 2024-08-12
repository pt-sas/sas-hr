<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AccessMenu;
use App\Models\M_Branch;
use App\Models\M_Country;
use App\Models\M_Division;
use App\Models\M_Employee;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use App\Models\M_Levelling;
use App\Models\M_Position;
use App\Models\M_Reference;
use App\Models\M_ReferenceDetail;
use App\Models\M_Role;
use App\Models\M_Status;
use App\Models\M_Supplier;
use Config\Services;

class Outsourcing extends BaseController
{
    protected $PATH_Karyawan = "karyawan";

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Employee($this->request);
        $this->entity = new \App\Entities\Employee();
    }

    public function index()
    {
        $mReference = new M_Reference($this->request);
        $mRole = new M_Role($this->request);
        $mBranch = new M_Branch($this->request);
        $mDiv = new M_Division($this->request);

        $data = [
            'ref_list' => $mReference->findBy([
                'sys_reference.name'              => 'Gender',
                'sys_reference.isactive'          => 'Y',
                'sys_ref_detail.isactive'         => 'Y',
                'sys_ref_detail.value <>'         => 'A',
            ], null, [
                'field'     => 'sys_ref_detail.name',
                'option'    => 'ASC'
            ])->getResult(),
            'role'        => $mRole->where('isactive', 'Y')
                ->orderBy('name', 'ASC')
                ->findAll(),
            'branch'      => $mBranch->where('isactive', 'Y')
                ->orderBy('name', 'ASC')
                ->findAll(),
            'division'    => $mDiv->where('isactive', 'Y')
                ->orderBy('name', 'ASC')
                ->findAll()
        ];

        return $this->template->render('masterdata/outsourcing/v_outsourcing', $data);
    }

    public function showAll()
    {
        $mAccess = new M_AccessMenu($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->model->column_order;
            $sort = $this->model->order;
            $search = $this->model->column_search;

            $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
            $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
            $arrEmployee = $this->model->getChartEmployee($this->session->get('md_employee_id'));

            if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
                $arrBranch = $arrAccess["branch"];
                $arrDiv = $arrAccess["division"];

                $arrEmpBased = $this->model->getEmployeeBased($arrBranch, $arrDiv);

                if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));

                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['md_employee.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['md_employee.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['md_employee.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['md_employee.md_supplier_id <>'] = 0;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->md_employee_id;
                $fullName = $value->fullname;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->value;
                $row[] = $fullName;
                $row[] = $value->pob;
                $row[] = format_dmy($value->birthday, "-");
                $row[] = $value->gender_name;
                $row[] = active($value->isactive);
                $row[] = $this->template->tableButton($ID);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            try {
                //TODO: Set null data for gender combobox not choose
                if (!isset($post['gender']))
                    $post['gender'] = "";

                if (!$this->validation->run($post, 'outsourcing')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $this->entity->fill($post);

                    if ($this->entity->getIsSameAddress() === "Y") {
                        $this->entity->setAddress($this->entity->getAddressDom());
                        $this->entity->setCountryId($this->entity->getCountryDomId());
                        $this->entity->setProvinceId($this->entity->getProvinceDomId());
                        $this->entity->setCityId($this->entity->getCityDomId());
                        $this->entity->setDistrictId($this->entity->getDistrictDomId());
                        $this->entity->setSubDistrictId($this->entity->getSubDistrictDomId());
                        $this->entity->setPostalCode($this->entity->getPostalCodeDom());
                    }

                    $response = $this->save();

                    if (isset($response[0]["success"])) {
                        $id = $this->getID();

                        if ($this->isNew()) {
                            $id = $this->insertID;
                            $response[0]["foreignkey"] = $id;
                        }
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function show($id)
    {
        $mRefDetail = new M_ReferenceDetail($this->request);
        $mStatus = new M_Status($this->request);
        $mPosition = new M_Position($this->request);
        $mLeveling = new M_Levelling($this->request);
        $mCountry = new M_Country($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDiv = new M_EmpDivision($this->request);
        $mBranch = new M_Branch($this->request);
        $mDiv = new M_Division($this->request);
        $mSupplier = new M_Supplier($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                if (!empty($list[0]->getStatusId())) {
                    $rowStatus = $mStatus->find($list[0]->getStatusId());
                    $list = $this->field->setDataSelect($mStatus->table, $list, $mStatus->primaryKey, $rowStatus->getStatusId(), $rowStatus->getName());
                }

                if (!empty($list[0]->getPositionId())) {
                    $rowPosition = $mPosition->find($list[0]->getPositionId());
                    $list = $this->field->setDataSelect($mPosition->table, $list, $mPosition->primaryKey, $rowPosition->getPositionId(), $rowPosition->getName());
                }

                if (!empty($list[0]->getLevellingId())) {
                    $rowLevel = $mLeveling->find($list[0]->getLevellingId());
                    $list = $this->field->setDataSelect($mLeveling->table, $list, $mLeveling->primaryKey, $rowLevel->getLevellingId(), $rowLevel->getName());
                }

                if (!empty($list[0]->getCountryId())) {
                    $rowCountry = $mCountry->find($list[0]->getCountryId());
                    $list = $this->field->setDataSelect($mCountry->table, $list, $mCountry->primaryKey, $rowCountry->getCountryId(), $rowCountry->getName());
                }

                if (!empty($list[0]->getCountryDomId())) {
                    $rowCountryDom = $mCountry->find($list[0]->getCountryDomId());
                    $list = $this->field->setDataSelect($mCountry->table, $list, "md_country_dom_id", $rowCountryDom->getCountryId(), $rowCountryDom->getName());
                }

                if (!empty($list[0]->getSuperiorId())) {
                    $rowSuperior = $this->model->find($list[0]->getSuperiorId());
                    $list = $this->field->setDataSelect($this->model->table, $list, "superior_id", $rowSuperior->getEmployeeId(), $rowSuperior->getFullName());
                }

                if (!empty($list[0]->getNationality())) {
                    $rowNationality = $mRefDetail->where("name", $list[0]->getNationality())->first();
                    $list = $this->field->setDataSelect($mRefDetail->table, $list, "nationality", $rowNationality->getValue(), $rowNationality->getName());
                }

                $rowBranch = $mEmpBranch->where($this->model->primaryKey, $id)->findAll();
                $rowDiv = $mEmpDiv->where($this->model->primaryKey, $id)->findAll();

                if ($rowBranch) {
                    $list = $this->field->setDataSelect($mEmpBranch->table, $list, $mBranch->primaryKey, $mBranch->primaryKey, $mBranch->primaryKey, $rowBranch);
                }

                if ($rowDiv) {
                    $list = $this->field->setDataSelect($mEmpDiv->table, $list, $mDiv->primaryKey, $mDiv->primaryKey, $mDiv->primaryKey, $rowDiv);
                }

                if (!empty($list[0]->getSupplierId())) {
                    $rowSupplier = $mSupplier->find($list[0]->getSupplierId());
                    $list = $this->field->setDataSelect($mSupplier->table, $list, $mSupplier->primaryKey, $rowSupplier->getSupplierId(), $rowSupplier->getName());
                }

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($list[0]->getFullName());
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setField([$mBranch->primaryKey, $mDiv->primaryKey]);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader)
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function destroy($id)
    {
        if ($this->request->isAJAX()) {
            try {
                $result = $this->delete($id);
                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
