<?php
/**
 * RedBean Pipeline
 *
 * @file    RedBean/Pipeline.php
 * @desc    Pipeline for Pub/Sub applications.
 * @author  David Deutsch
 * @license BSD/GPLv2
 *
 * copyright (c) David Deutsch
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Pipeline
{
	/**
	 * @var RedBean_Instance
	 */
	private static $r;

	public static function configureWithInstance( $instance, $prefix=null )
	{
		// Cheap trick to avoid recursive bs right now
		if ( !empty(self::$r) ) return;

		self::$r = clone $instance;

		self::$r->prefix($prefix . 'rsys_');
	}

	public static function addSubscriber( $details )
	{
		$expected = array('name', 'callback', 'lease_seconds', 'secret');

		foreach ( $expected as $k ) {
			if ( !isset($details->$k) ) {
				$details->$k = '';
			}
		}

		$expiration = 0;
		if ( !empty($details->lease_seconds) ) {
			$expiration = time() + $details->lease_seconds;
		}

		return self::$r->_(
			'subscriber',
			array(
				'name' => $details->name,
				'callback' => $details->callback,
				'secret' => $details->secret,
				'created' => self::$r->isoDateTime(),
				'expires' => self::$r->isoDateTime($expiration)
			),
			true
		);
	}

	public static function doesSubscriberExist( $name )
	{
		$subscriber = self::$r->x->one->subscriber->name($name)->find();

		return !empty($subscriber->id);
	}

	public static function getSubscriber( $name )
	{
		return self::$r->x->one->subscriber->name($name)->find();
	}

	public static function removeSubscriber( $name )
	{
		$subscriber = self::$r->x->one->subscriber->name($name)->find();

		if ( !empty($subscriber->id) ) {
			self::$r->trash($subscriber);
		}
	}

	public static function getUpdatesForSubscriber( $name )
	{
		$subscriber = self::$r->x->one->subscriber->name($name)->find();

		if ( empty($subscriber->id) ) return null;

		$updates = self::$r->x->all->update->related($subscriber)->find();

		if ( empty($updates) ) return null;

		if ( !is_array($updates) ) {
			$updates = array($updates);
		}

		$output = array();
		foreach ( $updates as $update ) {
			$data = $update->export();

			$data->id = (int) $data->id;

			$data->object = json_decode($data->object);

			$data->object->id = (int) $data->object->id;

			$output[] = $data;

			self::$r->unassociate($subscriber, $update);
		}

		return $output;
	}

	public static function addPublisher( $details )
	{
		$expected = array('name', 'callback', 'lease_seconds', 'secret');

		foreach ( $expected as $k ) {
			if ( !isset($details->$k) ) {
				$details->$k = '';
			}
		}

		$expiration = 0;
		if ( !empty($details->lease_seconds) ) {
			$expiration = time() + $details->lease_seconds;
		}

		return self::$r->_(
			'publisher',
			array(
				'name' => $details->name,
				'callback' => $details->callback,
				'secret' => $details->secret,
				'created' => self::$r->isoDateTime(),
				'expires' => self::$r->isoDateTime($expiration)
			),
			true
		);
	}

	public static function doesPublisherExist( $name )
	{
		$publisher = self::$r->x->one->publisher->name($name)->find();

		return !empty($publisher->id);
	}

	public static function getPublisher( $name )
	{
		return self::$r->x->one->publisher->name($name)->find();
	}

	public static function removePublisher( $name )
	{
		$publisher = self::$r->x->one->publisher->name($name)->find();

		if ( !empty($publisher->id) ) {
			self::$r->trash($publisher);
		}
	}

	public static function subscribe( $subscriber, $resource )
	{
		$subscriber = self::$r->x->one->subscriber->name($subscriber)->find();

		if ( empty($subscriber->id) ) return false;

		$resource = self::$r->x->one->resource->path($resource)->find(true);

		if ( empty($resource->id) ) return false;

		return self::$r->associate($resource, $subscriber);
	}

	public static function unsubscribe( $subscriber, $resource )
	{
		$subscriber = self::$r->x->one->subscriber->name($subscriber)->find();

		if ( empty($subscriber->id) ) return false;

		$resource = self::$r->x->one->resource->path($resource)->find();

		if ( empty($resource->id) ) return false;

		return self::$r->unassociate($resource, $subscriber);
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
	public static function add( $bean, $path, $type )
	{
		self::emit( self::makeUpdate($bean, $path, $type, 'add') );
	}

	/**
	 * @param $bean \RedBean_OODBBean
	 */
	public static function update( $bean, $path, $type )
	{
		$changes = $bean->getMeta('sys.changes');

		if ( empty($changes) ) return;

		self::emit( self::makeUpdate($bean, $path, $type, 'update') );
	}

	/**
	 * @param $bean \RedBean_OODBBean
	 */
	public static function delete( $bean, $path, $type )
	{
		self::emit( self::makeUpdate($bean, $path, $type, 'remove') );
	}

	private static function makeUpdate( $bean, $path, $type, $operation )
	{
		return self::$r->_(
			'update',
			array(
				'operation' => $operation,
				'path' => $path,
				'type' => $type,
				'objectid' => $bean->id,
				'object' => json_encode( $bean->export() ),
				'created' => self::$r->isoDateTime()
			),
			true
		);
	}

}
