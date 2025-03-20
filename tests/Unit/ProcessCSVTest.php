<?php

namespace Tests\Unit;

use App\Jobs\ProcessCSV;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\TestCase;

class ProcessCSVTest extends TestCase {

    const COLUMNS = ['name', 'description', 'price'];
    const FILE = 'file';
    const TEMP_FILE = 'tests\Unit\test.csv';

    public function testParseLine_EmptyLine() {
        $result = self::getFunctionResult('parseLine', ['']);
        $this->assertEquals([], $result);
    }

    public function testParseLine_ValidLine() {
        $result = self::getFunctionResult('parseLine', ['name,description,4.99']);
        $this->assertEquals(['name', 'description', 4.99], $result);
    }

    public function testGetColumns_UnamatchingColumnsCount() {
        $result = self::getFunctionResult('getColumns', ['name,description']);
        $this->assertFalse($result);
    }

    public function testGetColumns_CorrectDataNoHeader() {
        $result = self::getFunctionResult('getColumns', ['product,product description,4.99']);
        $this->assertEquals([
            'columnNames' => self::COLUMNS,
            'hasHeader' => false
        ], $result);
    }

    public function testGetColumns_CorrectDataWithHeaderAndDefaultOrder() {
        $result = self::getFunctionResult('getColumns', ['name,description,price']);
        $this->assertEquals([
            'columnNames' => self::COLUMNS,
            'hasHeader' => true
        ], $result);
    }

    public function testGetColumns_CorrectDataWithHeaderAndDifferentOrder() {
        $result = self::getFunctionResult('getColumns', ['description,name,price']);
        $this->assertEquals([
            'columnNames' => ['description', 'name', 'price'],
            'hasHeader' => true
        ], $result);
    }

    public function testGetProductDataFromLine_IncorrectColumnsCount() {
        $lineData = ['product', 4.99];
        $filename = self::FILE;
        $lineJSON = json_encode($lineData);
        Log::shouldReceive('error')
            ->once()
            ->with("Incorrect data at row {$lineJSON} for {$filename}");
        $result = self::getFunctionResult('getProductDataFromLine', [ $lineData ]);
        $this->assertEquals([], $result);
    }

    public function testGetProductDataFromLine_EmptyLine() {
        $lineData = [];
        $filename = self::FILE;
        Log::shouldReceive('error')
            ->once()
            ->with("Incorrect data at row [] for {$filename}");
        $result = self::getFunctionResult('getProductDataFromLine', [ $lineData ]);
        $this->assertEquals([], $result);
    }

    public function testGetProductDataFromLine_CorrectColumnsCount() {
        $lineData = ['product', 'product description', 4.99];
        $result = self::getFunctionResult('getProductDataFromLine', [ $lineData ]);
        $this->assertEquals([
            'name' => $lineData[0], 
            'description' => $lineData[1], 
            'price' => $lineData[2]
        ], $result);
    }

    public function testParseCsv_UnexistingFile() {
        $filename = self::FILE;
        Storage::shouldReceive('exists')
            ->once()
            ->with(self::FILE)
            ->andReturn(false);
        Log::shouldReceive('error')
            ->once()
            ->with("File {$filename} doesn't exist");
        $result = self::getFunctionResult('parseCSV', []);
        $this->assertFalse($result);
    }

    public function testParseCsv_UnreadFile() {
        $filename = self::FILE;
        Storage::shouldReceive('exists')
            ->once()
            ->with(self::FILE)
            ->andReturn(true);
        Storage::shouldReceive('readStream')
            ->once()
            ->with(self::FILE)
            ->andReturn(null);
        Log::shouldReceive('error')
            ->once()
            ->with("Couldn't read file {$filename}");
        $result = self::getFunctionResult('parseCSV', []);
        $this->assertFalse($result);
    }

    public function testParseCsv_ValidShortFile() {
        $writer = fopen(self::TEMP_FILE, 'c+');
        $productsData = self::writeProductDataToFile($writer, 2);

        Storage::shouldReceive('exists')
            ->once()
            ->with(self::FILE)
            ->andReturn(true);
        Storage::shouldReceive('readStream')
            ->once()
            ->with(self::FILE)
            ->andReturn($writer);

        $result = self::getFunctionResult('parseCSV', []);
        $this->assertEquals($productsData, $result);
        unlink(self::TEMP_FILE);
    }

    public function testParseCsv_ValidLongFile() {
        $writer = fopen(self::TEMP_FILE, 'c+');
        $productsData = self::writeProductDataToFile($writer, 12);
        array_splice($productsData, -2);

        Storage::shouldReceive('exists')
            ->once()
            ->with(self::FILE)
            ->andReturn(true);
        Storage::shouldReceive('readStream')
            ->once()
            ->with(self::FILE)
            ->andReturn($writer);

        $result = self::getFunctionResult('parseCSV', []);
        $this->assertEquals($productsData, $result);
        unlink(self::TEMP_FILE);
    }

    private function writeProductDataToFile($writer, $productsCount) {
        $productsData = [];
        for ($i = 1; $i <= $productsCount; $i++) {
            $productData = [
                self::COLUMNS[0] => "product {$i}",
                self::COLUMNS[1] => "description {$i}",
                self::COLUMNS[2] => $i + 0.99,
            ];
            $productsData[] = $productData;
            fwrite($writer, implode(",", array_values($productData)));
            if ($i != $productsCount) {
                fwrite($writer, "\n");
            }
        }
        rewind($writer);
        return $productsData;
    }

    private function getFunctionResult($functionName, $functionArgs, $columns = self::COLUMNS) {
        $mock = $this->getMockBuilder(ProcessCSV::class)
            ->setConstructorArgs([self::FILE, 1, 'user@example.com', 0, $columns])
            ->getMock();
    
        $reflection = new \ReflectionMethod(ProcessCSV::class, $functionName);
        $reflection->setAccessible(true);

        return $reflection->invoke($mock, ...$functionArgs);
    }
}
