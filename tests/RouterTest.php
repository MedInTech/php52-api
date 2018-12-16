<?php

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
  /** @var MedInTech_IoC_Interface */
  private $container;
  /** @var MedInTech_Api_IRouter */
  private $router;
  /** @var MedInTech_Api_IRequest */
  private $request;
  protected function setUp()
  {
    $this->container = new MedInTech_IoC_Basic();
    $this->router = new MedInTech_Api_Router($this->container);
    $this->request = $fooRequest = new MedInTech_Api_Request();
  }
  /**
   * @dataProvider routesData
   */
  public function testLoadResolve($route, $request, $return)
  {
    $this->router->loadRoute($route);

    $this->assertEquals($return, $this->router->resolve($request));
  }

  public function testLoadJsonFile()
  {
    $file = tmpfile();
    fwrite($file, json_encode([['prefix' => '/api', 'action' => 'foo', 'pattern' => '^/foo/(?<id>\d+)$']]));
    fseek($file, 0);
    $fname = stream_get_meta_data($file)['uri'];
    $this->router->loadRoute([
      'type'       => 'json_file',
      'controller' => 'RouterTestController',
      'file'       => $fname,
    ]);

    $this->assertEquals(
      18,
      $this->router->resolve(MedInTech_Api_Request::rawBuild("GET /api/foo/18 HTTP/1.0\r\n"))
    );

    fclose($file);
  }

  public function test404()
  {
    $this->router->loadRoute([
      'controller' => 'RouterTestController',
      'pattern'    => '^/foo$',
      'action'     => 'bar',
    ]);

    $this->assertEquals(
      '404',
      $this->router->resolve(MedInTech_Api_Request::rawBuild("GET /foo/18 HTTP/1.0\r\n"))
    );
  }
  public function testWrongHttpVerb()
  {
    $this->router->loadRoute([
      'controller' => 'RouterTestController',
      'pattern'    => '^/foo$',
      'action'     => 'bar',
      'methods'    => ['POST'],
    ]);

    $this->assertEquals('404', $this->router->resolve(MedInTech_Api_Request::rawBuild("GET /foo HTTP/1.0\r\n")));
  }
  public function testWrongPrefix()
  {
    $this->router->loadRoute([
      'prefix'     => '/api',
      'controller' => 'RouterTestController',
      'pattern'    => '^/foo$',
      'action'     => 'bar',
    ]);

    $this->assertEquals('404', $this->router->resolve(MedInTech_Api_Request::rawBuild("GET /foo HTTP/1.0\r\n")));
  }

  /**
   * @expectedException MedInTech_Api_RouterException
   * @expectedExceptionMessage Method barAction does not exists
   */
  public function testNoAction()
  {
    $this->router->loadRoute([
      'controller' => 'RouterTestController',
      'pattern'    => '^/foo$',
      'action'     => 'bar',
    ]);

    $this->router->resolve(MedInTech_Api_Request::rawBuild("GET /foo HTTP/1.0\r\n"));
  }

  public function routesData()
  {
    return [
      [
        ['controller' => 'RouterTestController', 'action' => 'foo', 'pattern' => '^/foo/(?<id>.*)$', 'methods' => 'GET'],
        MedInTech_Api_Request::rawBuild("GET /foo/18 HTTP/1.0\r\n"),
        18,
      ],
      [
        ['controller' => 'RouterTestController', 'action' => 'foo', 'pattern' => '^/foo', 'overrides' => ['id' => 42]],
        MedInTech_Api_Request::rawBuild("GET /fooUnbounded HTTP/1.0\r\n"),
        42,
      ],
      [
        ['controller' => 'RouterTestController', 'action' => 'foo', 'pattern' => '^/foo$', 'prefix' => '/api', 'overrides' => ['id' => 51]],
        MedInTech_Api_Request::rawBuild("GET /api/foo HTTP/1.0\r\n"),
        51,
      ],
      [
        [
          'type'       => 'list',
          'prefix'     => '/api',
          'controller' => 'RouterTestController',
          'overrides'  => ['id' => 51],
          'list'       => [
            [
              'pattern' => '^/foo$',
              'action'  => 'foo',
            ],
          ],
        ],
        MedInTech_Api_Request::rawBuild("GET /api/foo HTTP/1.0\r\n"),
        51,
      ],
      [
        [
          'controller'   => 'RouterTestController',
          'action'       => 'foo',
          'pattern'      => '/foo',
          'methods'      => 'GET',
          'requirements' => [
            ['type' => 'has', 'field' => 'id'],
            ['type' => 'eq', 'field' => 'token', 'value' => 'secret'],
          ],
        ],
        MedInTech_Api_Request::rawBuild("GET /foo?id=18&token=secret HTTP/1.0\r\n"),
        18,
      ],
    ];
  }
}

class RouterTestController
{
  public function fooAction($id)
  {
    return $id;
  }
}