<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use App\Models\M_AppraisalCycle;

class AppraisalCycle extends Entity
{
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'md_appraisal_cycle_id' => 'integer',
        'created_by'            => 'integer',
        'updated_by'            => 'integer',
    ];

    public function getAppraisalCycleId()
    {
        return $this->attributes['md_appraisal_cycle_id'] ?? null;
    }

    public function getTitle()
    {
        return $this->attributes['title'] ?? '';
    }

    public function setTitle($title)
    {
        $this->attributes['title'] = $title;
        return $this;
    }

    public function getStartDate()
    {
        return $this->attributes['start_date'] ?? null;
    }

    public function setStartDate($startDate)
    {
        $this->attributes['start_date'] = $startDate;
        return $this;
    }

    public function getEndDate()
    {
        return $this->attributes['end_date'] ?? null;
    }

    public function setEndDate($endDate)
    {
        $this->attributes['end_date'] = $endDate;
        return $this;
    }

    public function getDeadlineFilling()
    {
        return $this->attributes['deadline_filling'] ?? null;
    }

    public function setDeadlineFilling($date)
    {
        $this->attributes['deadline_filling'] = $date;
        $this->attributes['evaluator_due_date'] = $date; // backwards compatibility mirror
        return $this;
    }

    public function getDeadlineReview()
    {
        return $this->attributes['deadline_review'] ?? null;
    }

    public function setDeadlineReview($date)
    {
        $this->attributes['deadline_review'] = $date;
        $this->attributes['gm_due_date'] = $date; // backwards compatibility mirror
        return $this;
    }

    public function getDeadlineReading()
    {
        return $this->attributes['deadline_reading'] ?? null;
    }

    public function setDeadlineReading($date)
    {
        $this->attributes['deadline_reading'] = $date;
        return $this;
    }

    public function getEvaluatorDueDate()
    {
        return $this->getDeadlineFilling();
    }

    public function setEvaluatorDueDate($date)
    {
        return $this->setDeadlineFilling($date);
    }

    public function getGmDueDate()
    {
        return $this->getDeadlineReview();
    }

    public function setGmDueDate($date)
    {
        return $this->setDeadlineReview($date);
    }

    public function getStatus()
    {
        return $this->attributes['status'] ?? M_AppraisalCycle::STATUS_DRAFT;
    }

    public function setStatus($status)
    {
        $this->attributes['status'] = $status;
        return $this;
    }

    public function isOpen(): bool
    {
        return ($this->attributes['status'] ?? null) === M_AppraisalCycle::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return ($this->attributes['status'] ?? null) === M_AppraisalCycle::STATUS_CLOSED;
    }

    public function isDraft(): bool
    {
        return ($this->attributes['status'] ?? null) === M_AppraisalCycle::STATUS_DRAFT;
    }

    /**
     * Retrieve date limits for the 5 appraisal workflow stages.
     * Excludes state flags and legacy due_date columns.
     *
     * @return array<int, array{name: string, sub: string, key: string, date: ?string, date_fmt: string}>
     */
    public function getStageDeadlines(): array
    {
        $start   = $this->getStartDate();
        $end     = $this->getEndDate();
        
        $fill    = $this->getDeadlineFilling();
        $review  = $this->getDeadlineReview();
        $reading = $this->getDeadlineReading();

        $stages = [
            1 => ['name' => 'Assignment',         'sub' => 'HR memulai',          'key' => 'start_date',       'date' => $start],
            2 => ['name' => 'Form filling',       'sub' => 'Penilai mengisi',     'key' => 'deadline_filling', 'date' => $fill],
            3 => ['name' => 'Review superior',     'sub' => 'Atasan meninjau',     'key' => 'deadline_review',  'date' => $review],
            4 => ['name' => 'Pembacaan karyawan', 'sub' => 'Karyawan menyetujui', 'key' => 'deadline_reading', 'date' => $reading],
            5 => ['name' => 'Finalisasi',         'sub' => 'Finalisasi penilaian', 'key' => 'end_date',         'date' => $end],
        ];

        $result = [];
        foreach ($stages as $step => $st) {
            $rawDate = $st['date'];
            $ts      = !empty($rawDate) ? strtotime($rawDate) : null;

            $result[$step] = [
                'name'     => $st['name'],
                'sub'      => $st['sub'],
                'key'      => $st['key'],
                'date'     => $rawDate,
                'date_fmt' => $ts ? date('d M Y', $ts) : '-',
            ];
        }

        return $result;
    }

    /**
     * Alias for getStageDeadlines()
     */
    public function getStagedDeadline(): array
    {
        return $this->getStageDeadlines();
    }

    /**
     * Alias for getStageDeadlines()
     */
    public function getStagedDeadlines(): array
    {
        return $this->getStageDeadlines();
    }

    /**
     * Resolve the active stage (1 to 5) based on the supplied date (or today).
     *
     * @param string|null $currentDate Format: Y-m-d
     * @return int Stage number (1 to 5)
     */
    public function getCurrentStage(?string $currentDate = null): int
    {
        $todayStr = $currentDate ?: date('Y-m-d');
        $today    = strtotime($todayStr);

        $startStr = $this->getStartDate();
        $fillStr  = $this->getDeadlineFilling();
        $revStr   = $this->getDeadlineReview();
        $readStr  = $this->getDeadlineReading();
        $endStr   = $this->getEndDate();

        $start = ! empty($startStr) ? strtotime($startStr) : null;
        $fill  = ! empty($fillStr) ? strtotime($fillStr) : null;
        $rev   = ! empty($revStr) ? strtotime($revStr) : null;
        $read  = ! empty($readStr) ? strtotime($readStr) : null;
        $end   = ! empty($endStr) ? strtotime($endStr) : null;

        // If before start date
        if ($start && $today < $start) {
            return 1; // Assignment
        }
        // If within or at filling deadline
        if ($fill && $today <= $fill) {
            return 2; // Form filling
        }
        // If within or at review deadline
        if ($rev && $today <= $rev) {
            return 3; // Review superior
        }
        // If within or at reading deadline
        if ($read && $today <= $read) {
            return 4; // Pembacaan karyawan
        }
        // If past reading deadline or at/past end date
        return 5; // Finalisasi
    }

    /**
     * Get a target calendar date representing a specific stage for jumping/debugging.
     *
     * @param int $stage Stage number 1 to 5
     * @return string Format: Y-m-d
     */
    public function getStageTargetDate(int $stage): string
    {
        $startStr = $this->getStartDate() ?: date('Y-m-d');
        $fillStr  = $this->getDeadlineFilling();
        $revStr   = $this->getDeadlineReview();
        $readStr  = $this->getDeadlineReading();
        $endStr   = $this->getEndDate() ?: date('Y-m-d', strtotime('+30 days', strtotime($startStr)));

        switch ($stage) {
            case 1:
                return $startStr;
            case 2:
                if (! empty($fillStr)) {
                    return $fillStr;
                }
                return date('Y-m-d', strtotime('+2 days', strtotime($startStr)));
            case 3:
                if (! empty($revStr)) {
                    return $revStr;
                }
                $base = ! empty($fillStr) ? $fillStr : $startStr;
                return date('Y-m-d', strtotime('+3 days', strtotime($base)));
            case 4:
                if (! empty($readStr)) {
                    return $readStr;
                }
                $base = ! empty($revStr) ? $revStr : (! empty($fillStr) ? $fillStr : $startStr);
                return date('Y-m-d', strtotime('+3 days', strtotime($base)));
            case 5:
            default:
                return $endStr;
        }
    }
}