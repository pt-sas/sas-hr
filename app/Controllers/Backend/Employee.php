<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AccessMenu;
use App\Models\M_BloodType;
use App\Models\M_Branch;
use App\Models\M_Country;
use App\Models\M_DelegationTransfer;
use App\Models\M_Division;
use App\Models\M_Employee;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDelegation;
use App\Models\M_EmpDivision;
use App\Models\M_Levelling;
use App\Models\M_NotificationText;
use App\Models\M_Position;
use App\Models\M_Reference;
use App\Models\M_ReferenceDetail;
use App\Models\M_Religion;
use App\Models\M_Role;
use App\Models\M_Status;
use App\Models\M_User;
use Html2Text\Html2Text;
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
        $mStatus = new M_Status($this->request);

        $roleEmpAdm = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_Admin');
        $readOnly = "";
        $disabled = "";

        if (is_null($roleEmpAdm)) {
            $readOnly = "readonly";
            $disabled = "disabled";
        }

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
                ->findAll(),
            'role_emp_adm'  => $roleEmpAdm,
            'ptkp_list' => $mReference->findBy([
                'sys_reference.name'              => 'PTKPType',
                'sys_reference.isactive'          => 'Y',
                'sys_ref_detail.isactive'         => 'Y',
                'sys_ref_detail.value <>'         => 'A',
            ], null, [
                'field'     => 'sys_ref_detail.name',
                'option'    => 'DESC'
            ])->getResult(),
            'readonly'  => $readOnly,
            'disabled'  => $disabled,
            'status'    => $mStatus->where('isactive', 'Y')
                ->whereNotIn('md_status_id', [100003, 100006, 100007, 100008]) // Exclude Status OUTSOURCING FREELANCE MAGANG KONTRAK
                ->orderBy('name', 'ASC')
                ->findAll(),
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

            $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
            $employeeId = $this->session->get('md_employee_id');
            $employee = $this->model->find($employeeId);

            if ($employee->md_levelling_id > 100003 && !$roleEmp) {
                $where['md_employee.md_employee_id'] = $employeeId;
            } else {
                $where['md_employee.md_employee_id'] = [
                    'value'     => $this->access->getEmployeeData(false)
                ];
            }

            $where['md_employee.md_supplier_id'] = 0;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->md_employee_id;
                $fullName = $value->fullname;

                $number++;
                $path = 'uploads/' . $this->PATH_Karyawan . '/';

                $row[] = $ID;
                $row[] = $number;
                $row[] = imageShow($path, $value->image, $fullName);
                $row[] = $value->value;
                $row[] = $fullName;
                $row[] = $value->pob;
                $row[] = format_dmy($value->birthday, "-");
                $row[] = $value->gender_name;
                $row[] = $value->religion_name;
                $row[] = $value->status_karyawan;
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
            $mPosition = new M_Position($this->request);
            $mDelegTransfer = new M_DelegationTransfer($this->request);
            $mUser = new M_User($this->request);
            $cMail = new Mail();
            $mNotifText = new M_NotificationText($this->request);

            try {
                $img_name = "";

                //TODO: Set null data for gender combobox not choose
                if (!isset($post['gender']))
                    $post['gender'] = "";

                if ($file && $file->isValid()) {
                    $img_name = $post['nik'] . "." . $file->getExtension();
                    $post['image'] = $img_name;
                }

                if (!$this->validation->run($post, 'employee')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $path = $this->PATH_UPLOAD . $this->PATH_Karyawan . '/';

                    if ($this->isNew()) {
                        if ($file && $file->isValid())
                            uploadFile($file, $path, $img_name);
                    } else {
                        $row = $this->model->find($this->getID());

                        if (empty($post['image']) && !empty($row->getImage()) && file_exists($path . $row->getImage())) {
                            unlink($path . $row->getImage());
                        } else if (!empty($post['image']) && !empty($row->getImage()) && $post['image'] !== $row->getImage() && $file && $file->isValid()) {
                            uploadFile($file, $path, $img_name);
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

                    $position = $mPosition->where('md_position_id', $post['md_position_id'])->first();

                    if ($position->getIsMandatoryDuta() === "Y" && empty($post['md_ambassador_id'])) {
                        $response = message('success', false, 'Duta wajib diisi');
                    } else {
                        $response = $this->save();

                        if (isset($response[0]["success"])) {
                            $id = $this->getID();

                            if ($this->isNew()) {
                                $id = $this->insertID;
                                $response[0]["foreignkey"] = $id;

                                $delegationTransfer = $mDelegTransfer->getInTransitionDelegation("employee_from = {$post['md_ambassador_id']}")->getRow();
                                if (!empty($post['md_ambassador_id']) && $delegationTransfer) {
                                    $dataNotif = $mNotifText->where('name', 'Duta Sedang Tidak Bertugas')->first();
                                    $user = $mUser->where('sys_user_id', $delegationTransfer->user_from)->first();
                                    $managerID = $this->model->getEmployeeManagerID($id);
                                    $emailManager = $mUser->select('email')->where(['md_employee_id' => $managerID, 'isactive' => 'Y'])->first();
                                    $message = $dataNotif->getText();
                                    $message = str_replace(['(Var1)', '(Var2)'], [$user->username, $post['value']], $message);

                                    $subject = $dataNotif->getSubject();
                                    $message = new Html2Text($message);
                                    $message = $message->getText();

                                    if ($emailManager->email) {
                                        $cMail->sendEmail($emailManager->email, $subject, $message, null, "SAS HR");
                                    }
                                }
                            }
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
        $mEmpDelegation = new M_EmpDelegation($this->request);
        $mUser = new M_User($this->request);

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
                $rowAmbassador = $mEmpDelegation->where($this->model->primaryKey, $id)->first();

                if ($rowBranch) {
                    $list = $this->field->setDataSelect($mEmpBranch->table, $list, $mBranch->primaryKey, $mBranch->primaryKey, $mBranch->primaryKey, $rowBranch);
                }

                if ($rowDiv) {
                    $list = $this->field->setDataSelect($mEmpDiv->table, $list, $mDiv->primaryKey, $mDiv->primaryKey, $mDiv->primaryKey, $rowDiv);
                }

                if ($rowAmbassador) {
                    $userAmbassador = $mUser->where('sys_user_id', $rowAmbassador->sys_user_id)->first();
                    $empAmbassador = $this->model->where('md_employee_id', $userAmbassador->md_employee_id)->first();
                    $list = $this->field->setDataSelect($this->model->table, $list, 'md_ambassador_id', $empAmbassador->md_employee_id, $empAmbassador->value);
                }

                if (!empty($list[0]->getRhesus())) {
                    $rowRhesus = $mRefDetail->where("name", $list[0]->getRhesus())->first();
                    $list = $this->field->setDataSelect($mRefDetail->table, $list, "rhesus", $rowRhesus->getValue(), $rowRhesus->getName());
                }

                if (!empty($list[0]->getBloodTypeId())) {
                    $rowBlood = $mBlood->find($list[0]->getBloodTypeId());
                    $list = $this->field->setDataSelect($mBlood->table, $list, $mBlood->primaryKey, $rowBlood->getBloodTypeId(), $rowBlood->getName());
                }

                if (!empty($list[0]->getPtkpStatus())) {
                    $rowPTKP = $mRefDetail->where("value", $list[0]->getPtkpStatus())->first();
                    $list = $this->field->setDataSelect($mRefDetail->table, $list, "ptkp_status", $rowPTKP->getValue(), $rowPTKP->getName());
                }

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($list[0]->getFullName());
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setField([$mBranch->primaryKey, $mDiv->primaryKey, 'md_ambassador_id']);
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
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDivision = new M_EmpDivision($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->isAjax()) {
            $post = $this->request->getVar();
            $response = [];

            try {
                $status = [$this->Status_OUTSOURCING, $this->Status_FREELANCE, $this->Status_MAGANG, $this->Status_PENSIUN, $this->Status_RESIGN];

                if (!empty($post["name"]) && $post['name'] === "AccessAll") {
                    $status = array_diff($status, [$this->Status_RESIGN]);
                }

                $userId       = $this->session->get('sys_user_id');
                $employeeId   = $this->session->get('md_employee_id');

                $roleEmp      = $this->access->getUserRoleName($userId, 'W_Emp_All_Data');
                $empDelegation = $this->model->getEmpDelegation($userId);
                $arrAccess    = $mAccess->getAccess($userId);
                $arrEmployee  = $this->model->getChartEmployee($employeeId);

                if (!empty($empDelegation)) {
                    $arrEmployee = array_unique(array_merge($arrEmployee, $empDelegation));
                }

                $builder = $this->model
                    ->whereNotIn('md_status_id', $status)
                    ->where('isactive', 'Y')
                    ->orderBy('value', 'ASC');

                if (!empty($post['search'])) {
                    $builder->like('value', $post['search']);
                }

                if (!empty($post['name'])) {
                    if (($post['name'] === "Access" || $post['name'] === "AccessAll" || $post['name'] === "Junior") && $arrAccess && isset($arrAccess["branch"], $arrAccess["division"])) {
                        $arrEmpBased = $mEmployee->getEmployeeBased($arrAccess["branch"], $arrAccess["division"]);

                        if (!empty($empDelegation)) {
                            $arrEmpBased = array_unique(array_merge($arrEmpBased, $empDelegation));
                        }

                        if ($roleEmp && !empty($employeeId)) {
                            $builder->whereIn('md_employee_id', array_unique(array_merge($arrEmpBased, $arrEmployee)));
                        } else if (!$roleEmp && !empty($employeeId)) {
                            $builder->whereIn('md_employee_id', $arrEmployee);
                        } else if ($roleEmp && empty($employeeId)) {
                            $builder->whereIn('md_employee_id', $arrEmpBased);
                        } else {
                            $builder->whereIn('md_employee_id', [$employeeId]);
                        }
                    } else if (!empty($employeeId) && ($post['name'] === "Access" || $post['name'] === "AccessAll" || $post['name'] === "Junior")) {
                        $builder->whereIn('md_employee_id', $arrEmployee);
                    } else {
                        $builder->where('md_employee_id', $employeeId);
                    }
                } else if (isset($post['ref_id'])) {
                    //TODO : This for getting employee for Delegation in User Menu

                    $arrB = array_column($mEmpBranch->select('md_branch_id')->where('md_employee_id', $post['ref_id'])->findAll(), 'md_branch_id');
                    $arrD = array_column($mEmpDivision->select('md_division_id')->where('md_employee_id', $post['ref_id'])->findAll(), 'md_division_id');

                    $emp = $mEmployee->where('md_employee_id', $post['ref_id'])->first();
                    $arrEmpBased = $mEmployee->getEmployeeBased($arrB, $arrD);

                    $builder->whereIn('md_employee_id', $arrEmpBased)
                        ->where("md_levelling_id >= {$emp->md_levelling_id}");
                } else if (isset($post['md_employee_id'])) {
                    $builder->where('md_employee_id', $post['md_employee_id']);
                }

                if (!empty($post['name']) && $post['name'] === "Junior")
                    $builder->whereNotIn('md_employee_id', [$employeeId]);

                $list = $builder->findAll();

                foreach ($list as $key => $row) {
                    $response[$key]['id'] = $row->getEmployeeId();
                    $response[$key]['text'] = $row->getValue();
                }
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

    public function getBranchDivEmployee()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getVar();
            $mUser = new M_User($this->request);

            try {
                $branchID = explode(",", $post['md_branch_id']);
                $divisionID = explode(",", $post['md_division_id']);
                $arrEmpBased = $this->model->getEmployeeBased($branchID, $divisionID);

                $userList = $mUser->where('isactive', 'Y')->whereIn('md_employee_id', $arrEmpBased)->findAll();
                $empList = array_column($userList, 'md_employee_id');

                if (isset($post['search'])) {
                    $list = $this->model->where('isactive', 'Y')
                        ->whereIn('md_employee_id', $empList)
                        ->like('value', $post['search'])
                        ->orderBy('value', 'ASC')
                        ->findAll();
                } else {
                    $list = $this->model->where('isactive', 'Y')
                        ->whereIn('md_employee_id', $empList)
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
}