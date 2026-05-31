<?php
$mdb='C:\_Inforemp\_dev_ptp\Produccion PTP_w2.mdb';
try{
 $app=new COM('Access.Application');
 $app->OpenCurrentDatabase($mdb);
 $p=$app->CurrentProject;
 $reps=$p->AllReports; $r=[];
 for($i=0;$i<$reps->Count;$i++){ $r[]=(string)$reps->Item($i)->Name; }
 sort($r);
 echo "== REPORTS (".count($r).") ==\n".implode("\n",$r)."\n";
 $app->CloseCurrentDatabase();
 $app->Quit();
}catch(Exception $e){ echo "ERR: ".$e->getMessage()."\n"; }
