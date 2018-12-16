<?php

interface MedInTech_Api_IRouter
{
  /** @return MedInTech_Event_IEmitter */
  public function ee();
  /** Loads route from rules */
  public function loadRoute($route, array $auxDependencies = array());
  /** Parse request to match some route */
  public function resolve(MedInTech_Api_IRequest $request);
  public function getRoutes();
}