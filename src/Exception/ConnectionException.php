<?php
namespace WebUntis\Exception;

class ConnectionException extends ClientException
{
  // Constructor
  public function __construct(string $url, int $code = 0, \Throwable $previous = null)
  {
    parent::__construct("Unable to establish a connection to {$url}",$code,$previous);
  }
}
