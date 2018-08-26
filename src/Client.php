<?php
namespace WebUntis;

use Ds\Map;
use Snake\Hydrator\HydratorInterface;
use PhpJsonRpc\Client as JsonRpcClient;
use PhpJsonRpc\Client\Transport\TransportContainer;
use PhpJsonRpc\Common\Interceptor\Interceptor;
use PhpJsonRpc\Error\{ConnectionFailureException, ServerErrorException};
use WebUntis\Exception\{AuthorizationException, ConnectionException};

class Client
{
  // Variables
  private $url;
  private $client;
  private $hydrator;
  private $sessionId;

  // Constructor
  public function __construct(HydratorInterface $hydrator, string $url)
  {
    $this->url = $url;
    $this->client = new JsonRpcClient($this->url,JsonRpcClient::ERRMODE_EXCEPTION);
    $this->client->getTransport()->onPreRequest()->add(Interceptor::createWith(function(TransportContainer $container) {
      if ($this->isLoggedIn())
        $container->getTransport()->addHeaders(['Cookie: JSESSIONID=' . $this->sessionId]);
      return $container;
    }));
    $this->hydrator = $hydrator;
    $this->sessionId = null;
  }

  // Log in to the client, storing the session ID
  public function login(string $user, string $password): void
  {
    $this->sessionId = $this->call('authenticate',[$user,$password])['sessionId'];
  }

  // Log out and unset the session ID
  public function logout(): void
  {
    $this->call('logout');
    $this->sessionId = null;
  }

  // Return if the client is logged in
  public function isLoggedIn(): bool
  {
    return $this->sessionId !== null;
  }

  // Call a function from the client and return the result
  public function call(string $method, array $params = [])
  {
    try
    {
      return $this->client->call($method,$params);
    }
    catch (ConnectionFailureException $ex)
    {
      throw new ConnectionException($this->url,$ex->getCode(),$ex);
    }
    catch (ServerErrorException $ex)
    {
      if ($ex->getMessage() === 'no username specified')
        throw new AuthorizationException('no user specified',$ex->getCode(),$ex);
      elseif ($ex->getMessage() === 'bad credentials')
        throw new AuthorizationException('bad credentials',$ex->getCode(),$ex);
      else
        throw $ex;
    }
  }

  // Fetch data from the client
  public function fetch(string $objectClass, string $method, array $params = [], string $mapClass = Map::class, array $extraAttributes = [])
  {
    // Fetch the objects from the client
    $objects = $this->call($method,$params);

    // Deserialize the object one by one and add the to the map
    $map = new $mapClass();
    foreach ($objects as $object)
    {
      foreach ($extraAttributes as $key => $value)
        $object[$key] = $value;

      $object = $this->hydrator->hydrate($object,$objectClass);
      $map->put($object->id,$object);
    }
    return $map;
  }

  // Return when the data was last updated
  public function getLastUpdated(): \DateTime
  {
    $result = $this->call('getLatestImportTime');

    $dateTime = new \DateTime();
    $dateTime->setTimestamp(intval($result / 1000));
    return $dateTime;
  }
}
