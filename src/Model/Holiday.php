<?php
namespace WebUntis\Model;

class Holiday
{
  // Variables
  public $id;
  public $name;
  public $longName;
  public $startDate;
  public $endDate;

  // Convert to string
  public function __toString()
  {
    return $this->longName ?: $this->name;
  }
}
