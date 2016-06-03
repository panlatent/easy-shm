<?php

class ShmTest extends PHPUnit_Framework_TestCase {

    public function testConstructor()
    {
        $shm = new \Panlatent\EasyShm\Shm(Panlatent\EasyShm\Shm::key(__FILE__), '100');
        $this->assertEquals(100, $shm->size());
        $shm->delete();
    }

    public function testConstructorNotKey()
    {
        $shm = new \Panlatent\EasyShm\Shm(null, '100');
        $this->assertEquals(100, $shm->size());
        $shm->delete();

        $shm = new \Panlatent\EasyShm\Shm(null, '1kb');
        $this->assertEquals(1024, $shm->size());
        $shm->delete();

        $shm = new \Panlatent\EasyShm\Shm(null, '1M');
        $this->assertEquals(1024*1024, $shm->size());
        $shm->delete();
    }
    
    public function testWriteAndRead()
    {
        $shm = new \Panlatent\EasyShm\Shm(null, 20);
        $shm->write(0, 'Hello World');
        $this->assertEquals('Hello World', $shm->read(0, 11));
        $this->assertEquals('Hello World', substr($shm->read(0), 0, 11));
        $shm->write(-5, 'Hello');
        $this->assertEquals('Hello', $shm->read(-5));
        $this->assertEquals('Hello', $shm->read(15));
        $shm->delete();
    }
}
