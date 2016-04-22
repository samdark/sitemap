<?php
namespace samdark\sitemap\tests;

use samdark\sitemap\Url;

class UrlTest extends \PHPUnit_Framework_TestCase
{
    public function testFrequencyValidation()
    {
        $this->expectException('InvalidArgumentException');

        (new Url('http://example.com/mylink2'))
                ->changeFrequency('invalid');
    }

    public function testPriorityValidation()
    {
        $this->expectException('InvalidArgumentException');

        (new Url('http://example.com/mylink2'))
            ->priority(2.0);
    }

    public function testLocationValidation()
    {
        $this->expectException('InvalidArgumentException');

        new Url('notlink');
    }

    public function testLocationLengthValidation()
    {
        $this->expectException('InvalidArgumentException');

        new Url('http://example.com/' . str_repeat('z', 2048));
    }
}
