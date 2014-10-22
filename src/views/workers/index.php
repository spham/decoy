<ul id="workers" class="list-unstyled">
	
	<? foreach($workers as $worker): ?>
		<li data-js-view="worker" data-log-url=<?=route('decoy\workers@tail', strtolower(urlencode($worker->getName())))?> data-interval="<?=$worker->currentInterval('raw')?>">
			
			<div class="pull-right actions">
				<span class="status <?=$worker->isRunning()?'ok':'fail'?>">Rate: <strong><?=$worker->currentInterval('abbreviated')?></strong></span>
				<a class="btn btn-default">Logs</a>
			</div>
			
			<h3><?=ucwords(str_replace(':', ' : ', $worker->getName()))?></h3>
			<?=HTML::tag($worker->getDescription())?>
			
			<ul>
				<li>Last worker execution: <?=$worker->lastHeartbeat()?></li>
				<li>Last heartbeat<?if(!$worker->isRunning()):?> (and execution)<?endif?>: <?=$worker->lastHeartbeatCheck()?></li>
				<li>Currently executing every: <?=$worker->currentInterval()?></li>
			</ul>
			
			<div class="log closed">Loading...</div>
		</li>
	<? endforeach ?>
	
</ul>