<?php

namespace LaravelMysqlBackup;

use Illuminate\Console\Command;

class LaravelMysqlBackupCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql:backup {mode} {options?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Mysql Database To The Single File (.sql)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*
        @mode = backup [table1,table2]
        */
        
        if ($this->argument('mode') == 'backup') {

            $init_tables=false;
            if($this->argument('options') != null){
                $init_tables = explode(',',str_replace(' ','',trim($this->argument('options'),',')));
            }
            $this->backupDatabase($init_tables);
        } else {

            $this->printMessages(
                [
                    "php artisan mysql:backup \t 'This command ...'",
                    "php artisan mysql:backup table1,table2,table3,.... \t 'This command ...'",
                    "php artisan mysql:backup help \t 'see help'"
                ]
            );

        }
    }

    protected function backupDatabase($tables){
        $start_backup_time =  microtime(true);

        $db_name = env('DB_DATABASE');

        $date_time = date('Y/m/d H:i:s');

        $db_export_file = base_path().'/storage/LaravelMysqlBackup_'.@time().'.sql';


        #get Tables
        if(!$tables){
            $tables = $this->getTables($db_name);
        }
        

        #print in file
        file_put_contents(

            $db_export_file,

            $this->printMessages(
                [
                    "-- Backup With Laravel Mysql Backup --",
                    "-- https://github.com/saeedvir/laravel-mysql-backup --",
                    "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;",
                    "/*!40101 SET NAMES utf8 */;",
                    "/*!50503 SET NAMES utf8mb4 */;",
                    "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;",
                    "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;"
                ],
                true
            )
        );

        #export Table Data
        $this->exportTable($tables);

        #print in file
        file_put_contents(
            $db_export_file,
            $this->printMessages(
                [
                    "/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;",
                    "/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;",
                    "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;"
                ]
                ,true),
            FILE_APPEND
        ); 


        $this->printMessages(
            [
                'backup done.',
                microtime(true) - $start_backup_time
            ]
        );
    }

    protected function getTables($db_name){
        $db_data = \DB::select(DB::raw("SHOW TABLE STATUS FROM `$db_name`;"));
        $db_data = json_decode(json_encode($db_data), true);

        $tables = [];
        if(count($db_data)>0){
            foreach ($db_data as $item){
                $tables[] = $item['Name'];
            }
        }
        unset($db_data);

        return $tables;

    }

    protected function exportTable($tables=[]){

        global $db_export_file;

        foreach ($tables as $table) {

            //Show Create Table
            $db_data = \DB::select(DB::raw("SHOW CREATE TABLE `$db_name`.`$table`;"));
            $db_data = json_decode(json_encode($db_data[0]), true);
            file_put_contents(
                $db_export_file,
                $this->printMessages(
                    replaceCreateQuery($db_data['Create Table']).';',true
                ),
                 FILE_APPEND
            );
            unset($db_data);

            //TRUNCATE Table
            file_put_contents(
                $db_export_file,
                $this->printMessages(
                    [
                        "DELETE FROM `$table`;",
                        "/*!40000 ALTER TABLE `$table` DISABLE KEYS */;"."\r\n"
                    ]
                    ), 
                    FILE_APPEND
                
            );

            //get Data From Table
            $db_data = DB::table($table)->first();
            
            $db_data = json_decode(json_encode($db_data), true);

            $tbl_cols = [];
            if(count($db_data)>0){
                $tbl_cols = array_keys($db_data);

                $column_str="INSERT IGNORE INTO `'.$table.'`  (";
                foreach ($tbl_cols as $col_name) {
                    $column_str .='`'.$col_name.'`,';
                }

                $column_str=trim($column_str,',').' ) VALUES '."\r\n";

                file_put_contents(
                    $db_export_file,
                    $this->printMessages($column_str,true),
                    FILE_APPEND
                );

                unset($db_data,$column_str);


                $db_data = \DB::select(DB::raw("SELECT * FROM `$db_name`.`$table`;"));
                $db_data = json_decode(json_encode($db_data), true);

                $interval_row=0;
                foreach ($db_data as $key => $item){

                    $row_insert='(';
                   
                        foreach ($item as  $value) {
    
                                if (!is_numeric($value)) {
                                    $row_insert .='\''.$value.'\',';
                                }else{
                                    $row_insert .=$value.',';
                                }
    
                                
                        }
    
                        if ($interval_row+1==count($db_data)) {
                            $row_insert =trim($row_insert,',').');';
    
                            file_put_contents(
                                $db_export_file,
                                $this->printMessages($row_insert,true),
                                FILE_APPEND
                            );
                                
                        }else{
                            $row_insert =trim($row_insert,',').'),';

                            file_put_contents(
                                $db_export_file,
                                $this->printMessages($row_insert,true),
                                FILE_APPEND
                            );                                
    
                        }
    
                        $interval_row++;

                }
                unset($db_data,$interval_row,$row_insert);


            }
            file_put_contents(
                $db_export_file,
                $this->printMessages('/*!40000 ALTER TABLE `'.$table.'` ENABLE KEYS */;',true) , 
                FILE_APPEND
            ); 
            

            file_put_contents(
                $db_export_file,
                $this->printMessages('REPAIR TABLE `'.$table.'`;OPTIMIZE TABLE `'.$table.'`;',true), 
                FILE_APPEND
            );   

        }//Foreach Tables
    }

    protected function replaceCreateQuery($str=''){
        return str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $this->replaceQuat($str));
    }
    protected function replaceQuat($inpt){
        return str_replace('"','`', $inpt);
    }

    protected function printMessages($messages,$ret=false)
    {
        $ret_data = null ;
        if (is_array($messages)) {
           
            $ret_data = "\r\n";

            if(!$ret){
                echo $ret_data;
            }
            
            foreach ($messages as $message) {

                $ret_data .= $message. "\r\n";
                if(!$ret){
                    echo $message . "\r\n";
                }
                
            }
        } else {
            $ret_data="\r\n" . $messages . "\r\n";
            if(!$ret){
                echo $ret_data;
            }
           
        }

        return ($ret == true) ?  $ret_data : null;
    }
}