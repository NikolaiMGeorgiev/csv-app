<?php

namespace App\Jobs;

use App\Mail\ProcessingResult;
use App\Models\Products;
use App\Models\Uploads;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ProcessCSV implements ShouldQueue
{
    use Queueable, Batchable;

    public $filename;
    public $userId;
    public $userEmail;
    public $byteStart;
    public $byteEnd;
    public $maxLinesRead;
    public $fileIsFullyRead;
    public $columns;

    /**
     * Create a new job instance.
     */
    public function __construct($filename, $userId, $userEmail, $byteStart, $columns = null) {
        $this->filename = $filename;
        $this->userId = $userId;
        $this->userEmail = $userEmail;
        $this->byteStart = $byteStart;
        $this->maxLinesRead = 10;
        $this->fileIsFullyRead = false;
        $this->columns = $columns;
    }

    /**
     * Execute the job.
     */
    public function handle(): void {
        $uploadsDBRow = Uploads::where('users_id', $this->userId)
            ->where('file_path', $this->filename);
        $uploadsDBRow->update([
            'status' => 'processing',
            'updated_at' => now()
        ]);

        $productsData = self::parseCSV();

        if (!$productsData) {
            $uploadsDBRow->update([
                'status' => 'failed',
                'updated_at' => now()
            ]);
            Mail::to($this->userEmail)->queue(new ProcessingResult('failed', $this->filename, $this->userId));
            return;
        }

        foreach ($productsData as $productData) {
            $dbProductData = [
                'name' => $productData['name'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'users_id' => $this->userId
            ];
            $product = new Products($dbProductData);
            if(!$product->save()) {
                $jsonProductData = json_encode($dbProductData);
                Log::error("Failed to save {$jsonProductData} from file {$this->filename}");
            }
        }

        if ($this->fileIsFullyRead) {
            $uploadsDBRow
                ->update([
                    'status' => 'completed',
                    'updated_at' => now()
                ]);
            Storage::delete($this->filename);
            Mail::to($this->userEmail)->queue(new ProcessingResult('completed', $this->filename, $this->userId));
        } else {
            Bus::dispatch(new ProcessCSV($this->filename, $this->userId, $this->userEmail, $this->byteEnd, $this->columns));
        }
    }

    /**
     * Execute all actions needed to retrieve the data from the CSV
     * @return array|false the data from the CSV
     */
    protected function parseCSV() {
        if (!Storage::exists($this->filename)) {
            Log::error("File {$this->filename} doesn't exist");
            return false;
        }
        
        $reader = Storage::readStream($this->filename);
        
        if (empty($reader)) {
            Log::error("Couldn't read file {$this->filename}");
            return false;
        }

        fseek($reader, $this->byteStart);
        $products = [];

        if (empty($this->columns)) {
            $firstLine = fgets($reader);
            $columnsData = self::getColumns($firstLine);
    
            if (!$columnsData) {
                fclose($reader);
                Log::error("Incorrect column data for file {$this->filename}");
                return false;
            }
    
            $this->columns = $columnsData['columnNames'];
            if (!$columnsData['hasHeader']) {
                $firstLineData = self::parseLine($firstLine);
                $products[] = self::getProductDataFromLine($firstLineData);
            }
        }
    

        $linesRead = 0;
        while (!feof($reader) && $linesRead < $this->maxLinesRead) {
            $line = fgets($reader);
            $lineData = self::parseLine($line);
            $productData = self::getProductDataFromLine($lineData);
            if (!empty($productData)) {
                $products[] = $productData;
            }
            $linesRead++;
        }

        $this->byteEnd = ftell($reader);
        if (feof($reader)) {
            $this->fileIsFullyRead = true;
        }

        fclose($reader);
        return $products;
    }

    /**
     * Extract the data from a single line
     * @param string $line a line from the file
     * @return array the data from the line
     */
    protected function parseLine($line) {
        if (!empty($line)) {
            $productData = explode(",", trim($line));
            if (!empty($productData)) {
                return $productData;
            }
        }
        return [];
    }

    /**
     * Get the column from the file header or the default order if no header is present
     * @param string $firstLine the first line of the file
     * @return array|false the column names in order
     */
    protected function getColumns($firstLine) {
        $columnNames = ['name', 'description', 'price'];
        $columnNamesCopy = array_slice($columnNames, 0);
        $lineData = self::parseLine($firstLine);
        $lineDataCopy = array_slice($lineData, 0);
        $hasHeader = false;

        if (count($columnNames) != count($lineData)) {
            return false;
        }

        sort($columnNamesCopy);
        sort($lineDataCopy);

        # The first line is a header so it's used for determining the column order
        if (implode(",", $lineDataCopy) == implode(",", $columnNamesCopy)) {
            $columnNames = $lineData;
            $hasHeader = true;
        }
        
        return [
            'columnNames' => $columnNames,
            'hasHeader' => $hasHeader
        ];
    }

    /**
     * Extracts the data for each product field from the data in a single line
     * @param array $lineData the data from a line
     * @return array the product data
     */
    protected function getProductDataFromLine($lineData) {
        $productData = [];

        if (count($lineData) != count($this->columns)) {
            $lineJSON = json_encode($lineData);
            Log::error("Incorrect data at row {$lineJSON} for {$this->filename}");
            return [];
        }

        for ($i = 0; $i < count($this->columns); $i++) {
            $column = $this->columns[$i];
            $data = $lineData[$i];
            $productData[$column] = $data;
        }
        
        return $productData;
    }
}
