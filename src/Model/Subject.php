<?php
namespace WebUntis\Model;

class Subject
{
  // Variables
  public $id;
  public $name;
  public $longName;
  public $alternateName;
  public $active;
  public $foreColor;
  public $backColor;

  // Convert to string
  public function __toString()
  {
    return $this->longName ?: $this->name;
  }
}
