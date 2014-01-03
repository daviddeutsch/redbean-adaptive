<?php
/**
 * Bean Helper.
 * The Bean helper helps beans to access access the toolbox and
 * FUSE models. This Bean Helper makes use of the facade to obtain a
 * reference to the toolbox.
 *
 * @file    RedBean/BeanHelperFacade.php
 * @desc    Finds the toolbox for the bean.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_BeanHelper_Facade implements RedBean_BeanHelper
{
	/**
	 * @var RedBean_Instance
	 */
	private $instance;

	/**
	 * @var RedBean_ModelHelper
	 */
	private $helper;

	public function __construct( $instance )
	{
		$this->instance = $instance;

		$this->helper = new RedBean_ModelHelper($this->instance);
	}

	/**
	 * @see RedBean_BeanHelper::getInstance
	 */
	public function getInstance()
	{
		return $this->instance;
	}

	/**
	 * @see RedBean_BeanHelper::getModelForBean
	 */
	public function getModelForBean( RedBean_OODBBean $bean )
	{
		$modelName = $this->helper->getModelName( $bean->getMeta( 'type' ), $bean );

		if ( !class_exists( $modelName ) ) {
			return NULL;
		}

		$obj = $this->helper->factory( $modelName );
		$obj->loadBean( $bean );

		return $obj;
	}

	/**
	 * @see RedBean_BeanHelper::getExtractedToolbox
	 */
	public function getExtractedToolbox()
	{
		$toolbox = $this->instance->getToolbox();

		return array( $toolbox->getRedBean(), $toolbox->getDatabaseAdapter(), $toolbox->getWriter(), $toolbox );
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
		$this->helper->setModelFormatter( $modelFormatter );
	}

}
