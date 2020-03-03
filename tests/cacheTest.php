<?php

use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    public function Cache()
    {
        $lru = new Cache();
        return $lru;
    }
   
    public function testStartsEmpty()
    {
        $this->Cache()->init();
        $this->assertNull($this->Cache()->get('key20'));
    }

    public function testSetandGet()
    {
        $key = 'key1';
        $value = 'Lorem Ipsum1';
        $key2 = 'key2';
        $value2 = 'Lorem Something else';
        $res = $this->Cache()->set($key, $value);
        $this->assertTrue($res);
        $res = $this->Cache()->set($key2, $value2);
        $this->assertTrue($res);
        $this->assertEquals($this->Cache()->get($key), $value);
        $this->assertEquals($this->Cache()->get($key2), $value2);
    }
    public function testSetMoreThanTen()
    {
        for ($n=1; $n < 21; $n++) {
            $key = 'key'.$n;
            $value = 'Lorem Ipsum' . $n;
            $res = $this->Cache()->set($key, $value);
            $this->assertTrue($res);
        }
    }

    public function testGetShouldBeEvicted()
    {
        $key = 'key1';
        $value = 'Lorem Ipsum1';

        $this->assertNull($this->Cache()->get($key));
    }

    public function testGetShouldNotBeEvicted()
    {
        $key = 'key20';
        $value = 'Lorem Ipsum20';
        
        $this->assertEquals($this->Cache()->get($key), $value);
    }

    public function testUpdate()
    {
        $key = 'key20';
        $value = 'Scooby doo';
        $res = $this->Cache()->set($key, $value);
        $this->assertTrue($res);
        $this->assertEquals($this->Cache()->get($key), $value);
    }
}
