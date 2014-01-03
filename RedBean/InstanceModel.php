<?php

class RedBean_InstanceModel extends RedBean_SimpleModel
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
