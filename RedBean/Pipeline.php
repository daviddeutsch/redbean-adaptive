<?php

class RedBean_Pipeline
{
	/**
	 * @var RedBean_Instance
	 */
	private $r;

	public function __construct( $instance )
	{
		$this->r = clone $instance;

		$this->r->prefix('sys_pipeline_');
	}

	public function addExternalListener( $name )
	{
		$listener = $this->r->dispense('listener');

		$listener->name      = $name;
		$listener->location  = 'external';
		$listener->created   = $this->r->isoDateTime();
		$listener->last_call = $this->r->isoDateTime();

		return $this->r->store($listener);
	}

	public function getUpdatesForListener( $name )
	{
		$listener = $this->r->x->one->listener->name($name);

		$listener->last_call = $this->r->isoDateTime();

		$updates = $this->r->x->all->update->related($listener)->find();

		$output = array();
		foreach ( $updates as $update ) {
			$output[] = $update->export();

			$this->r->unassociate($listener, $update);
		}
	}

	public function subscribe( $listener, $resource )
	{
		$listener = $this->r->x->one->listener->name($listener)->find();

		if ( empty($listener->id) ) return false;

		$resource = $this->r->x->one->resource->path($resource)->find(true);

		return $this->r->associate($resource, $listener);
	}

	public function emit( $update )
	{
		// TODO: Rework to support genuine path support w/ permutations
		$listeners = array_unique(
			array_merge(
				$this->r->x->resource
					->path($update->path)->find(true)->sharedListener,
				$this->r->x->resource
					->path($update->type)->find(true)->sharedListener
			)
		);

		foreach( $listeners as $listener ) {
			if ( $listener->location == 'external' ) {
				$this->r->associate($listener, $update);
			} else {
				// TODO: Support internal callbacks
			}
		}
	}

	public function add( $bean )
	{
		$this->emit(
			$this->r->_(
				'update',
				array(
					'operation' => 'add',
					'path' => $bean->getMeta('type') . '/' . $bean->id,
					'type' => $bean->getMeta('type'),
					'object_id' => $bean->id,
					'object' => json_encode($bean),
					'created' => $this->r->isoDateTime()
				),
				true
			)
		);
	}

	public function update( $bean )
	{
		$changes = $bean->getMeta('sys.changes');

		if ( empty($changes) ) return;

		$this->emit(
			$this->r->_(
				'update',
				array(
					'operation' => 'update',
					'path' => $bean->getMeta('type') . '/' . $bean->id,
					'type' => $bean->getMeta('type'),
					'object_id' => $bean->id,
					'object' => json_encode($bean),
					'created' => $this->r->isoDateTime()
				),
				true
			)
		);
	}


	public function delete( $bean )
	{
		$this->emit(
			$this->r->_(
				'update',
				array(
					'operation' => 'remove',
					'path' => $bean->getMeta('type') . '/' . $bean->id,
					'type' => $bean->getMeta('type'),
					'object_id' => $bean->id,
					'object' => json_encode($bean),
					'created' => $this->r->isoDateTime()
				),
				true
			)
		);
	}
}
