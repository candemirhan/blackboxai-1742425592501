<?php
require_once 'config.php';

class FtpService {
    private $conn = null;
    private $config = null;
    private $error = null;

    public function __construct() {
        $this->config = getFtpConfig();
        if (!$this->config) {
            throw new Exception('FTP configuration not found');
        }
    }

    public function connect() {
        if ($this->conn) {
            return true;
        }

        if ($this->config['secure']) {
            $this->conn = ftp_ssl_connect($this->config['host'], $this->config['port']);
        } else {
            $this->conn = ftp_connect($this->config['host'], $this->config['port']);
        }

        if (!$this->conn) {
            throw new Exception('Could not connect to FTP server');
        }

        $login = ftp_login($this->conn, $this->config['ftp_username'], $this->config['ftp_password']);
        if (!$login) {
            ftp_close($this->conn);
            throw new Exception('FTP login failed');
        }

        ftp_pasv($this->conn, true);
        return true;
    }

    public function listFiles($directory = '/') {
        try {
            $this->connect();
            $files = ftp_nlist($this->conn, $directory);
            if ($files === false) {
                throw new Exception('Could not list directory contents');
            }

            $fileList = [];
            foreach ($files as $file) {
                $basename = basename($file);
                if ($basename != '.' && $basename != '..') {
                    $size = ftp_size($this->conn, $file);
                    $modTime = ftp_mdtm($this->conn, $file);
                    
                    $fileList[] = [
                        'name' => $basename,
                        'path' => $file,
                        'size' => $size > 0 ? $size : 0,
                        'modified' => $modTime > 0 ? date('Y-m-d H:i:s', $modTime) : '',
                        'is_dir' => $size === -1
                    ];
                }
            }
            return $fileList;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function uploadFile($sourceFile, $targetPath) {
        try {
            $this->connect();
            
            if (!file_exists($sourceFile)) {
                throw new Exception('Source file does not exist');
            }

            $upload = ftp_put($this->conn, $targetPath, $sourceFile, FTP_BINARY);
            if (!$upload) {
                throw new Exception('File upload failed');
            }

            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function downloadFile($remotePath, $localPath) {
        try {
            $this->connect();
            
            if (!ftp_get($this->conn, $localPath, $remotePath, FTP_BINARY)) {
                throw new Exception('File download failed');
            }

            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function createDirectory($path) {
        try {
            $this->connect();
            
            if (!ftp_mkdir($this->conn, $path)) {
                throw new Exception('Failed to create directory');
            }

            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function deleteFile($path) {
        try {
            $this->connect();
            
            if (!ftp_delete($this->conn, $path)) {
                throw new Exception('Failed to delete file');
            }

            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function getError() {
        return $this->error;
    }

    public function __destruct() {
        if ($this->conn) {
            ftp_close($this->conn);
        }
    }
}