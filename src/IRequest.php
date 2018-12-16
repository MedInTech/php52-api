<?php

interface MedInTech_Api_IRequest
{
  public static function build();
  public static function rawBuild($httpRequest);

  public function has($field);
  public function get($key, $default = null); // expected order: argument, param

  /** @return MedInTech_Struct_Container_Interface */
  public function getParameters();
  /** @return MedInTech_Struct_Container_Interface */
  public function getArguments();
  /** @return MedInTech_Struct_Container_Interface */
  public function getCookies();

  public function getMethod();
  public function getUri();
  public function getPathInfo();
  public function getHttpVersion();
  /** @return MedInTech_Struct_Container_Interface */
  public function getHeaders();
  public function getBody();

  public function setMethod($method);
  public function setUri($uri);
  public function setPathInfo($pathInfo);
  public function setHttpVersion($httpVersion);
  public function setHeaders(MedInTech_Struct_Container_Interface $headers, $replace = true);
  public function setParameters(MedInTech_Struct_Container_Interface $parameters, $replace = true);
  public function setArguments(MedInTech_Struct_Container_Interface $arguments, $replace = true);
  public function setCookies(MedInTech_Struct_Container_Interface $cookies, $replace = true);
  public function setBody($body);

  public function render();

}