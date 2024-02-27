<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AccessMenu;
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

            $arrEmployee = $this->model->getChartEmployee($this->session->get("md_employee_id"));
            $where['md_employee.md_employee_id'] = [
                'value'     => $arrEmployee
            ];

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->md_employee_id;
                $fullName = $value->fullname;

                $number++;
                // $path = $this->PATH_UPLOAD . $this->PATH_Karyawan . '/';
                $path = $path = 'uploads/' . $this->PATH_Karyawan . '/';

                $row[] = $ID;
                $row[] = $number;
                $row[] = imageShow($path, $value->image, $fullName);
                $row[] = $value->value;
                $row[] = $fullName;
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
            $file = $this->request->getFile('image');

            try {
                $img_name = "";

                //TODO: Set null data for gender combobox not choose
                if (!isset($post['gender']))
                    $post['gender'] = "";

                if ($file && $file->isValid()) {
                    $img_name = $file->getName();
                    $post['image'] = $img_name;
                }

                if (!$this->validation->run($post, 'employee')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $path = $this->PATH_UPLOAD . $this->PATH_Karyawan . '/';

                    if ($this->isNew()) {
                        uploadFile($file, $path);
                    } else {
                        $row = $this->model->find($this->getID());

                        if ($post['image'] !== $row->getImage()) {
                            if (file_exists($path . $row->getImage())) {
                                unlink($path . $row->getImage());
                                $response = $file->move($path);
                            }
                        }
                    }

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

                $path = $this->PATH_UPLOAD . $this->PATH_Karyawan . '/';

                if (file_exists($path . $list[0]->image)) {
                    $path = 'uploads/' . $this->PATH_Karyawan . '/';
                    $list[0]->image = $path . $list[0]->image;
                } else {
                    $list[0]->image = "";
                }


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
                    $list = $this->field->setDataSelect($mEmpBranch->table, $list, $mBranch->primaryKey, $mBranch->primaryKey, $mBranch->primaryKey, $rowBranch);
                }

                if ($rowDiv) {
                    $list = $this->field->setDataSelect($mEmpDiv->table, $list, $mDiv->primaryKey, $mDiv->primaryKey, $mDiv->primaryKey, $rowDiv);
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
                $result = $this->delete($id);
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

            return $this->response->setJSON($response);
        }
    }

    public function getList()
    {
        $mAccess = new M_AccessMenu($this->request);

        if ($this->request->isAjax()) {
            $post = $this->request->getVar();

            $response = [];

            try {
                $arrEmployee = $this->model->getChartEmployee($this->session->get("md_employee_id"));
                $access = $mAccess->getAccess($this->session->get("sys_user_id"));

                if (isset($post['search'])) {
                    if (!empty($post['name'])) {
                        if ($access && isset($access["branch"]) && isset($access["division"])) {
                            $arr = $this->model->getEmployeeBased($access["branch"], $access["division"]);

                            if ($arrEmployee) {
                                $arr = array_unique(array_merge($arr, $arrEmployee));

                                $list = $this->model->where('isactive', 'Y')
                                    ->whereIn('md_employee_id', $arr)
                                    ->orderBy('value', 'ASC')
                                    ->findAll();
                            } else {
                                $list = $this->model->where('isactive', 'Y')
                                    ->whereIn('md_employee_id', $arr)
                                    ->orderBy('value', 'ASC')
                                    ->findAll();
                            }
                        }
                    } else {
                        $list = $this->model->where('isactive', 'Y')
                            ->like('value', $post['search'])
                            ->orderBy('value', 'ASC')
                            ->findAll();
                    }
                } else if (!empty($post['name'])) {
                    if ($access && isset($access["branch"]) && isset($access["division"])) {
                        $arr = $this->model->getEmployeeBased($access["branch"], $access["division"]);

                        if ($arrEmployee) {
                            $arr = array_unique(array_merge($arr, $arrEmployee));

                            $list = $this->model->where('isactive', 'Y')
                                ->whereIn('md_employee_id', $arr)
                                ->orderBy('value', 'ASC')
                                ->findAll();
                        } else {
                            $list = $this->model->where('isactive', 'Y')
                                ->whereIn('md_employee_id', $arr)
                                ->orderBy('value', 'ASC')
                                ->findAll();
                        }
                    }
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

    public function getNik()
    {
        if ($this->request->isAjax()) {

            $response = [];

            try {
                $nik = $this->model->getLastNik();

                $response["suggestions"][] = [
                    'value' => $nik,
                    'name'  => $nik,
                ];
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}