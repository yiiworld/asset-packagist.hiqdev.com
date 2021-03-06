<?php

namespace hiqdev\assetpackagist\helpers;

class Locker
{
    static protected $_handles = [];
    static protected $_instances = [];

    protected $_path;
    protected $_count = 0;
    protected $_handle;

    public function __construct($path)
    {
        $this->_path = $path;
    }

    static public function getInstance($path)
    {
        return isset(static::$_instances[$path]) ? static::$_instances[$path] : new static($path);
    }

    protected function getHandle()
    {
        if ($this->_handle === null) {
            if (isset(static::$_handles[$this->_path])) {
                $this->_handle = static::$_handles[$this->_path];
            } else {
                if (!file_exists($this->_path)) {
                    if (file_put_contents($this->_path, 'lock') === FALSE) {
                        throw new Exception('failed create lock file');
                    }
                }
                $this->_handle = fopen($this->_path, 'r+');
            }
        }

        return $this->_handle;
    }

    public function isLocked()
    {
        return $this->_count > 0;
    }

    public function lock()
    {
        if (!$this->isLocked()) {
            if (!flock($this->getHandle(), LOCK_EX)) {
                throw new Exception('failed get lock');
            }
        }
        ++$this->_count;
    }

    public function release()
    {
        if ($this->_count<1) {
            throw new Exception('no lock to release');
        }
        if ($this->_count === 1) {
            if (!flock($this->getHandle(), LOCK_UN)) {
                throw new Exception('failed release lock');
            }
        }
        --$this->_count;
    }

}
