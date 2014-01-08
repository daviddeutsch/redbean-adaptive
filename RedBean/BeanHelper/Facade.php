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
	 * @var RedBean_QueryWriter_AQueryWriter
	 */
	private $writer;

	/**
	 * @var RedBean_OODB
	 */
	private $redbean;

	/**
	 * @var RedBean_ModelHelper
	 */
	private $helper;

	public function __construct( $writer, $redbean )
	{
		$this->writer =& $writer;

		$this->redbean =& $redbean;

		$this->helper = new RedBean_ModelHelper();
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

	public function getModelHelper()
	{
		return $this->helper;
	}

	/**
	 * @see RedBean_BeanHelper::getWriterRedbean
	 */
	public function getWriterRedbean()
	{
		return array( $this->writer, $this->redbean );
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
