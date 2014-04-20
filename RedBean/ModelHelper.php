<?php
/**
 * RedBean Model Helper
 *
 * @file    RedBean/ModelHelper.php
 * @desc    Connects beans to models, in essence
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * This is the core of so-called FUSE.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_ModelHelper implements RedBean_Observer
{

	/**
	 * @var RedBean_IModelFormatter|callable
	 */
	private $modelFormatter;

	/**
	 * @var RedBean_DependencyInjector
	 */
	private $dependencyInjector;

	private $modelCache;

	/**
	 * @see RedBean_Observer::onEvent
	 */
	public function onEvent( $eventName, $bean )
	{
		$bean->$eventName();
	}

	/**
	 * Given a model ID (model identifier) this method returns the
	 * full model name.
	 *
	 * @param string           $model
	 * @param RedBean_OODBBean $bean
	 *
	 * @return string
	 */
	public function getModelName( $model, $bean = NULL )
	{
		if ( isset($this->modelCache[$model]) ) {
			return $this->modelCache[$model];
		}

		if ( is_object($this->modelFormatter) ) {
			$name = $this->modelFormatter->formatModel( $model, $bean );
		} elseif ( is_callable($this->modelFormatter) ) {
			$name = call_user_func( $this->modelFormatter, $model, $bean );
		} else {
			$prefix = defined('REDBEAN_MODEL_PREFIX') ? REDBEAN_MODEL_PREFIX : 'Model_';

			$name = $prefix . ucfirst( $model );
		}

		$this->modelCache[$model] = $name;

		return $name;
	}

	/**
	 * Sets the model formatter to be used to discover a model
	 * for Fuse.
	 *
	 * @param string $modelFormatter
	 *
	 * @return void
	 */
	public function setModelFormatter( $modelFormatter )
	{
		$this->modelFormatter = $modelFormatter;
	}

	/**
	 * Obtains a new instance of $modelClassName, using a dependency injection
	 * container if possible.
	 *
	 * @param string $modelClassName name of the model
	 *
	 * @return object
	 */
	public function factory( $modelClassName )
	{
		if ( $this->dependencyInjector ) {
			return $this->dependencyInjector->getInstance( $modelClassName );
		}

		$model = new $modelClassName();

		return $model;
	}

	/**
	 * Sets the dependency injector to be used.
	 *
	 * @param RedBean_DependencyInjector $di injector to be used
	 *
	 * @return void
	 */
	public function setDependencyInjector( RedBean_DependencyInjector $di )
	{
		$this->dependencyInjector = $di;
	}

	/**
	 * Stops the dependency injector from resolving dependencies. Removes the
	 * reference to the dependency injector.
	 *
	 * @return void
	 */
	public function clearDependencyInjector()
	{
		$this->dependencyInjector = NULL;
	}

	/**
	 * Attaches the FUSE event listeners. Now the Model Helper will listen for
	 * CRUD events. If a CRUD event occurs it will send a signal to the model
	 * that belongs to the CRUD bean and this model will take over control from
	 * there.
	 *
	 * @param RedBean_Observable $observable
	 *
	 * @return void
	 */
	public function attachEventListeners( RedBean_Observable $observable )
	{
		foreach ( array( 'update', 'open', 'delete', 'after_delete', 'after_update', 'dispense' ) as $e ) {
			$observable->addEventListener( $e, $this );
		}
	}
}
