<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_BloodType;
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
use App\Models\M_Religion;
use App\Models\M_Role;
use App\Models\M_Status;
use Config\Services;

class Employee extends BaseController
{
    protected $folder = "karyawan/";

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

        return $this->template->render('masterdata/employee/v_employee', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->model->column_order;
            $sort = $this->model->order;
            $search = $this->model->column_search;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->md_employee_id;

                $number++;

                $path = 'upload/';

                $row[] = $ID;
                $row[] = $number;
                $row[] = imageShow($path, $value->image);
                $row[] = $value->value;
                $row[] = $value->fullname;
                $row[] = $value->pob;
                $row[] = format_dmy($value->birthday, "-");
                $row[] = $value->gender_name;
                $row[] = $value->religion_name;
                $row[] = active($value->isactive);
                $row[] = $this->template->tableButton($ID);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $file = $this->request->getFile('image');

            try {
                $img_name = "";
                $post['image'] = "";

                if ($file && $file->isValid()) {
                    $img_name = $file->getName();
                    $post['image'] = $img_name;
                }

                if (!$this->validation->run($post, 'employee')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $path = FCPATH . 'upload';
                    // $path = WRITEPATH . "uploads/" . $this->folder;

                    if (!is_dir($path))
                        mkdir($path);

                    $file->move($path);
                    // $response = uploadFile($file, $path, $img_name);

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

            // return $this->response->setJSON($imgName);
            return json_encode($response);
        }
    }

    public function show($id)
    {
        $mRefDetail = new M_ReferenceDetail($this->request);
        $mReligion = new M_Religion($this->request);
        $mBlood = new M_BloodType($this->request);
        $mStatus = new M_Status($this->request);
        $mPosition = new M_Position($this->request);
        $mLeveling = new M_Levelling($this->request);
        $mCountry = new M_Country($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDiv = new M_EmpDivision($this->request);
        $mBranch = new M_Branch($this->request);
        $mDiv = new M_Division($this->request);

        if ($this->request->isAJAX()) {

            try {

                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                $list[0]->image = 'upload/' . $list[0]->image;

                if (!empty($list[0]->getReligionId())) {
                    $rowFeligion = $mReligion->find($list[0]->getReligionId());
                    $list = $this->field->setDataSelect($mReligion->table, $list, $mReligion->primaryKey, $rowFeligion->getReligionId(), $rowFeligion->getName());
                }

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

                if (!empty($list[0]->getMaritalStatus())) {
                    $rowMarital = $mRefDetail->where("name", $list[0]->getMaritalStatus())->first();
                    $list = $this->field->setDataSelect($mRefDetail->table, $list, "marital_status", $rowMarital->getValue(), $rowMarital->getName());
                }

                if (!empty($list[0]->getHomeStatus())) {
                    $rowHome = $mRefDetail->where("name", $list[0]->getHomeStatus())->first();
                    $list = $this->field->setDataSelect($mRefDetail->table, $list, "homestatus", $rowHome->getValue(), $rowHome->getName());
                }

                $rowBranch = $mEmpBranch->where($this->model->primaryKey, $id)->findAll();
                $rowDiv = $mEmpDiv->where($this->model->primaryKey, $id)->findAll();

                if ($rowBranch) {
                    $list = $this->field->setDataSelect($mEmpBranch->table, $list, $mBranch->primaryKey, $mBranch->primaryKey, "name", $rowBranch);
                }

                if ($rowBranch) {
                    $list = $this->field->setDataSelect($mEmpDiv->table, $list, $mDiv->primaryKey, $mDiv->primaryKey, "name", $rowDiv);
                }

                if (!empty($list[0]->getRhesus())) {
                    $rowRhesus = $mRefDetail->where("name", $list[0]->getRhesus())->first();
                    $list = $this->field->setDataSelect($mRefDetail->table, $list, "rhesus", $rowRhesus->getValue(), $rowRhesus->getName());
                }

                if (!empty($list[0]->getBloodTypeId())) {
                    $rowBlood = $mBlood->find($list[0]->getBloodTypeId());
                    $list = $this->field->setDataSelect($mBlood->table, $list, $mBlood->primaryKey, $rowBlood->getBloodTypeId(), $rowBlood->getName());
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
                $result = $this->model->delete($id);
                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getBy($id)
    {
        if ($this->request->isAJAX()) {
            $response = [];

            try {
                $row = $this->model->find($id);
                $response['text'] = $row->fullname;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getDetailEmployee()
    {
        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();
            $response = [];

            try {
                $mEmpBranch = new M_EmpBranch($this->request);
                $mEmpDiv = new M_EmpDivision($this->request);
                $mBranch = new M_Branch($this->request);
                $mDiv = new M_Division($this->request);

                $id = $post["md_employee_id"];
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $rowBranch = $mEmpBranch->where($this->model->primaryKey, $id)->findAll();
                $rowDiv = $mEmpDiv->where($this->model->primaryKey, $id)->findAll();

                $list = $this->field->setDataSelect($mEmpBranch->table, $list, $mBranch->primaryKey, $mBranch->primaryKey, "name", $rowBranch);
                $list = $this->field->setDataSelect($mEmpDiv->table, $list, $mDiv->primaryKey, $mDiv->primaryKey, "name", $rowDiv);

                $response = $list;

                // $response = gettype($rowBranch);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            // return $this->response->setJSON($response);

            return json_encode($response);
        }
    }

    public function getList()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getVar();

            $response = [];

            try {
                if (isset($post['search'])) {
                    $list = $this->model->where('isactive', 'Y')
                        ->like('value', $post['search'])
                        ->orderBy('value', 'ASC')
                        ->findAll();
                } else {
                    $list = $this->model->where('isactive', 'Y')
                        ->orderBy('value', 'ASC')
                        ->findAll();
                }

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->getEmployeeId();
                    $response[$key]['text'] = $row->getValue();
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getSuperior()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getVar();

            $response = [];

            try {
                if (isset($post['search'])) {
                    $list = $this->model->where('isactive', 'Y')
                        ->whereNotIn('md_levelling_id', [100005, 100006])
                        ->like('fullname', $post['search'])
                        ->orderBy('fullname', 'ASC')
                        ->findAll();
                } else {
                    $list = $this->model->where('isactive', 'Y')
                        ->whereNotIn('md_levelling_id', [100005, 100006])
                        ->orderBy('fullname', 'ASC')
                        ->findAll();
                }

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->getEmployeeId();
                    $response[$key]['text'] = $row->getFullName();
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
