<?php
namespace WebUntis\Exception;

class AuthorizationException extends ClientException
{
  // Constructor
  public function __construct(string $message, int $code = 0, \Throwable $previous = null)
  {
    parent::__construct("Unable to authorize: " . $message,$code,$previous);
  }
}
