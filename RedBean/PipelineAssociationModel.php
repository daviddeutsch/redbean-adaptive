<?php
/**
 * RedBean Pipeline Association Model
 *
 * @file    RedBean/PipelineAssociationModel.php
 * @desc    Pipeline Model for Pub/Sub applications.
 * @author  David Deutsch
 * @license BSD/GPLv2
 *
 * copyright (c) David Deutsch
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_PipelineAssociationModel extends RedBean_SimpleModel
{
	public function after_update()
	{
		$paths = $this->makePath($this->bean);
		$types = $this->makeType($this->bean);

		foreach ( $paths as $i => $path ) {
			RedBean_Pipeline::add($this->bean, $path, $types[$i]);
		}
	}

	public function delete()
	{
		$paths = $this->makePath($this->bean);
		$types = $this->makeType($this->bean);

		foreach ( $paths as $i => $path ) {
			RedBean_Pipeline::delete($this->bean, $path, $types[$i]);
		}
	}

	protected function makePath( $bean )
	{
		$objects = explode('_', $bean->getMeta('type'));

		$oneid = $objects[0].'_id';
		$twoid = $objects[1].'_id';

		return array(
			$objects[0] . '/' . $bean->$oneid . '/' . $objects[1] . '/' . $bean->$twoid,
			$objects[1] . '/' . $bean->$twoid . '/' . $objects[0] . '/' . $bean->$oneid
		);
	}

	protected function makeType( $bean )
	{
		$objects = explode('_', $bean->getMeta('type'));

		$oneid = $objects[0].'_id';
		$twoid = $objects[1].'_id';

		return array(
			$objects[0] . '/' . $bean->$oneid . '/' . $objects[1],
			$objects[1] . '/' . $bean->$twoid . '/' . $objects[0]
		);
	}
}
