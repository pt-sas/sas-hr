<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Mail;
use Config\Services;

class Mail extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Mail($this->request);
        $this->entity = new \App\Entities\Mail();
        $this->email = Services::email();
    }

    public function index()
    {
        return $this->template->render('backend/configuration/mail/form_mail');
    }

    public function showAll()
    {
        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->findAll(1);

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'   => $this->field->store($fieldHeader)
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            try {
                $this->entity->fill($post);

                if (!$this->validation->run($post, 'mail')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $response = $this->save();
                    $response[0]['insert_id'] = $this->model->getInsertID();
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function createTestEmail()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            try {
                if (!$this->validation->run($post, 'mail')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $row = $this->model->first();

                    if ($row) {
                        $content = 'Aset EMail Test';

                        if ($row->getIsActive() === $this->access->active()) {
                            $email = $this->sendEmail($row->getRequestEmail(), $content, $content);

                            if ($email) {
                                $response = message('success', true, 'Process completed successfully');
                            } else {
                                $response = message('error', true, $this->email->printDebugger(['header']));
                            }
                        } else {
                            $response = message('error', true, 'Please Active data first');
                        }
                    } else {
                        $response = message('error', true, 'Please Insert data first');
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function sendEmail($to, $subject, $message, $from = null, $yourName = null, $attach = null)
    {
        $row = $this->model->first();

        $email = $this->initializeEmail();

        if (is_null($from))
            $from = $row->getSmtpUser();

        if (is_null($yourName))
            $yourName = $row->getSmtpUser();

        if (!is_null($attach)) {
            $email->clear(true);
            $email->attach($attach);
        } else {
            $email->clear();
        }

        $email->setFrom($from, $yourName);
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($message);

        if ($email->send()) {
            $data = true;
        } else {
            $data = false;
            log_message('error', $email->printDebugger(['headers']));
        }

        return $data;
    }

    private function initializeEmail()
    {
        $row = $this->model->first();

        $config["protocol"] = $row->getProtocol();
        $config["SMTPHost"] = $row->getSmtpHost();
        $config["SMTPUser"] = $row->getSmtpUser();
        $config["SMTPPass"] = $row->getSmtpPassword();
        $config["SMTPPort"] = $row->getSmtpPort();
        $config["SMTPCrypto"] = $row->getSmtpCrypto();

        return $this->email->initialize($config);
    }
}
