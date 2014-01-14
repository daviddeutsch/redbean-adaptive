<?php

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
