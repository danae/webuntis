<?php
namespace WebUntis\Model;

class Department
{
  // Variables
  public $id;
  public $name;
  public $longName;

  // Convert to string
  public function __toString()
  {
    return $this->longName ?: $this->name;
  }
}
