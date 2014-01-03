<?php

class RedBean_Pipeline
{
	/**
	 * @var RedBean_Instance
	 */
	private $instance;

	public function __construct( $instance )
	{
		$this->instance = $instance;
	}

	public function addClient()
	{
		$listener = $this->instance->dispense('pipelistener');

		return $this->instance->store($listener);
	}

	public function followResource( $client, $resource )
	{

	}
}
