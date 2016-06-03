<?php
/**
 * EasyShm
 *
 * @author  panlatent@gmail.com
 * @link    https://github.com/panlatent/easy-shm
 * @license https://opensource.org/licenses/MIT
 */

namespace Panlatent\EasyShm;

class Shm {

    const READ = 'a';
    const READ_WRITE = 'w';
    const CREATE_READ_WRITE = 'c';
    const NEW_FAIL = 'n';

    protected $shmID;

    protected $shmFlag;

    protected $shmMode;

    protected $shmSize;

    protected $tempFilename;

    public function __construct($key = null, $size = 0, $mode = 0660, $flag = self::CREATE_READ_WRITE)
    {
        $this->shmMode = $mode;
        $this->shmFlag = $flag;

        if (null === $key) {
            $this->tempFilename = tempnam(sys_get_temp_dir(), '');
            $key = static::key($this->tempFilename);
        }

        $this->shmSize = $this->suffixSizeToByteLength($size);
        $this->shmID = shmop_open($key, $this->shmFlag, $this->shmMode, $this->shmSize);
        if (false === $this->shmID) {
            throw new Exception('Shard Memory open fail');
        }
    }

    public function __destruct()
    {
        if ($this->tempFilename) {
            unlink($this->tempFilename);
        }

        shmop_close($this->shmID);
    }

    public function size()
    {
        if (null === $this->shmSize) {
            $this->shmSize = shmop_size($this->shmID);
        }

        return $this->shmSize;
    }

    public function read($start, $length = null)
    {
        $offset = $start >= 0 ? $start : $this->shmSize + $start;

        if (null === $length) {
            $length = $start >= 0 ? $this->shmSize - $start : -($start);
        }

        return shmop_read($this->shmID, $offset, $length);
    }

    public function write($start, $data)
    {
        $offset = $start >= 0 ? $start : $this->shmSize + $start;

        return shmop_write($this->shmID, $data, $offset);
    }

    public function delete()
    {
        return shmop_delete($this->shmID);
    }

    public static function key($filePath)
    {
        return ftok($filePath, 'n');
    }

    protected function suffixSizeToByteLength($size)
    {
        if (is_numeric($size)) {
            return $size;
        } elseif (!preg_match('/^(\d+)([a-zA-Z]+)$/', $size, $match)) {
            throw new Exception("Wrong memory size parameter \"{$size}\"");
        }

        $size = $match[1];
        $unit = $match[2];

        switch (strtoupper($unit)) {
            case 'B':
                break;
            case 'K':
            case 'KB':
                $size *= 1024;
                break;
            case 'M':
            case 'MB':
                $size *= 1024 * 1024;
                break;
            case 'G':
            case 'GB':
                $size *= 1024 * 1024 * 1024;
                break;
            default:
                throw new Exception("Undefined Memory size unit: $unit");
        }

        return $size;
    }


}