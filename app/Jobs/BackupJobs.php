<?php

namespace App\Jobs;

use App\Jobs\Jobs;

use Illuminate\Database\Capsule\Manager as DB;

class BackupJobs extends Jobs {

	public function call_db(){
		$logger = $this->logger;
		$db = $this->settings['db'];
		return $this->scheduler->call(function() use ($logger,$db){
			//////////////////////////      INICIO DEL JOBS
			$filename='db_ciro_'.date('G_a_m_d_y').'.sql';
			$dir = __DIR__.'/../../logs/';
			$result=exec('mysqldump '.$db['database'].' --password='.$db['password'].' --user='.$db['username'].' --single-transaction >'.$dir.$filename,$output);

			$logger->info('call_db output: '.$dir.$filename,$output,$output);
			if($output==''){/* no output is good */
				return true;
			}	else {
			/* we have something to log the output here*/
				return false;
			}
			/////////////////////////				FINALIZACION DEL JOBS
		});
	}

}