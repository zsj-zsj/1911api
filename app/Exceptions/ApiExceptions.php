<?php
namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class ApiExceptions extends \Exception
{
    public function __construct($message ,$code)
    {
        parent::__construct($message ,$code);
    }
}
