<?php
namespace WebUntis\Model;

use Eluceo\iCal\Component\Event;
use Snake\Hydrator\CustomHydrateInterface;
use Snake\Hydrator\HydratorInterface;
use WebUntis\Webuntis;
use WebUntis\Provider\HydratorProvider;

class Timeslot implements CustomHydrateInterface
{
  // Constrants
  const TYPE_GROUP = 1;
  const TYPE_TEACHER = 2;
  const TYPE_SUBJECT = 3;
  const TYPE_ROOM = 4;
  const TYPE_STUDENT = 5;

  // Variables
  public $id;
  public $startTime;
  public $endTime;
  public $classes;
  public $subjects;
  public $rooms;

  // Return a merged timetable from this timetable and another timetable
  public function append(self $other): self
  {
    // Check if the timetables can be merged
    if (!$this->isAppendable($other))
      throw new InvalidArgumentException('The specified timetables cannot be merged');

    // Merge the timetables
    $merged = new self();
    $merged->id = $this->id;
    $merged->startTime = $this->startTime;
    $merged->endTime = $other->endTime;
    $merged->classes = $this->classes;
    $merged->subjects = $this->subjects;
    $merged->rooms = $this->rooms;
    return $merged;
  }

  // Return if this timetable can be appended with another timetable
  public function isAppendable(TimetableModel $other): bool
  {
    return $other->startTime->getTimestamp() - $this->endTime->getTimestamp() <= 900
      && $this->classes == $other->classes
      && $this->subjects == $other->subjects
      && $this->rooms == $other->rooms;
  }

  // Convert an array to an object
  public function hydrate(HydratorInterface $hydrator, array $array, array $context): object
  {
    extract($context);

    // Check if we can access the database
    if (!is_a($webuntis,Webuntis::class))
      throw new InvalidArgumentException("No database is defined");

    // Get the school year for this schedule
    $year = $webuntis['inYear']($array['startTime']);

    // Get the groups
    $groups = array_map(function($classId) use ($webuntis, $year) {
      return $webuntis['groups']($year)[$classId];
    },array_column($array['kl'],'id'));

    // Get the subjects
    $subjects = array_map(function($subjectId) use ($webuntis) {
      return $webuntis['subjects'][$subjectId];
    },array_column($array['su'],'id'));

    // Get the rooms
    $rooms = array_map(function($roomId) use ($webuntis) {
      return $webuntis['rooms'][$roomId];
    },array_column($array['ro'],'id'));

    // Return the timetable
    $this->id = $array['id'];
    $this->startTime = $array['startTime'];
    $this->endTime = $array['endTime'];
    $this->groups = $groups;
    $this->subjects = $subjects;
    $this->rooms = $rooms;
    return $this;
  }

  // Convert to string
  public function __toString()
  {
    return sprintf("%s - %s: %s – %s",$this->startTime->format('Y-m-d H:i'),$this->endTime->format('Y-m-d H:i'),implode(', ',$this->subjects),implode(', ',$this->rooms));
  }

  // Convert to VEVENT
  public function toEvent(WebUntis $webuntis)
  {
    $eventUid = sprintf("%s-%s-%d",$webuntis['server'],$webuntis['school'],$this->id);

    $event = new Event($eventUid);
    $event->setDtStamp($webuntis['lastUpdated']);
    $event->setDtStart($this->startTime);
    $event->setDtEnd($this->endTime);
    $event->setSummary(implode(', ',$this->subjects));
    $event->setDescription(implode(', ',$this->groups));
    $event->setLocation(implode(', ',$this->rooms));

    return $event;
  }
}
