<?php

class RedBean_PipelineModel extends RedBean_SimpleModel
{
	/**
	 * @var RedBean_Instance
	 */
	private $instance;

	public function open()
	{

	}

	public function after_update( $bean )
	{
		$delta = $this->getDelta();

		if ( is_null($delta) ) return;

		R::pipeline()->push(
			array(
				'action' => 'update',
				'type' => $this->bean->getMeta('type'),
				'item_id' => $this->bean->id,
				'delta' => json_encode($delta)
			)
		);
	}

	public function after_delete()
	{
		R::pipeline()->push(
			array(
				'action' => 'delete',
				'type' => $this->bean->getMeta('type'),
				'item_id' => $this->bean->id
			)
		);
	}

	private function getDelta()
	{
		$delta = $this->bean->getMeta('sys.delta');

		if ( empty($delta) ) return null;

		return $delta;
	}
}
