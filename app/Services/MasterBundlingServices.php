<?php

namespace App\Services;

use App\Entities\BundlingDivisionRule;
use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\M_BundlingDivisionRule;
use App\Models\M_DocumentType;
use App\Models\M_MasterBundling;
use App\Services\BaseServices;

class MasterBundlingServices extends BaseServices
{
    protected $baseSubType;

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_MasterBundling($this->request);
        $this->entity = new \App\Entities\MasterBundling();

        $this->baseSubType = $this->model->Pengajuan_Master_Paket;
    }

    public function create(array $data)
    {
        $this->model->db->transBegin();

        try {
            $_ID       = !empty($data[$this->model->primaryKey]) ? (int) $data[$this->model->primaryKey] : null;
            $doProcess = $data['processNow'] ?? "N";

            // submissiontype tetap diisi sistem (acuan workflow), bukan input form
            $data['submissiontype'] = $this->baseSubType;
            $data['divisionrule']   = ($data['divisionrule'] ?? 'N') === 'Y' ? 'Y' : 'N';
            $data['isprorate']      = ($data['isprorate'] ?? 'N') === 'Y' ? 'Y' : 'N';
            $data['project_value']  = (float) ($data['project_value'] ?? 0);

            if ($data['project_value'] <= 0)
                throw new ValidationException("Nominal harus lebih dari 0");

            $this->assertNameUnique($data['name'], $_ID);

            //* Aturan per divisi: dikosongkan bila divisionrule = N
            $rules = $this->parseRules($data);

            if ($_ID) {
                $current = $this->model->where([$this->model->primaryKey => $_ID, 'submissiontype' => $this->baseSubType])->first();

                if (empty($current))
                    throw new NotFoundException("Paket tidak ditemukan");

                // if ($current->getDocStatus() !== $this->DOCSTATUS_Drafted)
                //     throw new ValidationException("Hanya paket berstatus Draft yang bisa diubah");

                unset($data['value']); // kode tidak berubah
            } else {
                $data['value'] = $this->nextValue();
            }

            $this->entity->fill($data);

            if (!$_ID) {
                // $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
            } else {
                $this->entity->setMasterBundlingId($_ID);
            }

            $result = $this->save(false);

            if (!$_ID) $_ID = $this->insertID;

            $this->syncRules($rules, $_ID);

            if ($doProcess === "Y") {
                // $this->proccessTransaction($_ID, $this->DOCSTATUS_Completed);
            }

            $this->model->db->transCommit();

            return $result;
        } catch (\Throwable $e) {
            $this->model->db->transRollback();
            throw $e;
        }
    }

    public function show(int $id)
    {
        $mRule = new M_BundlingDivisionRule($this->request);

        $list = $this->model->where([$this->model->primaryKey => $id, 'submissiontype' => $this->baseSubType])
            ->findAll();

        if (empty($list))
            throw new NotFoundException("Paket tidak ditemukan");

        $rules = array_map(static fn($r) => [
            'id'                 => (int) $r->{$mRule->primaryKey},
            'md_division_id'     => (int) $r->md_division_id,
            'maximum_submission' => (int) $r->maximum_submission,
        ], $mRule->where($this->model->primaryKey, $id)->orderBy($mRule->primaryKey, 'ASC')->findAll());

        return [
            'header' => $list,
            'rules'  => $rules,
        ];
    }

    public function destroy(int $id)
    {
        $row = $this->model->where([$this->model->primaryKey => $id, 'submissiontype' => $this->baseSubType])->first();

        if (empty($row))
            throw new NotFoundException("Paket tidak ditemukan");

        if ($row->getDocStatus() !== $this->DOCSTATUS_Drafted)
            throw new ValidationException("Hanya paket berstatus Draft yang bisa dihapus");

        $mRule = new M_BundlingDivisionRule($this->request);

        $this->model->db->transBegin();

        try {
            $mRule->where($this->model->primaryKey, $id)->delete();
            $this->model->delete($id);

            $this->model->db->transCommit();
        } catch (\Throwable $e) {
            $this->model->db->transRollback();
            throw $e;
        }

        return 'Paket berhasil dihapus';
    }

    public function proccessTransaction(int $id, String $docaction, int $subTypeTarget = null)
    {
        //* Call Services
        $WScenarioServices = new WScenarioServices($this->userID, $this->employeeID);

        //* Call Models
        $mDocType = new M_DocumentType($this->request);
        $mRule = new M_BundlingDivisionRule($this->request);

        //* Get Data Transaction
        $row = $this->model->where([$this->model->primaryKey => $id, 'submissiontype' => $this->baseSubType])->first();

        if (empty($row))
            throw new NotFoundException("Paket tidak ditemukan");

        if ($docaction === $row->getDocStatus())
            throw new ValidationException("Silahkan refresh terlebih dahulu");

        //* Get menu URL
        $docType = $mDocType->getDocTypeMenu($row->submissiontype);

        if (empty($docType->sys_submenu_id))
            throw new NotFoundException("Tipe Pengajuan {$docType->name} belum diset acuan menu-nya");

        //* Checking docaction condition
        if ($docaction === $this->DOCSTATUS_Completed) {
            if ($row->divisionrule === 'Y' && $mRule->where($this->model->primaryKey, $id)->countAllResults() === 0)
                throw new ValidationException("Paket belum memiliki aturan divisi");

            $WScenarioServices->setScenario($this->entity, $this->model, $this->modelDetail, $id, $docaction, $docType->url);

            return 'Paket berhasil Diproses';
        } else if ($docaction === $this->DOCSTATUS_Voided) {
            $this->entity->setDocStatus($this->DOCSTATUS_Voided);
            $this->entity->setMasterBundlingId($id);
            $this->save();
            return 'Paket berhasil Divoid';
        } else {
            throw new BusinessException("Dokumen aksi ini tidak tersedia pada tipe pengajuan ini");
        }
    }

    /** Kode paket berikutnya, contoh PKT-0007 (kolom value maksimal 10 karakter) */
    public function nextValue(): string
    {
        $row = $this->model->db->table($this->model->table)
            ->selectMax($this->model->primaryKey, 'max_id')->get()->getRow();

        return 'PK' . str_pad((string) ((int) ($row->max_id ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function assertNameUnique(string $name, ?int $id): void
    {
        $query = $this->model->where('name', trim($name));

        if ($id) $query->where($this->model->primaryKey . ' !=', $id);

        if ($query->countAllResults() > 0)
            throw new ValidationException("Nama paket sudah dipakai");
    }

    /**
     * Membaca baris aturan divisi dari field "table" (JSON dari process.js).
     * @return array<int,int> [md_division_id => maximum_submission]
     */
    private function parseRules(array $data): array
    {
        if ($data['divisionrule'] !== 'Y') return [];

        $rules = [];

        foreach ((json_decode($data['table'] ?? '[]', true) ?: []) as $line) {
            if (!is_array($line)) continue;

            $divisionId = (int) ($line['md_division_id'] ?? 0);
            $max        = (int) ($line['maximum_submission'] ?? 0);

            if ($divisionId < 1 || $max < 1)
                throw new ValidationException("Setiap baris wajib memilih divisi dan batas minimal 1 pengajuan");

            if (isset($rules[$divisionId]))
                throw new ValidationException("Satu divisi tidak boleh dipilih dua kali");

            $rules[$divisionId] = $max;
        }

        if (empty($rules))
            throw new ValidationException("Tambahkan minimal satu divisi pada batas pengajuan");

        return $rules;
    }

    /** Samakan tabel trx_bundling_division_rule dengan daftar aturan terbaru */
    private function syncRules(array $rules, int $id): void
    {
        $mRule = new M_BundlingDivisionRule($this->request);

        $existing = [];

        foreach ($mRule->where($this->model->primaryKey, $id)->findAll() as $row) {
            if (!isset($rules[$row->md_division_id])) {
                $mRule->delete($row->{$mRule->primaryKey});
            } else {
                $existing[$row->md_division_id] = $row;
            }
        }

        foreach ($rules as $divisionId => $max) {
            $entity = new BundlingDivisionRule();

            if (isset($existing[$divisionId])) {
                if ((int) $existing[$divisionId]->maximum_submission === $max) continue;

                $entity->{$mRule->primaryKey} = $existing[$divisionId]->{$mRule->primaryKey};
            } else {
                $entity->{$this->model->primaryKey} = $id;
                $entity->md_division_id = $divisionId;
                $entity->created_by = $this->userID;
            }

            $entity->maximum_submission = $max;
            $entity->updated_by = $this->userID;

            $mRule->save($entity);
        }
    }
}
