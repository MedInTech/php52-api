<?php

interface MedInTech_Api_IResponse
{
  const STATUS_CONTINUE        = 100;
  const STATUS_SWITCH_PROTOCOL = 101;
  const STATUS_PROCESSING      = 102;

  const STATUS_OK                = 200;
  const STATUS_CREATED           = 201;
  const STATUS_ACCEPTED          = 202;
  const STATUS_NON_AUTHORITATIVE = 203;
  const STATUS_NO_CONTENT        = 204;
  const STATUS_RESET_CONTENT     = 205;
  const STATUS_PARTIAL_CONTENT   = 206;
  const STATUS_MULTISTATUS       = 207;
  const STATUS_ALREADY_REPORTED  = 208;
  const STATUS_IM_USED           = 226;

  const STATUS_MULTIPLE_CHOICE    = 300;
  const STATUS_MOVED_PERMANENT    = 301;
  const STATUS_MOVED              = 302;
  const STATUS_SEE_OTHER          = 303;
  const STATUS_NOT_MODIFIED       = 304;
  const STATUS_USE_PROXY          = 305;
  const STATUS_REDIRECT_TEMP      = 307;
  const STATUS_REDIRECT_PERMANENT = 308;

  const STATUS_BAD_REQUEST           = 400;
  const STATUS_UNATHORIZED           = 401;
  const STATUS_PAYMENT_REQUIRED      = 402;
  const STATUS_FORBIDDEN             = 403;
  const STATUS_NOT_FOUND             = 404;
  const STATUS_METHOD_NOT_ALLOWED    = 405;
  const STATUS_UNACCEPTABLE          = 406;
  const STATUS_UNATHORIZED_PROXY     = 407;
  const STATUS_TIMEOUT               = 408;
  const STATUS_CONFLICT              = 409;
  const STATUS_GONE                  = 410;
  const STATUS_LENGTH_REQUIRED       = 411;
  const STATUS_PRECONDITION_FAILED   = 412;
  const STATUS_PAYLOAD_TOO_LARGE     = 413;
  const STATUS_URI_TOO_LONG          = 414;
  const STATUS_UNSUPPORTED_MEDIA     = 415;
  const STATUS_RANGE_NOT_SATISFIABLE = 416;
  const STATUS_EXPECTATION_FAILED    = 418;
  const STATUS_AUTH_EXPIRED          = 419;
  const STATUS_TOO_MANY_REQUESTS     = 429;

  const STATUS_SERVER_ERROR        = 500;
  const STATUS_NOT_IMPLEMENTED     = 501;
  const STATUS_BAD_GATEWAY         = 502;
  const STATUS_SERVICE_UNAVAILABLE = 503;

  public function setHeaders(MedInTech_Struct_Container_Interface $headers, $replace = true);

  /** @return MedInTech_Struct_Container_Interface */
  public function getHeaders();

  public function getBody();

  public function getStatus();

  public function setRequest(MedInTech_Api_IRequest $request);

  public function getRequest();

  public function isSuccess();

}