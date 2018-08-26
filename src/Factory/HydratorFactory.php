<?php
namespace WebUntis\Factory;

use Snake\Hydrator\{ChainHydrator, CustomHydrator, HydratorInterface, ObjectHydrator};
use WebUntis\WebUntis;
use WebUntis\Model\Department;

class HydratorFactory
{
  // Parse a date
  public static function parseDate(int $date): \DateTime
  {
    $dateTime = \DateTime::createFromFormat('Ymd',(string)$date);
    $dateTime->setTime(0,0);
    return $dateTime;
  }

  // Parse a time
  public static function parseTime(\DateTime $date, int $time): \DateTime
  {
    $hours = $time / 100;
    $minutes = $time % 100;

    $dateTime = clone $date;
    $dateTime->setTime($hours,$minutes);
    return $dateTime;
  }

  // Parse a time period
  public static function parseTimePeriod(array $array): array
  {
    if (!(isset($array['date']) && isset($array['startTime']) && isset($array['endTime'])))
      return $array;

    $date = self::parseDate($array['date']);
    $array['startTime'] = self::parseTime($date,$array['startTime']);
    $array['endTime'] = self::parseTime($date,$array['endTime']);
    unset($array['date']);

    return $array;
  }

  // Parse a color
  public static function parseColor(string $color)
  {
    return "#{$color}";
  }

  // Parse a department ID
  public static function parseDepartmentId($departmentId, array $context): ?Department
  {
    extract($context);

    // Check if we can access the database
    if (!is_a($webuntis,WebUntis::class))
      throw new \InvalidArgumentException("No database is defined");

    // Get the department if there is one
    if ($departmentId !== null)
      return $webuntis->departments[$departmentId] ?? null;
    else
      return null;
  }

  // Create the instance
  public static function create(array $context = []): HydratorInterface
  {
    $customHydrator = new CustomHydrator();

    $objectHydrator = (new ObjectHydrator())
      ->setErrorOnNotWritable(false)
      ->setNameCallbacks([
        'startDate' => [self::class,'parseDate'],
        'endDate' => [self::class,'parseDate'],
        'foreColor' => [self::class,'parseColor'],
        'backColor' => [self::class,'parseColor'],
        'did' => [self::class,'parseDepartmentId']
      ])
      ->setNameConvertors([
        'did' => 'department'
      ]);

    return (new ChainHydrator([$customHydrator,$objectHydrator]))
      ->setContext($context)
      ->setBefore([
        [self::class,'parseTimePeriod']
      ]);
  }
}
