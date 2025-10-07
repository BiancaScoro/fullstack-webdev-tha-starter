<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Mimics CI4's respond() method
     * $data: array to return as JSON
     * $status: HTTP status code (default 200)
     */
    protected function respond($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
