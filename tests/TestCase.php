<?php


namespace SamDark\Sitemap\tests;


class TestCase extends \PHPUnit\Framework\TestCase
{
    private $tempPaths = [];

    /**
     * @param string $fileName
     * @return string
     */
    protected function getTempPath(string $fileName): string
    {
        $path = __DIR__ . '/runtime/' . $fileName;
        $this->tempPaths[] = $path;
        return $path;
    }

    protected function tearDown()
    {
        foreach ($this->tempPaths as $tempPath) {
            @unlink($tempPath);
        }

        $this->tempPaths = [];
    }

    /**
     * Asserts if file is valid XML accoring to XSD specified
     * @param string $fileName
     * @param string $xsdName
     */
    protected function assertValidXml(string $fileName, string $xsdName)
    {
        $xml = new \DOMDocument();
        $xml->load($fileName);
        $this->assertTrue($xml->schemaValidate(__DIR__ . '/xsd/' . $xsdName . '.xsd'), "$fileName is not valid accoring to $xsdName XML schema definition");
    }
}
