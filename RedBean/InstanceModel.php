<?php

class RedBean_PipelineModel extends RedBean_SimpleModel
{
	/**
	 * @var RedBean_Instance
	 */
	private $instance;

	public function bindInstance( $instance )
	{
		$this->instance = $instance;
	}
}
