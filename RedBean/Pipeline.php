<?php

class RedBean_Pipeline
{
	/**
	 * @var RedBean_Instance
	 */
	private static $r;

	public static function configureWithInstance( $instance )
	{
		// Cheap trick to avoid recursive bs right now
		if ( !empty(self::$r) ) return;

		self::$r = clone $instance;

		self::$r->prefix('sys_pipeline_');
	}

	public static function addExternalListener( $name )
	{
		$listener = self::$r->dispense('listener');

		$listener->name      = $name;
		$listener->location  = 'external';
		$listener->created   = self::$r->isoDateTime();
		$listener->last_call = self::$r->isoDateTime();

		return self::$r->store($listener);
	}

	public static function getUpdatesForListener( $name )
	{
		$listener = self::$r->x->one->listener->name($name);

		$listener->last_call = self::$r->isoDateTime();

		$updates = self::$r->x->all->update->related($listener)->find();

		$output = array();
		foreach ( $updates as $update ) {
			$output[] = $update->export();

			self::$r->unassociate($listener, $update);
		}
	}

	public static function subscribe( $listener, $resource )
	{
		$listener = self::$r->x->one->listener->name($listener)->find();

		if ( empty($listener->id) ) return false;

		$resource = self::$r->x->one->resource->path($resource)->find(true);

		return self::$r->associate($resource, $listener);
	}

	public static function emit( $update )
	{
		// TODO: Rework to support genuine path support w/ permutations
		$listeners = array_unique(
			array_merge(
				self::$r->x->resource
					->path($update->path)->find(true)->sharedListener,
				self::$r->x->resource
					->path($update->type)->find(true)->sharedListener
			)
		);

		foreach( $listeners as $listener ) {
			if ( $listener->location == 'external' ) {
				self::$r->associate($listener, $update);
			} else {
				// TODO: Support internal callbacks
			}
		}
	}

	public static function add( $bean )
	{
		self::emit(
			self::$r->_(
				'update',
				array(
					'operation' => 'add',
					'path' => $bean->getMeta('type') . '/' . $bean->id,
					'type' => $bean->getMeta('type'),
					'objectid' => $bean->id,
					'object' => json_encode($bean),
					'created' => self::$r->isoDateTime()
				),
				true
			)
		);
	}

	public static function update( $bean )
	{
		$changes = $bean->getMeta('sys.changes');

		if ( empty($changes) ) return;

		self::emit(
			self::$r->_(
				'update',
				array(
					'operation' => 'update',
					'path' => $bean->getMeta('type') . '/' . $bean->id,
					'type' => $bean->getMeta('type'),
					'objectid' => $bean->id,
					'object' => json_encode($bean),
					'created' => self::$r->isoDateTime()
				),
				true
			)
		);
	}


	public static function delete( $bean )
	{
		self::emit(
			self::$r->_(
				'update',
				array(
					'operation' => 'remove',
					'path' => $bean->getMeta('type') . '/' . $bean->id,
					'type' => $bean->getMeta('type'),
					'objectid' => $bean->id,
					'object' => json_encode($bean),
					'created' => self::$r->isoDateTime()
				),
				true
			)
		);
	}
}
