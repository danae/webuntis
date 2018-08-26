<?php
namespace WebUntis\Model;

class Group
{
  // Variables
  public $id;
  public $name;
  public $longName;
  public $active;
  public $department;
  public $year;

  // Convert to string
  public function __toString()
  {
    return $this->longName ?: $this->name;
  }
}
