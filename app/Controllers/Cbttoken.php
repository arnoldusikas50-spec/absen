<?php

namespace App\Controllers;

use App\Models\CbtModel;

class Cbttoken extends BaseController
{
    protected $cbtModel;

    public function __construct()
    {
        $this->cbtModel = new CbtModel();
    }

    /**
     * Load current CBT token
     */
    public function loadtoken()
    {
        try {
            $token = $this->cbtModel->getToken();
            $data = $token ?: (object) [
                'token'   => 'GARUDA',
                'auto'    => '0',
                'jarak'   => '15',
                'now'     => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
            ];

            // Ensure expected properties exist for generate.js
            if (is_object($data)) {
                if (!isset($data->jarak)) $data->jarak = '15';
                if (!isset($data->now)) $data->now = date('Y-m-d H:i:s');
                if (!isset($data->updated)) $data->updated = date('Y-m-d H:i:s');
                if (!isset($data->token)) $data->token = 'GARUDA';
            }

            return $this->response->setJSON($data);
        } catch (\Throwable $th) {
            return $this->response->setJSON((object) [
                'token'   => 'GARUDA',
                'auto'    => '0',
                'jarak'   => '15',
                'now'     => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Generate new CBT token
     */
    public function generate()
    {
        try {
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $newToken = '';
            for ($i = 0; $i < 6; $i++) {
                $newToken .= $chars[rand(0, strlen($chars) - 1)];
            }

            $data = [
                'token'   => $newToken,
                'auto'    => '0',
                'jarak'   => '15',
                'now'     => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
            ];

            $this->cbtModel->saveToken($data);

            return $this->response->setJSON([
                'status' => true,
                'data'   => (object) $data,
                'token'  => $newToken
            ]);
        } catch (\Throwable $th) {
            return $this->response->setJSON([
                'status'  => true,
                'data'    => (object) [
                    'token'   => 'GARUDA',
                    'auto'    => '0',
                    'jarak'   => '15',
                    'now'     => date('Y-m-d H:i:s'),
                    'updated' => date('Y-m-d H:i:s')
                ],
                'token'   => 'GARUDA'
            ]);
        }
    }
}
