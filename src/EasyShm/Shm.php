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

    protected $shmActualSize;

    protected $shmDataAreaOffset;

    protected $tempFilename;

    public function __construct($key = null, $size = 0, $mode = 0644, $flag = self::CREATE_READ_WRITE)
    {
        $this->shmSize = $this->suffixSizeToByteLength($size);
        $this->shmMode = $mode;
        $this->shmFlag = $flag;

        if (null === $key) {
            $this->tempFilename = tempnam(sys_get_temp_dir(), '');
            $key = static::key($this->tempFilename);
        }

        $this->shmDataAreaOffset = $this->memoryStructureSize();
        $this->shmActualSize = $this->shmSize + $this->shmDataAreaOffset;
        $this->shmID = shmop_open($key, $this->shmFlag, $this->shmMode, $this->shmActualSize);
        if (false === $this->shmID) {
            throw new Exception('Shard Memory open fail');
        }

        $this->memoryStructureInit();
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
        return $this->shmSize;
    }

    public function actualSize()
    {
        return $this->shmActualSize;
    }

    public function read($start, $length = null)
    {
        $offset = $start >= 0 ? $this->shmDataAreaOffset + $start : $this->shmActualSize + $start;

        if (null === $length) {
            $length = $start >= 0 ? $this->shmSize - $start : $this->shmActualSize - $offset;
        }

        return shmop_read($this->shmID, $offset, $length);
    }

    public function write($start, $data)
    {
        $offset = $start >= 0 ? $this->shmDataAreaOffset + $start : $this->shmActualSize + $start;

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

    protected function memoryStructureInit()
    {
        if ($this->shmFlag == self::READ || $this->shmFlag == self::READ_WRITE) {
            $result = unpack('L2', shmop_read($this->shmID, 0, $this->shmDataAreaOffset));
            $this->shmSize = $result[0];
            $this->shmActualSize = $result[0] + $this->memoryStructureSize();
            $this->shmDataAreaOffset = $result[1];
        } else {
            $binaryHeader = pack('L2', $this->shmSize, $this->shmDataAreaOffset);
            shmop_write($this->shmID, $binaryHeader, 0);
        }
    }

    protected function memoryStructureSize()
    {
        return array_sum([
            'size'   => 2,
            'offset' => 2,
        ]);
    }

    protected function suffixSizeToByteLength($size)
    {
        if (is_numeric($size)) {
            return $size;
        } elseif ( ! preg_match('/^(\d+)([a-zA-Z]+)$/', $size, $match)) {
            throw new Exception('Wrong memory size parameter');
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