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

		self::$r = new RedBean_Instance();

		$writer = new \RedBean_QueryWriter_MySQL( $instance->toolbox->getDatabaseAdapter() );
		$redbean = new \RedBean_OODB( $writer );

		$toolbox = new \RedBean_ToolBox(
			$redbean,
			$instance->toolbox->getDatabaseAdapter(),
			$writer
		);

		self::$r->configureWithToolbox($toolbox);

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
		$resource_exact = self::$r->x->resource->path($update->path)->find();
		$resource_type  = self::$r->x->resource->path($update->type)->find();

		if ( empty($resource_exact->id) && empty($resource_type->id) ) return;

		$listeners = array();

		if ( !empty($resource_exact->id) ) {
			$listeners = array_unique(
				array_merge($listeners, $resource_exact->sharedListener)
			);
		}

		if ( !empty($resource_type->id) ) {
			$listeners = array_unique(
				array_merge($listeners, $resource_type->sharedListener)
			);
		}

		if ( empty($listeners) ) return;

		foreach( $listeners as $listener ) {
			if ( $listener->location == 'external' ) {
				self::$r->associate($listener, $update);
			} else {
				// TODO: Support internal callbacks
			}
		}
	}

	/**
	 * @param $bean \RedBean_OODBBean
	 */
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
					'object' => json_encode( $bean->export() ),
					'created' => self::$r->isoDateTime()
				),
				true
			)
		);
	}

	/**
	 * @param $bean \RedBean_OODBBean
	 */
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
					'object' => json_encode( $bean->export() ),
					'created' => self::$r->isoDateTime()
				),
				true
			)
		);
	}

	/**
	 * @param $bean \RedBean_OODBBean
	 */
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
					'object' => json_encode( $bean->export() ),
					'created' => self::$r->isoDateTime()
				),
				true
			)
		);
	}
}
