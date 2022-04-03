<?php

namespace App\ApiResult;

class ApiResult
{
    protected $isSuccess;
    protected $data;
    protected $error;
    protected $system_error;
    protected static $_instance;
    // Construct
    function __construct()
    {
        $this->isSuccess = false;
        $this->data = null;
        $this->error = 'Some thing wrong';
        $this->system_error = 'Class not use';
    }
    // Set Data
    public function setData($data)
    {
        $this->isSuccess = true;
        $this->data = $data;
        $this->error = null;
        $this->system_error = null;
    }
    // Set Error
    public function setError($error, $system_error = null)
    {
        $this->isSuccess = false;
        $this->data = NULL;
        $this->error = $error;
        $this->system_error = $system_error;
    }
    // To response
    public function toResponse()
    {
        $response = [
            'isSuccess' => $this->isSuccess,
            'data' => $this->data,
            'error' => $this->error,
            'system_error' => $this->system_error
        ];
        return $response;
    }

    // Get Instance
    public static function getInstance()
    {
        if (self::$_instance !== null) {
            return self::$_instance;
        }
        self::$_instance = new self();
        return self::$_instance;
    }
}
