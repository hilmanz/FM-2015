<?php

class PrivateLeagueMailerShell extends AppShell{

	var $tasks = array('PrivateLeagueMailer');

	public function main()
	{
		$this->out('Private League Mailer');
        $this->PrivateLeagueMailer->execute();
	}

}