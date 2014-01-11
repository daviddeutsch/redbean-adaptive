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

	public static function addSubscriber( $name, $callback=null )
	{
		return self::$r->_(
			'subscriber',
			array(
				'name' => $name,
				'created' => self::$r->isoDateTime(),
				'callback' => $callback
			),
			true
		);
	}

	public static function doesSubscriberExist( $name )
	{
		$subscriber = self::$r->x->one->subscriber->name($name)->find();

		return !empty($subscriber->id);
	}

	public static function getUpdatesForSubscriber( $name )
	{
		$subscriber = self::$r->x->one->subscriber->name($name)->find();

		if ( empty($subscriber->id) ) return null;

		$updates = self::$r->x->all->update->related($subscriber)->find();

		if ( empty($updates) ) return null;

		$output = array();
		foreach ( $updates as $update ) {
			$output[] = $update->export();

			self::$r->unassociate($subscriber, $update);
		}

		return $output;
	}

	public static function addPublisher( $name )
	{
		return self::$r->_(
			'publisher',
			array(
				'name' => $name,
				'created' => self::$r->isoDateTime()
			),
			true
		);
	}

	public static function doesPublisherExist( $name )
	{
		$publisher = self::$r->x->one->publisher->name($name)->find();

		return !empty($publisher->id);
	}

	public static function subscribe( $listener, $resource )
	{
		$subscriber = self::$r->x->one->listener->name($listener)->find();

		if ( empty($subscriber->id) ) return false;

		$resource = self::$r->x->one->resource->path($resource)->find(true);

		return self::$r->associate($resource, $subscriber);
	}

	public static function emit( $update )
	{
		$resource_exact = self::$r->x->resource->path($update->path)->find();
		$resource_type  = self::$r->x->resource->path($update->type)->find();

		if ( empty($resource_exact->id) && empty($resource_type->id) ) return;

		$subscribers = array();

		if ( !empty($resource_exact->id) ) {
			$subscribers = array_unique(
				array_merge($subscribers, $resource_exact->sharedSubscriber)
			);
		}

		if ( !empty($resource_type->id) ) {
			$subscribers = array_unique(
				array_merge($subscribers, $resource_type->sharedSubscriber)
			);
		}

		if ( empty($subscribers) ) return;

		foreach( $subscribers as $subscriber ) {
			if ( empty($subscriber->callback) ) {
				// No callback, so this will be stashed for retrieval by the subscriber
				self::$r->associate($subscriber, $update);
			} else {
				// TODO: Support internal callbacks

				/*
				 * Most likely, this would be one or more callback observers
				 * that are submitted when the Pipeline is set up. Then, we
				 * could allow stuff like:
				 *
				 * RedBean_Pipeline::addCallbackObserver( 'my', new myObserver() );
				 *
				 * RedBean_Pipeline::addSubscriber(
				 *    'test_subscriber',
				 *    'my.customMethod'
				 * );
				 *
				 * When the update is emitted, myObserver::customMethod(); is
				 * called with the update as only input.
				 */
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
