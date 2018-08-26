<?php
namespace WebUntis\Exception;

class ClientException extends \Exception
{
  // Constructor
  public function __construct(string $message, int $code = 0, \Throwable $previous = null)
  {
    parent::__construct($message,$code,$previous);
  }
}
