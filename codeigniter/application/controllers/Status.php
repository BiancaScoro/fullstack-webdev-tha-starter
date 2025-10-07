<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Status extends MY_Controller
{
    public function index()
    {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'online']);
    }
}
