<?php
/**
 * RedBean Access
 *
 * @file    RedBean/Access.php
 * @desc    Access for RBAC-style rules.
 * @author  David Deutsch
 * @license BSD/GPLv2
 *
 * copyright (c) David Deutsch
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Access
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

	public static function restrict( $resource, $action )
	{
		$resource = self::getResource($resource);

		self::$r->x->one->restriction->related($resource)->find(true);
	}

	public static function permit( $subject, $resource, $action )
	{
		$resource = self::getResource($resource);

		$restriction = self::getRestriction($resource);

		self::$r->associate($subject, $restriction);
	}

	public static function isPermitted( $subject, $resource, $action )
	{
		$resource = self::getResource($resource);

		$restriction = self::getRestriction($resource);

		self::$r->associate($subject, $restriction);
	}

	public static function getSubject( $subject )
	{
		return self::$r->x->one->subject->name($subject)->find(true);
	}

	public static function getRestriction( $resource )
	{
		return self::$r->x->one->restriction->related($resource)->find();
	}

	public static function getResource( $resource )
	{
		return self::$r->x->one->resource->path($resource)->find(true);
	}
}
