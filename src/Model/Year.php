<?php
namespace WebUntis\Model;

class Year
{
  // Variables
  public $id;
  public $name;
  public $startDate;
  public $endDate;

  // Convert to string
  public function __toString()
  {
    return $this->name;
  }
}
