<?php

//forever start --minUptime=600000 --spinSleepTime=600000 -c /bin/sh /home/football/public_html/web/app/Console/cake InGamePurchase

class InGamePurchaseShell extends AppShell{

	var $tasks = array('ManageInGamePurchase');

	public function main()
	{
		$this->out('Private League Mailer');
        $this->ManageInGamePurchase->execute();
	}

}