<?php
namespace App\Utility;

class Ftp
{
    private $ip;
    private $port;
    private $username;
    private $password;
    public $conn;

    public  $dirList = [];

    function __construct(array $server = []  )
    {
        $this->ip = $server['ip'];
        $this->port = $server['port'];
        $this->username = $server['username'];
        $this->password = $server['password'];
    }

    public function test_connection(){
        $ftpConn = @ftp_connect($this->ip,$this->port,5000);
        $login = @ftp_login($ftpConn,$this->username,$this->password);
        if ((!@$ftpConn) || (!@$login)) {
            return false;
        } else{
            @ftp_pasv($ftpConn, true);

            $this->conn = $ftpConn;
           return true;
        }
    }

    public function SaveFile($filname){
        if (@ftp_get($this->conn, public_path('backups/')."$filname", "/"."$filname", FTP_BINARY, 0)) {

            @ftp_close($this->conn);
            return url("/backups/$filname");
        }
        else {
            @ftp_close($this->conn);
            return false;
        }

    }

    public function getDirectoryList($dir){
        $file_list = @ftp_nlist($this->conn, $dir);

        $this->dirList = $file_list;
        return $file_list;
    }

    public function create_dir($dir){
        return @ftp_mkdir( $this->conn,$dir );
    }

    public function exit_create($dir){
        if (@ftp_nlist($this->conn, $dir) === false) {
            @ftp_mkdir($this->conn, $dir);
        }
    }

    public function uploadFileToBackUp($file_name,$ip = "no"){
        $save_dir = "/public_html/";
        $backupdir = $this->exit_create($save_dir.'backups');
        $save_dir = "/public_html/backups/";
        $this->exit_create($save_dir.str_replace('.','_',$ip));
        $save_dir .= str_replace('.','_',$ip)."/";
        $this->exit_create($save_dir.date('Y_m_d'));
        $save_dir .= date('Y_m_d')."/";


        if(@ftp_put($this->conn, $save_dir.$file_name, public_path("/backups/".$file_name), FTP_BINARY,0)){

            unlink(public_path("/backups/".$file_name));
            return true;
        }

        return false;
    }
}
