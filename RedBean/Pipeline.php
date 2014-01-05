<?php

class RedBean_Pipeline
{
	/**
	 * @var RedBean_Instance
	 */
	private $r;

	public function __construct( $instance )
	{
		$this->r = $instance;

		$this->r->prefix('sys_pipeline_');
	}

	public function addClient()
	{
		$listener = $this->r->dispense('listener');

		return $this->r->store($listener);
	}

	public function followResource( $listener, $resource )
	{
		$resource = $this->r->x->resource->path($resource)->find(true);

		$listener = $this->r->x->listener->sha($listener)->find(true);

		$this->r->associate($resource, $listener);
	}

	public function add( $bean )
	{
		$this->r->_(
			'event',
			array(
				'operation' => 'new',
				'path' => $bean->getMeta('type') . '/' . $bean->id,
				'object' => json_encode($bean),
				'created' => $this->r->isoDateTime()
			),
			true
		);
	}

	public function update( $bean )
	{
		$changes = $bean->getMeta('sys.changes');

		if ( empty($changes) ) return;

		$this->r->_(
			'event',
			array(
				'operation' => 'update',
				'path' => $bean->getMeta('type') . '/' . $bean->id,
				'object' => json_encode($bean),
				'created' => $this->r->isoDateTime()
			),
			true
		);
	}


	public function delete( $bean )
	{
		$this->r->_(
			'event',
			array(
				'operation' => 'delete',
				'path' => $bean->getMeta('type') . '/' . $bean->id,
				'object' => json_encode($bean),
				'created' => $this->r->isoDateTime()
			),
			true
		);
	}
}
