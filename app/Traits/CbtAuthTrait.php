<?php

namespace App\Traits;

/**
 * CbtAuthTrait - Common authentication methods for CBT controllers
 * 
 * Reduces code duplication across CBT-related controllers
 */
trait CbtAuthTrait
{
    protected $currentUser;
    protected $currentSiswa;
    protected $currentTp;
    protected $currentSmt;

    /**
     * Check if user is logged in as siswa
     * Returns error response if not authenticated
     */
    protected function requireSiswaAuth(): ?object
    {
        if (!$this->isLoggedIn() || !$this->inGroup('siswa')) {
            return null;
        }

        $this->loadCurrentContext();
        return $this->currentSiswa;
    }

    /**
     * Load current user context (user, siswa, tp, smt)
     * Caches result for the request lifetime
     */
    protected function loadCurrentContext(): void
    {
        if ($this->currentUser !== null) {
            return; // Already loaded
        }

        $this->currentUser = $this->authModel->user($this->getUserId());

        if (!$this->currentUser) {
            return;
        }

        $this->currentTp = $this->dashboardModel->getTahunActive();
        $this->currentSmt = $this->dashboardModel->getSemesterActive();

        $this->currentSiswa = $this->dashboardModel->getDataSiswa(
            $this->currentUser->username,
            $this->currentTp->id_tp,
            $this->currentSmt->id_smt
        );
    }

    /**
     * Return JSON error response
     */
    protected function jsonError(string $message, int $code = 400): \CodeIgniter\HTTP\Response
    {
        return $this->response->setJSON([
            'status' => false,
            'success' => false,
            'message' => $message
        ])->setStatusCode($code);
    }

    /**
     * Return JSON success response
     */
    protected function jsonSuccess($data = null, string $message = 'Success'): \CodeIgniter\HTTP\Response
    {
        $response = [
            'status' => true,
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->response->setJSON($response);
    }

    /**
     * Validate AJAX request
     */
    protected function requireAjax(): bool
    {
        return $this->request->isAJAX();
    }

    /**
     * Get POST parameter with default
     */
    protected function postParam(string $key, $default = null)
    {
        return $this->request->getPost($key) ?? $default;
    }

    /**
     * Get GET parameter with default  
     */
    protected function getParam(string $key, $default = null)
    {
        return $this->request->getGet($key) ?? $default;
    }

    /**
     * Validate required POST parameters
     * Returns array of missing params, or empty if all present
     */
    protected function validateRequired(array $params): array
    {
        $missing = [];
        foreach ($params as $param) {
            $value = $this->request->getPost($param);
            if ($value === null || $value === '') {
                $missing[] = $param;
            }
        }
        return $missing;
    }
}
