<?php

class ShmStreamTest extends PHPUnit_Framework_TestCase {

    public function setUp()
    {
        parent::setUp();
        $this->assertTrue(\Panlatent\EasyShm\ShmStream::register());
    }

    public function testWriteAndRead()
    {
        $fd = fopen('shm://localhost?size=10', 'w+');
        $this->assertEquals(5, fwrite($fd, 'Hello'));
        $this->assertEquals(5, fwrite($fd, 'World'));
        $this->assertEquals(0, fwrite($fd, 'World'));

        $this->assertEquals(0, fseek($fd, 0));
        $this->assertEquals(0, ftell($fd));
        $this->assertEquals('HelloWorld', fread($fd, 10));
    }

}
