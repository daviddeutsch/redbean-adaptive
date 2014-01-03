<?php

class RedBean_PipelineModel extends RedBean_SimpleModel
{
	/**
	 * @var RedBean_Instance
	 */
	protected $r;

	public function bindInstance( $instance )
	{
		$this->r = $instance;
	}
}
