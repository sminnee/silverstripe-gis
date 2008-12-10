<?php
Director::addRules(100,array(
	'dev/tilerenderqueue/$Action/$ID' => 'TileRenderQueue_Controller'
));
?>