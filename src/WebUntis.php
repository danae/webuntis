<?php
namespace WebUntis;

use DI\ContainerBuilder;
use Ds\Map;
use Psr\Container\ContainerInterface;
use Snake\Extractor\ExtractorInterface;
use Snake\Hydrator\HydratorInterface;
use WebUntis\Factory\{ExtractorFactory, HydratorFactory};
use WebUntis\Model\{Department, Group, Holiday, Room, Subject, Timeslot, Year};
use WebUntis\Provider\{ClientProvider, ExtractorProvider, HydratorProvider};
use WebUntis\Timetable\Timetable;

use function DI\{autowire, factory, get, value};

class WebUntis
{
  // Variables
  private $container;

  // Constructor
  public function __construct(array $settings = [])
  {
    // Create a container builder
    $builder = new ContainerBuilder();

    // Add services
    $builder->addDefinitions([
      // Values
      'urlPattern' => "https://%s/WebUntis/jsonrpc.do?school=%s",
      'url' => function(ContainerInterface $c) {
        if ($c->has('server') && $c->has('school') && !empty($c->get('server')) && !empty($c->get('school')))
          return sprintf($c->get('urlPattern'),$c->get('server'),$c->get('school'));
        else
          throw new \InvalidArgumentException("You must define either 'server' and 'school' parameters or an 'url' parameter to initialize the client");
      },

      // Services
      HydratorInterface::class => factory([HydratorFactory::class,'create'])
        ->parameter('context',['webuntis' => $this]),
      ExtractorInterface::class => factory([ExtractorFactory::class,'create'])
        ->parameter('context',['webuntis' => $this]),
      Client::class => autowire()
        ->constructorParameter('url',get('url')),
        //if (end(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))['function'] === '__destruct')

      // Easy access to services
      'hydrator' => get(HydratorInterface::class),
      'extractor' => get(ExtractorInterface::class),
      'client' => get(Client::class),

      // Model services
      'years' => factory([Client::class,'fetch'])
        ->parameter('method','getSchoolyears')
        ->parameter('objectClass',Year::class),
      'holidays' => factory([Client::class,'fetch'])
        ->parameter('method','getHolidays')
        ->parameter('objectClass',Holiday::class),
      'departments' => factory([Client::class,'fetch'])
        ->parameter('method','getDepartments')
        ->parameter('objectClass',Department::class),
      'groups' => function(ContainerInterface $c) {
        $groups = new Map();
        foreach ($c->get('years') as $year)
          $groups = $groups->merge($c->get('client')->fetch(Group::class,'getKlassen',['schoolyearId' => $year->id],Map::class,['year' => $year]));
        return $groups;
      },
      'subjects' => factory([Client::class,'fetch'])
        ->parameter('method','getSubjects')
        ->parameter('objectClass',Subject::class),
      'rooms' => factory([Client::class,'fetch'])
        ->parameter('method','getRooms')
        ->parameter('objectClass',Room::class),

      /*// Timetable service
      'timetable' => function() {

      },*/

      // Helper services
      'lastModified' => factory([Client::class,'getLastUpdated']),
      'currentYear' => function(Client $client, HydratorInterface $hydrator) {
        $result = $client->call('getCurrentSchoolyear');
        return $hydrator->hydrate($result,Year::class);
      },
      'currentYearId' => function(ContainerInterface $c) {
        return $c->get('currentYear')->id;
      },
      'inYear' => value(function(\DateTime $date) {
        foreach (get('years') as $year)
          if ($year->startDate->getTimestamp() <= $date->getTimestamp() && $date->getTimestamp() < $year->endDate->getTimestamp())
            return $year;
        return null;
      })
    ]);

    // Add the settings array
    $builder->addDefinitions($settings);

    // Create the container
    $this->container = $builder->build();

    // Log in if credentials are provided
    if (isset($this->user) && isset($this->password))
      $this->client->login($this->user,$this->password);
  }

  // Destructor
  public function __destruct()
  {
    // Log out if the client is logged in
    //if ($this->client->isLoggedIn())
    //  $this->client->logout();
  }

  // Magic get method: returns the value associated with the specified name from the container
  public function __get(string $name)
  {
    return $this->container->get($name);
  }

  // Magic isset method: returns if the container contains the specified name
  public function __isset(string $name)
  {
    return $this->container->has($name);
  }

  private function stillToImplement()
  {
    $timetable = function() {
      return function(Group $group, \DateTime $startDate, \DateTime $endDate) use ($app) {
        // Create a collection of the results
        return $client->fetch(Timeslot::class,'getTimetable',[
          'startDate' => ExtractorProvider::formatDate($startDate),
          'endDate' => ExtractorProvider::formatDate($endDate),
          'id' => $group->id,
          'type' => $type
        ],Timetable::class);
      };
    };
  }
}
