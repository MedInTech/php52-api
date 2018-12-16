<?php

class MedInTech_Api_Router implements MedInTech_Api_IRouter
{
  private $routes = array();
  const ROUTE_METHODS    = 'methods';
  const ROUTE_PATTERN    = 'pattern';
  const ROUTE_CONTROLLER = 'controller';
  const ROUTE_ACTION     = 'action';
  const ROUTE_PREFIX     = 'prefix';

  const HANDLER_BEFORE_ACTION = 'beforeAct';
  const HANDLER_AFTER_ACTION  = 'afterAct';
  const HANDLER_ON_EXCEPTION  = 'onExcept';
  const ROUTE_OVERRIDES       = 'overrides';

  /** @var MedInTech_IoC_Interface */
  private $container;
  /** @var MedInTech_Api_IRequest */
  private $request;
  /** @var MedInTech_Event_IEmitter */
  private $ee;

  public function __construct(MedInTech_IoC_Interface $container)
  {
    $this->container = $container;
    $this->ee = new MedInTech_Event_Emitter(); // @dependency
  }

  public function ee() { return $this->ee; }

  public function loadJson($filename, $aux = array())
  {
    $data = json_decode(file_get_contents($filename), true);
    $this->load($data, $aux);
  }

  public function load($data, $aux = array())
  {
    foreach ($data as $route) {
      $this->loadRoute($route, $aux);
    }
  }
  public function loadRoute($route, $aux = array())
  {
    $type = isset($route['type']) ? $route['type'] : 'raw';

    unset($route['type']);
    switch ($type) {
      case 'json_file':
        $file = $route['file'];
        unset($route['file']);
        $this->loadJson($file, $aux + $route);
        break;
      case 'list':
        $list = $route['list'];
        unset($route['list']);
        $this->load($list, $aux + $route);
        break;
      case 'raw':
      default:
        $this->routes[] = $aux + $route;
    }
    $this->ee->emit('loadRoute', array(
      'route' => $route,
      'aux'   => $aux,
    ));
  }

  public function resolve(MedInTech_Api_IRequest $request)
  {
    $this->request = $request;
    $verb = $request->getMethod();
    $path = $request->getPathInfo();

    $this->ee->emit('resolve', array(
      'verb' => $verb,
      'path' => $path,
    ));

    foreach ($this->routes as $route) {
      if ($this->resolveRoute($request, $route)) {
        if (!empty($route[self::ROUTE_OVERRIDES])) {
          foreach ($route[self::ROUTE_OVERRIDES] as $k => $v) {
            $this->container->set($k, $v);
          }
        }
        $controller = $this->container->create($route[self::ROUTE_CONTROLLER]);

        $this->call($controller, self::HANDLER_BEFORE_ACTION);

        $actionMethod = $route[self::ROUTE_ACTION] . 'Action';
        try {

          if (!is_callable(array($controller, $actionMethod))) {
            throw new MedInTech_Api_RouterException("Method $actionMethod does not exists");
          }
          $result = $this->call($controller, $actionMethod, $request->getArguments()->all() + $request->getParameters()->all());

          $afterRes = $this->call($controller, self::HANDLER_AFTER_ACTION, array(
            'result' => $result,
          ));
          if (!is_null($afterRes)) $result = $afterRes;
          $this->ee->emit('result', array('value' => $result));

        } catch (Exception $ex) {
          $this->ee->emit('exception', array('value' => $ex));
          $result = $this->call($controller, self::HANDLER_ON_EXCEPTION, array(
            'exception' => $ex,
          ));
          if (is_null($result)) throw $ex;
        }

        return $result;
      }
    }

    return '404';
  }

  private function call($ctrl, $method, $auxResolves = array())
  {
    if (!is_callable(array($ctrl, $method))) return null;

    return $this->container->call($ctrl, $method, $auxResolves + array(
        'MedInTech_Api_IRequest' => $this->request,
      ));
  }

  private function resolveRoute(MedInTech_Api_IRequest $request, $route)
  {
    $verb = $request->getMethod();
    $path = $request->getPathInfo();

    if (isset($route[self::ROUTE_METHODS]) && $route[self::ROUTE_METHODS] !== 'ALL') {
      $verbs = is_array($route[self::ROUTE_METHODS]) ? $route[self::ROUTE_METHODS] : explode('|', $route[self::ROUTE_METHODS]);
      if (isset($route[self::ROUTE_METHODS]) && !in_array($verb, $verbs)) {
        return false;
      }
    }

    if (isset($route[self::ROUTE_PREFIX])) {
      $prefix = $route[self::ROUTE_PREFIX];
      if (strpos($path, $prefix) !== 0) {
        return false;
      }
      $path = substr($path, strlen($prefix));
    }

    $patterns = is_array($route[self::ROUTE_PATTERN]) ? $route[self::ROUTE_PATTERN] : array($route[self::ROUTE_PATTERN]);
    foreach ($patterns as $pattern) {
      if (!preg_match("#$pattern#", $path, $matches)) continue;
      foreach ($matches as $key => $value) {
        if (is_numeric($key)) $key = "arg_{$key}"; // @notice should be document or better refactored
        $request->getArguments()->set($key, $value);
      }
      if (isset($route['requirements'])) {
        foreach ($route['requirements'] as $req) {
          switch ($req['type']) {
            case 'has':
              if (!$request->has($req['field'])) return false;
              break;
            case 'eq':
              if ($request->get($req['field']) !== $req['value']) return false;
              break;
            default:
          }
        }
      }
      $this->ee->emit('match', array(
        'pattern' => $pattern,
        'path'    => $path,
        'ctrl'    => $route['controller'],
        'action'  => $route['action'] . 'Action',
      ));

      return true;
    }

    return false;
  }
  public function getRoutes() { return $this->routes; }
}
