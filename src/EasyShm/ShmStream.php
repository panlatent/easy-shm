<?php
/**
 * easy-shm
 */

namespace Panlatent\EasyShm;


class ShmStream {

    /**
     * @var \Panlatent\EasyShm\Shm
     */
    protected $shm;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var int
     */
    protected $shmSize;

    /**
     * @var int
     */
    protected $shmMode;

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @var bool
     */
    protected $readLock = false;

    /**
     * @var bool
     */
    protected $writeLock = false;

    public static function register($protocol = 'shm')
    {
        return stream_wrapper_register($protocol, get_called_class());
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        if (!$this->parsePathUrl($path)) {
            throw new Exception('Failed to open the EasyShm stream: unable to resolve path.');
        }
        $key = $this->path ? ftok($this->path, 'n') : null;
        if ('127.0.0.1' != gethostbyname($this->host)) {
            throw new Exception('Failed to open the EasyShm stream: support local access only.');
        }

        switch ($mode) {
            case 'r':
            case 'rb':
                $flag = Shm::READ;
                $this->shmSize = 0;
                break;
            case 'r+':
            case 'rb+':
                $flag = Shm::READ_WRITE;
                $this->shmSize = 0;
                break;
            case 'w':
            case 'wb':
                $flag = Shm::CREATE_READ_WRITE;
                $this->readLock = true;
                break;
            case 'w+':
            case 'wb+':
                $flag = Shm::CREATE_READ_WRITE;
                break;
            case 'a':
            case 'ab':
                $flag = Shm::CREATE_READ_WRITE;
                $this->readLock = true;
                $this->position = $this->shmSize;
                break;
            case 'a+':
            case 'ab+':
                $flag = Shm::CREATE_READ_WRITE;
                $this->position = $this->shmSize;
                break;
            default:
                throw new Exception("Failed to open the EasyShm stream: the wrong open mode \"{$mode}\".");
        }

        if (false === $this->shmSize) {
            throw new Exception("Failed to open the EasyShm stream: no size parameter.");
        }

        $this->shm = new Shm($key, $this->shmSize, $this->shmMode, $flag);
        
        return true;
    }
    
    public function stream_close()
    {
        return $this->shm->delete();
    }

    public function stream_read($count)
    {
        if ($this->readLock) {
            throw new Exception('Failed to read to EasyShm stream: the wrong open mode.');
        }

        if ($count > $this->shmSize) { // System default $count = 8129
            $count = $this->shmSize - $this->position;
        }

        $ret = $this->shm->read($this->position, $count);
        $this->position += $count;

        return $ret;
    }

    public function stream_write($data)
    {
        if ($this->writeLock) {
            throw new Exception('Failed to write to EasyShm stream: the wrong open mode.');
        }

        $ref = $this->shm->write($this->position, $data);
        $ref and $this->position += $ref;

        return $ref;
    }

    public function stream_eof()
    {
        return $this->position >= $this->shm->size();
    }

    public function stream_tell()
    {
        return $this->position;
    }

    public function stream_seek($offset, $whence)
    {
        switch ($whence) {
            case SEEK_SET:
                break;
            case SEEK_CUR:
                $offset += $this->position;
                break;
            case SEEK_END:
                $offset += $this->shm->size();
                break;
            default:
                return false;
        }

        $ret = ($offset >= 0 && $offset <= $this->shm->size());
        if ($ret) {
            $this->position = $offset;
        }

        return $ret;
    }

    protected function parsePathUrl($path)
    {
        if (!($url = parse_url($path))) {
            return false;
        }

        $this->host = isset($url['host']) ? $url['host'] : '127.0.0.1';
        $this->path = isset($url['path']) ? $url['path'] : false;
        $this->shmSize = $this->getPathQueryParams($url, 'size');
        $this->shmMode = $this->getPathQueryParams($url, 'mode', 0664);

        return true;
    }

    protected function getPathQueryParams($url, $param, $default = false)
    {
        if (!isset($url['query']))
            return $default;

        parse_str($url['query'], $params);

        return isset($params[$param]) ? $params[$param] : $default;
    }
    
}