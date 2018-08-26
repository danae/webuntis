<?php
namespace WebUntis\Model;

class Room
{
  // Variables
  public $id;
  public $name;
  public $longName;
  public $active;
  public $building;
  public $department;
  public $foreColor;
  public $backColor;

  // Convert to string
  public function __toString()
  {
    return $this->name;
  }
}
