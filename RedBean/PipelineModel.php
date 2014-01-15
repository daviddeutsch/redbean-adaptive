<?php
/**
 * RedBean Pipeline Model
 *
 * @file    RedBean/PipelineModel.php
 * @desc    Pipeline Model for Pub/Sub applications.
 * @author  David Deutsch
 * @license BSD/GPLv2
 *
 * copyright (c) David Deutsch
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_PipelineModel extends RedBean_SimpleModel
{
	private $existing = false;

	public function open()
	{
		$this->existing = true;
	}

	public function after_update()
	{
		if ( $this->existing ) {
			RedBean_Pipeline::update($this->bean);
		} else {
			RedBean_Pipeline::add($this->bean);
		}
	}

	public function delete()
	{
		RedBean_Pipeline::delete($this->bean);
	}
}
