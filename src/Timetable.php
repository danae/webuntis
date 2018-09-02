<?php
namespace WebUntis;

use Ds\Map;
use Eluceo\iCal\Component\Calendar;
use Snake\Extractor\CustomExtractInterface;
use Snake\Extractor\ExtractorInterface;
use WebUntis\Model\Timeslot;

class Timetable
{
  // Constrants
  const TYPE_GROUP = 1;
  const TYPE_TEACHER = 2;
  const TYPE_SUBJECT = 3;
  const TYPE_ROOM = 4;
  const TYPE_STUDENT = 5;

  // Variables
  private $map;

  // Constructor
  public function __construct()
  {
    $this->map = new Map;

    // Sort the timetables
    $this->map->sort(function($a, $b) {
      return Timeslot::compare($a,$b);
    });

    // Search for appendable timeslots and merge them
    foreach ($this->map as $id => $timeslot)
    {
      // Check if the timeslot equals the last (except time)
      if ($lastTimeslot !== null && $lastTimeslot->isAppendable($timeslot))
      {
        // Merge the two timeslots
        $newTimeslot = $lastTimeslot->append($timeslot);
        $this->map->put($newTimeslot->id,$newTimeslot);

        // Remove the current timeslot
        $this->map->remove($id);

        // Set the last timeslot to the merged timeslot
        $lastTimeslot = $newTimeslot;
      }
      else
      {
        // Set the last timetable
        $lastTimeslot = $timeslot;
      }
    }
  }

  // Pass unknown methods to the underlying map
  public function __call(string $method, array $args)
  {
    return call_user_func_array([$this->map,$method],$args);
  }

  // Convert to VCalendar
  public function toCalendar(WebUntis $webuntis)
  {
    $calendar = new Calendar("-//dengsn//webuntis-api");

    foreach ($this->map->values() as $timeslot)
      $calendar->addComponent($timeslot->toEvent($webuntis));

    return $calendar;
  }
}
