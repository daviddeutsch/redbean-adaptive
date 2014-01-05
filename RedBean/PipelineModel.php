<?php

class RedBean_PipelineModel extends RedBean_InstanceModel
{
	private $existing = false;

	public function open()
	{
		$this->existing = true;
	}

	public function after_update()
	{
		if ( $this->existing ) {
			$this->r->pipeline->update($this->bean);
		} else {
			$this->r->pipeline->add($this->bean);
		}
	}

	public function after_delete()
	{
		$this->r->pipeline->delete($this->bean);
	}
}
