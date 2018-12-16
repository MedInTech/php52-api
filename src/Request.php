<?php

class MedInTech_Api_Request implements MedInTech_Api_IRequest
{
  const CRLF = "\r\n";
  /** @var string */
  public $body = '';
  /** @var MedInTech_Struct_Container_Interface */
  private $headers;
  /** @var MedInTech_Struct_Container_Interface */
  private $params;
  /** @var MedInTech_Struct_Container_Interface */
  private $cookies;
  /** @var MedInTech_Struct_Container_Interface */
  private $arguments;
  /** @var string */
  private $method = 'GET';
  /** @var string */
  private $uri = '/';
  /** @var string */
  private $httpVersion = '1.0';
  /** @var string */
  private $pathInfo = '/';

  public function __construct()
  {
    $this->arguments = new MedInTech_Struct_Container_Simple();
    $this->params = new MedInTech_Struct_Container_Simple();
    $this->headers = new MedInTech_Struct_Container_CIKey();
    $this->cookies = new MedInTech_Struct_Container_CIKey();
  }
  public static function build()
  {
    $request = new self;
    $request->setMethod($_SERVER['REQUEST_METHOD']);
    $request->setUri($_SERVER['REQUEST_URI']);
    $request->setPathInfo(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/');
    $request->setHeaders(self::parseHeaders());
    $request->setBody(file_get_contents('php://input'));
    $request->setParameters(new MedInTech_Struct_Container_Simple(array_merge($_GET, $_POST, $_FILES)));
    $request->setCookies(new MedInTech_Struct_Container_Simple($_COOKIE));
    $request->setArguments(new MedInTech_Struct_Container_Simple());

    return $request;
  }

  public static function rawBuild($httpRequest)
  {
    $request = new self();

    $parts = explode(self::CRLF . self::CRLF, $httpRequest);
    $headers = explode(self::CRLF, array_shift($parts));
    $body = array_shift($parts);
    unset($parts);

    // GET /uri HTTP/1.0
    $statusLine = preg_split('#\s+#', array_shift($headers), 3);
    $method = array_shift($statusLine);
    $uri = array_shift($statusLine);
    $httpVer = array_shift($statusLine);
    $httpVer = explode('/', $httpVer);
    $httpVer = end($httpVer);
    unset($statusLine);

    $uriParts = explode('?', $uri);
    $pathInfo = array_shift($uriParts);
    $query = array_shift($uriParts);
    parse_str($query, $queryParsed);

    $request->setMethod($method);
    $request->setPathInfo($pathInfo);
    $request->setHttpVersion($httpVer);

    $request->getParameters()->exchangeArray($queryParsed);
    foreach ($headers as $header) {
      // Content-type: 42
      $parts = preg_split('#:\s*#', $header, 2);
      $name = array_shift($parts);
      $value = array_shift($parts); // todo: research, may be some decode required
      unset($parts);
      $request->getHeaders()->set($name, $value);
    }
    $request->setBody($body); // some body parse can be applied here

    // fixme: we have empty params/query/cookies/arguments here
    return $request;
  }

  public function has($key)
  {
    return $this->arguments->has($key) || $this->params->has($key);
  }
  public function get($key, $default = null)
  {
    return $this->arguments->get($key, $this->params->get($key, $default));
  }
  public function getHeader($key) { return $this->headers->get($key); }

  private static function parseHeaders($all = false)
  {
    $whitelist = array(
      'CONTENT_TYPE',
    );
    $headers = new MedInTech_Struct_Container_CIKey();
    foreach ($_SERVER as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $header = substr($name, 5);
      } elseif (in_array(strtoupper($name), $whitelist, true) || $all) {
        $header = $name;
      } else {
        continue;
      }

      $header = strtr($header, array('_' => '-'));
      $headers->set($header, $value);
    }

    return $headers;
  }

  // Low level
  public function getMethod() { return $this->method; }
  public function getUri() { return $this->uri; }
  public function getPathInfo()
  {
    return $this->pathInfo;
  }
  public function getHttpVersion() { return $this->httpVersion; }
  public function getHeaders() { return $this->headers; }
  public function getBody() { return $this->body; }

  public function setMethod($method) { $this->method = $method; }
  public function setUri($uri) { $this->uri = $uri; }
  public function setPathInfo($pathInfo)
  {
    $this->pathInfo = $pathInfo;
  }
  public function setHttpVersion($httpVersion) { $this->httpVersion = $httpVersion; }
  public function setHeaders(MedInTech_Struct_Container_Interface $headers, $replace = true)
  {
    if ($replace) {
      $this->headers = $headers;
    } else {
      foreach ($headers->all() as $key => $value) {
        $this->headers->set($key, $value);
      }
    }
  }
  public function setBody($body) { $this->body = $body; }

  public function render()
  {
    if (strtoupper($this->method) === 'GET') {
      $params = http_build_query($this->params->all());
      $sign = strpos($this->uri, '?') === false ? '?' : '&';
      $this->setUri($this->getUri() . $sign . $params);
    }
    $lines[] = "{$this->method} {$this->uri} HTTP/{$this->httpVersion}";
    $body = $this->getBody();
    $this->headers->set('Content-Length', strlen($body)); // length in bytes, not mb_strlen

    if (!$this->headers->has('Accept')) {
      $this->headers->set('Accept', '*/*');
    }
    if (!$this->headers->has('User-Agent')) {
      $this->headers->set('User-Agent', 'MedIntTech Http Client');
    }

    foreach ($this->headers->all() as $name => $value) {
      $nameParts = explode('-', $name);
      foreach ($nameParts as &$np) {
        $np = ucfirst($np);
      }
      unset($np);
      $name = implode('-', $nameParts);
      $lines[] = "$name: $value";
    }
    foreach ($this->cookies->all() as $name => $value) {
      $lines[] = "Set-Cookie: $name=$value";
    }
    $lines[] = '';
    $lines[] = $body;

    return implode(self::CRLF, $lines);
  }
  public function __toString() { return $this->render(); }

  // High level
  public function getParameters() { return $this->params; }
  public function getArguments() { return $this->arguments; }
  public function getCookies() { return $this->cookies; }
  public function setParameters(MedInTech_Struct_Container_Interface $parameters, $replace = true)
  {
    if ($replace) {
      $this->params = $parameters;
    } else {
      foreach ($parameters->all() as $key => $value) {
        $this->params->set($key, $value);
      }
    }
  }
  public function setArguments(MedInTech_Struct_Container_Interface $arguments, $replace = true)
  {
    if ($replace) {
      $this->arguments = $arguments;
    } else {
      foreach ($arguments->all() as $key => $value) {
        $this->arguments->set($key, $value);
      }
    }
  }
  public function setCookies(MedInTech_Struct_Container_Interface $cookies, $replace = true)
  {
    if ($replace) {
      $this->cookies = $cookies;
    } else {
      foreach ($cookies->all() as $key => $value) {
        $this->cookies->set($key, $value);
      }
    }
  }
}
