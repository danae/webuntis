<?php
namespace WebUntis\Factory;

use Snake\Extractor\{ChainExtractor, CustomExtractor, ExtractorInterface, ObjectExtractor};

class ExtractorFactory
{
  // Format a date
  public static function formatDate(\DateTime $dateTime): int
  {
    return intval($dateTime->format('Ymd'));
  }

  // Format a time
  public static function formatTime(\DateTime $dateTime): int
  {
    return intval($dateTime->format('Hi'));
  }

  // Format a time period
  public static function formatTimePeriod(array $array): array
  {
    if (!(isset($array['startTime']) && isset($array['endTime'])))
      return $array;

    $array['date'] = self::formatDate($array['startTime']);
    $array['startTime'] = self::formatTime($array['startTime']);
    $array['endTime'] = self::formatTime($array['endTime']);

    return $array;
  }

  // Create the instance
  public static function create(array $context = [])
  {
    $customExtractor = new CustomExtractor();

    $objectExtractor = (new ObjectExtractor())
      ->setNameCallbacks([
        'startDate' => [self::class,'formatDate'],
        'endDate' => [self::class,'formatDate']
      ]);

    return (new ChainExtractor([$customExtractor,$objectExtractor]))
      ->setContext($context)
      ->setBefore([
        [self::class,'formatTimePeriod']
      ]);
  }
}
