<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Attendance;
use App\Models\M_Bundling;
use App\Models\M_Employee;
use Config\Services;

class RealizationBundling extends BaseController
{
    private const PHOTO_MAX = 20;
    private const PHOTO_MAX_SIZE = 10 * 1024 * 1024;  // 10 MB
    private const PHOTO_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const PHOTO_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    private $movedFiles = [];

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Bundling($this->request);
    }

    public function index()
    {
        $data = [
            'toolbarRealization'    => $this->template->toolbarButtonProcess()
        ];

        return $this->template->render('transaction/bundlingrealization/v_bundling_realization', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $today = date('Y-m-d');

            $dateRange = null;
            foreach ($post['form'] as $value) :
                if ($value['name'] === "realization_date") {
                    if (!empty($value['value'])) {
                        $dateRange = explode(" - ",  urldecode($value['value']));
                    }
                }

            endforeach;

            $table = 'v_realization_bundling';
            $select = '*';
            $join = [];
            $order = [
                '',
                '',
                'documentno',
                'employee_name',
                'branch_name',
                'division_name',
                'realization_date'
            ];
            $search = $this->request->getPost('search');
            $sort = ['documentno' => 'ASC'];

            $whereClause = "docstatus = '{$this->DOCSTATUS_Inprogress}'";
            $whereClause .= " AND md_employee_id IN (" . implode(",", $this->access->getEmployeeData(false)) . ")";

            if (!$dateRange) {
                $whereClause .= " AND realization_date <= '{$today}'";
            }

            $where = [$whereClause];

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];

                $ID = $value->trx_bundling_id;

                $number++;

                $row[] = $number;
                $row[] = "";
                $row[] = format_dmy($value->realization_date, '-');
                $row[] = $value->documentno;
                $row[] = $value->employee_name;
                $row[] = $value->branch_name;
                $row[] = $value->division_name;
                $row[] = $this->template->tableButtonProcess($ID);
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

    /**
     * Data pengajuan untuk modal realisasi
     * result_oriented: Y = Hasil, N = Jam | period_type: day / month
     */
    public function getRealizationData()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $trx = $this->model->find($post['id']);

            if (!$trx) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Pengajuan tidak ditemukan']);
            }

            $mEmployee = new M_Employee($this->request);
            $applicant = $mEmployee->find($trx->md_employee_id);

            $result = [
                'documentno'       => $trx->documentno,
                'employee_name'    => $applicant->fullname ?? '',
                'result_oriented'  => $trx->result_oriented,
                'period_type'      => $trx->period_type,
                'startdate'        => $trx->startdate,
                'enddate'          => $trx->enddate,
                'minimum_hour'     => $trx->minimum_hour,
                'maximum_hour'     => $trx->maximum_hour,
                'task_description' => $trx->task_description,
                'employees'        => $this->getParticipants($trx->trx_bundling_id)
            ];

            return $this->response->setJSON($result);
        }
    }

    /**
     * Jam absen karyawan pada satu tanggal
     * TODO: ganti dengan url get data absen yang sebenarnya jika sudah ada
     */
    public function getAttendance()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $row = $this->findAttendance((int) $post['md_employee_id'], $post['date']);

            return $this->response->setJSON($row ? ['clock_in' => $row->clock_in, 'clock_out' => $row->clock_out] : null);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $today = date('Y-m-d');

            try {
                $trx = $this->model->find($post['id']);

                if (!$trx) {
                    $response = message('success', false, 'Pengajuan tidak ditemukan');
                } else {
                    $isResult = $trx->result_oriented === 'task';
                    $rule = $isResult ? 'realisasi_paket_hasil' : 'realisasi_paket_jam';

                    if ($trx->docstatus !== $this->DOCSTATUS_Inprogress) {
                        $response = message('success', false, 'Pengajuan tidak dalam proses realisasi');
                    } else if (!empty($trx->realization_by)) {
                        $response = message('success', false, 'Pengajuan sudah direalisasi');
                    } else if (!$this->validation->run($post, $rule)) {
                        $response = $this->field->errorValidation($this->model->table, $post);
                        // } else if ($today < date('Y-m-d', strtotime($trx->startdate))) {
                        //     $response = message('success', false, 'tanggal realisasi belum terpenuhi');
                    } else {
                        //* Karyawan -> peserta pada trx_bundling_participant
                        $participant = array_column($this->getParticipants($trx->trx_bundling_id), 'participant_id', 'id');
                        $employees = array_values(array_unique(array_map('intval', (array) json_decode($post['employees'], true))));

                        if (!$employees || array_diff($employees, array_keys($participant))) {
                            $response = message('success', false, 'Karyawan tidak sesuai dengan peserta pengajuan');
                        } else {
                            $detail = $isResult
                                ? $this->prepareResult($trx)
                                : $this->prepareHour($trx, $post, $employees, $today);

                            if (isset($detail['error'])) {
                                $response = message('success', false, $detail['error']);
                            } else {
                                $response = $this->saveRealization($trx, $post, $employees, $participant, $detail);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                //! Batalkan transaksi dan hapus foto yang sudah terlanjur dipindahkan
                $this->db()->transRollback();
                foreach ($this->movedFiles as $path) @unlink($path);

                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    /**
     * Validasi realisasi berdasarkan jam (per karyawan per tanggal) -> trx_bundling_event
     */
    private function prepareHour($trx, $post, array $employees, string $today)
    {
        $startdate = date('Y-m-d', strtotime($trx->startdate));
        $enddate = $trx->enddate ? date('Y-m-d', strtotime($trx->enddate)) : $startdate;

        //* Bulanan: tanggal lembur boleh di seluruh bulan pengajuan
        if ($trx->period_type === 'month') {
            $startdate = date('Y-m-01', strtotime($startdate));
            $enddate = date('Y-m-t', strtotime($enddate));
        }

        $min = (float) $trx->minimum_hour * 60;
        $max = (float) $trx->maximum_hour * 60;

        $events = [];
        $exists = [];
        $minutes = [];

        foreach ((array) json_decode($post['lines'], true) as $line) {
            $employeeID = (int) ($line['md_employee_id'] ?? 0);
            $date = (string) ($line['date'] ?? '');
            $start = strtotime($line['starttime'] ?? '');
            $end = strtotime($line['endtime'] ?? '');

            if (!in_array($employeeID, $employees, true))
                return ['error' => 'Data lembur tidak sesuai dengan karyawan yang dipilih'];

            if ($date < $startdate || $date > $enddate || ($trx->period_type === 'day' && $date !== $startdate))
                return ['error' => "Tanggal {$date} di luar periode pengajuan"];

            // if ($date > $today)
            //     return ['error' => "Tanggal {$date} belum dapat direalisasi"];

            if (isset($exists[$employeeID . '|' . $date]))
                return ['error' => "Tanggal {$date} dipilih lebih dari satu kali untuk karyawan yang sama"];

            $exists[$employeeID . '|' . $date] = true;

            if (!$start || !$end || $end <= $start)
                return ['error' => "Jam selesai harus setelah jam mulai ({$date})"];

            //? Jam absen dibaca ulang di server
            $attendance = $this->findAttendance($employeeID, $date);

            if ($attendance && $attendance->clock_out && $end > strtotime($attendance->clock_out))
                return ['error' => "Jam selesai melebihi check out absen ({$date})"];

            $minute = (int) (($end - $start) / 60);

            if ($max && $minute > $max)
                return ['error' => "Durasi tanggal {$date} melebihi maksimal {$trx->maximum_hour} jam"];

            if ($min && $minute < $min)
                return ['error' => "Durasi tanggal {$date} kurang dari minimal {$trx->minimum_hour} jam"];

            $events[] = [
                'md_employee_id' => $employeeID,
                'date'           => $date,
                'starttime'      => date('H:i:s', $start),
                'endtime'        => date('H:i:s', $end),
                'time'           => $minute
            ];

            $minutes[$employeeID] = ($minutes[$employeeID] ?? 0) + $minute;
        }

        //* Harian: tidak perlu event per tanggal, cukup total menit per karyawan
        return $minutes
            ? ['events' => $trx->period_type === 'day' ? [] : $events, 'minutes' => $minutes]
            : ['error' => 'Pilih minimal satu tanggal lembur'];
    }

    /**
     * Validasi foto hasil (photo_0, photo_1, ...) -> disimpan ke trx_bundling.image (dipisah koma)
     */
    private function prepareResult($trx)
    {
        $files = [];

        foreach ($this->request->getFiles() as $key => $file) {
            if (strpos((string) $key, 'photo_') !== 0 || $file->getError() === UPLOAD_ERR_NO_FILE) continue;

            if (!$file->isValid())
                return ['error' => 'Foto "' . $file->getClientName() . '" gagal diunggah: ' . $file->getErrorString()];

            $files[] = $file;
        }

        if (!$files) return ['error' => 'Unggah minimal satu foto hasil'];

        if (count($files) > self::PHOTO_MAX)
            return ['error' => 'Maksimal ' . self::PHOTO_MAX . ' foto'];

        $photos = [];

        foreach ($files as $i => $file) {
            $name = $file->getClientName();
            $ext = $file->guessExtension(); //? Dari isi file, bukan dari nama file

            if (!$file->getSize() || $file->getSize() > self::PHOTO_MAX_SIZE)
                return ['error' => "Foto \"{$name}\" melebihi batas ukuran 10 MB"];

            if (
                !in_array($file->getMimeType(), self::PHOTO_MIME, true) ||
                !in_array($ext, self::PHOTO_EXT, true) ||
                @getimagesize($file->getTempName()) === false
            )
                return ['error' => "Foto \"{$name}\" bukan gambar yang valid (JPG, PNG, WEBP, GIF)"];

            $photos[] = [
                'file_name' => $trx->trx_bundling_id . '_' . ($i + 1) . '_' . bin2hex(random_bytes(3)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext),
                'file'      => $file
            ];
        }

        return ['photos' => $photos];
    }

    /**
     * Simpan realisasi dalam satu transaksi:
     * - Jam   : trx_bundling_event (per tanggal), trx_bundling_participant.total_time (menit)
     * - Harian: tanpa event, total_amount langsung sesuai nominal (project_value)
     * - Hasil : foto ke trx_bundling_image (foto pertama juga di trx_bundling.image), description
     * Peserta yang ikut ditandai trx_bundling_participant.isjoined = Y
     */
    private function saveRealization($trx, $post, array $employees, array $participant, array $detail)
    {
        $db = $this->db();
        $user = $this->access->getSessionUser();
        $now = date('Y-m-d H:i:s');
        $isResult = $trx->result_oriented === 'task';

        $moved = &$this->movedFiles;

        //* Pindahkan foto lebih dulu; dihapus lagi jika transaksi gagal
        foreach ($detail['photos'] ?? [] as $row) {
            $row['file']->move(FCPATH . 'uploads/realisasi', $row['file_name']);
            $moved[] = FCPATH . 'uploads/realisasi/' . $row['file_name'];
        }

        $minutes = $detail['minutes'] ?? [];
        $isDay = $trx->period_type === 'day';

        $db->transStart();

        //* Bulanan: simpan per tanggal ke trx_bundling_event (harian tidak perlu event)
        foreach ($detail['events'] ?? [] as $event) {
            $db->table('trx_bundling_event')->insert([
                'trx_bundling_participant_id' => $participant[$event['md_employee_id']],
                'date'                        => $event['date'] . ' 00:00:00',
                'starttime'                   => $event['starttime'],
                'endtime'                     => $event['endtime'],
                'time'                        => $event['time'], // TODO: satuan menit, sesuaikan jika kolom time berisi jam
                'created_by'                  => $user,
                'updated_by'                  => $user
            ]);
        }

        foreach ($participant as $employeeID => $participantID) {
            $joined = in_array($employeeID, $employees, true);

            $db->table('trx_bundling_participant')
                ->where('trx_bundling_participant_id', $participantID)
                ->update([
                    'isjoined'     => $joined ? 'Y' : 'N',
                    //* Peserta yang tidak ikut: tidak ada jam dan tidak mendapat nominal
                    'total_time'   => $joined ? ($minutes[$employeeID] ?? null) : 0,
                    //* Harian: peserta yang ikut langsung sesuai nominal pengajuan
                    'total_amount' => $joined ? ($isDay ? $trx->project_value : null) : 0,
                    'updated_by'   => $user,
                    'updated_at'   => $now
                ]);
        }

        //* Foto hasil: satu baris per foto
        foreach ($detail['photos'] ?? [] as $row) {
            $db->table('trx_bundling_image')->insert([
                'trx_bundling_id' => $trx->trx_bundling_id,
                'file_name'       => $row['file_name'],
                'created_by'      => $user,
                'updated_by'      => $user
            ]);
        }

        //* Pengajuan langsung di-complete (TODO: total_amount bulanan belum dihitung)
        $header = [
            'docstatus'        => $this->DOCSTATUS_Completed ?? 'CO', // TODO: sesuaikan nama konstanta/kode docstatus
            'realization_date' => $now,
            'realization_by'   => $user,
            'updated_by'       => $user,
            'updated_at'       => $now
        ];

        if ($isResult) {
            $header['image'] = $detail['photos'][0]['file_name']; // foto pertama; semua foto ada di trx_bundling_image
            $header['description'] = trim($post['description']);
        }

        $db->table('trx_bundling')->where('trx_bundling_id', $trx->trx_bundling_id)->update($header);

        $db->transComplete();

        if (!$db->transStatus()) {
            foreach ($moved as $path) @unlink($path);

            return message('success', false, 'Gagal menyimpan realisasi');
        }

        return message('success', true, 'Realisasi berhasil disimpan, pengajuan selesai');
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    /**
     * Peserta pengajuan dari trx_bundling_participant
     */
    private function getParticipants($bundlingID)
    {
        return $this->db()->table('trx_bundling_participant p')
            ->select('p.trx_bundling_participant_id AS participant_id, e.md_employee_id AS id, e.fullname AS name')
            ->join('md_employee e', 'e.md_employee_id = p.md_employee_id')
            ->where('p.trx_bundling_id', $bundlingID)
            ->orderBy('e.fullname')
            ->get()->getResultArray();
    }

    /**
     * Data absen karyawan pada satu tanggal
     * TODO: sesuaikan nama kolom tanggal dan jam pada tabel absen
     */
    private function findAttendance(int $employeeID, string $date)
    {
        $mAttendance = new M_Attendance($this->request);

        $whereClause = "v_attendance.md_employee_id = {$employeeID} AND v_attendance.date = '{$date}'";

        return $mAttendance->getAttendance($whereClause)->getRow();
    }
}
