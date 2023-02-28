<?
  if(!function_exists("ssh2_connect")) die('!!! Function *ssh2_connect* not found, need *php_ssh2.dll*');

  $host = 'myserv';
  $port = 22;
  $user = 'user';
  $pass = 'pass';

  if(!$conn = ssh2_connect($host, $port)) die('Unable to connect');
  if(!ssh2_auth_password($conn, $user, $pass)) die('Unable to authenticate.');
  if(!$sftp = ssh2_sftp($conn)) die('Unable to open sftp.');

  downloadAll('/usr/home/share/download/', 'C:/1/download/');
  uploadAll('C:/1/upload/',  '/usr/home/share/upload/');

  #
  function uploadAll($localDir,$remoteDir)
    {
     global $conn, $sftp;

     echo "uploadAll: $localDir -> $remoteDir\n";

     @ssh2_sftp_mkdir($sftp, $remoteDir, 0777,true);
     if(!$dir = opendir($localDir)) die("Could not open the directory $localDir");
     $files=array();
     while(false !== ($file = readdir($dir)))
       {
        if(is_file("{$localDir}{$file}")) $files[] = $file;
       }
     if(count($files)>0)
       foreach($files as $file)
         {
          $info=stat("{$localDir}{$file}");
          echo "reMoving fileUp: $file (size: $info[size])\n";
          if(!ssh2_scp_send($conn, $localDir.$file, $remoteDir.$file)) echo "Could not upload: ${localDir}${file}\n";
          if(!unlink($localDir.$file)) echo "Could not unlink: ${localDir}${file}\n";
         }
     else
       echo "localDir $localDir is empty\n";
     echo "\n";
     return true;
    }

  #
  function downloadAll($remoteDir,$localDir)
    {
     global $conn, $sftp;

     echo "downloadAll: $remoteDir -> $localDir\n";

     @mkdir($localDir,0777,true);
     if(!$dir = opendir("ssh2.sftp://{$sftp}{$remoteDir}")) die('Could not open the directory');
     $files=array();
     while(false !== ($file = readdir($dir)))
       {
        if(is_file("ssh2.sftp://{$sftp}/{$remoteDir}{$file}")) $files[] = $file;
       }
     if(count($files)>0)
       foreach($files as $file)
         {
          $info=ssh2_sftp_stat($sftp, $remoteDir.$file);
          echo "reMoving fileDown: $file (size: $info[size] $info[mtime])\n";
          if(!ssh2_scp_recv($conn, $remoteDir.$file, $localDir.$file)) echo "Could not download: ${remoteDir}${file}\n";
          if(!ssh2_sftp_unlink($sftp, $remoteDir.$file)) echo "Could not unlink: ${remoteDir}${file}\n";
          touch($localDir.$file, $info['mtime'], $info['atime']);
         }
     else
       echo "remoteDir $remoteDir is empty\n";
     echo "\n";
     return true;
    }
?>