<?php
namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\BufferedOutput;

class ExcelService
{
    public function importFromExcel($filePath, $originalFileName)
    {
        try {
            Log::info("Starting the import process for Excel file.", ['filePath' => $filePath]);

            $reader = new Xlsx();
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Extract headers
            $headerRow = $worksheet->getRowIterator(1, 1)->current();
            $headers = [];
            foreach ($headerRow->getCellIterator() as $cell) {
                $header = $cell->getValue();
                $header = $header === 'id' ? 'data_id' : $header;
                $headers[] = $header;
            }

            // Sanitize headers
            $headers = $this->sanitizeColumnNames($headers);

            // Infer data types
            $dataTypes = $this->inferDataTypes($worksheet, $headers);

            // Generate table name
            $tableName = $this->sanitizeTableName($originalFileName);

            // Create migration with the inferred data types
            $migrationFileName = $this->createMigration($tableName, $headers, $dataTypes);

            // Execute the new migration
            Artisan::call('migrate', ['--path' => 'database/migrations/' . $migrationFileName]);
            Log::info("Migration executed for table: " . $tableName);

            // Generate CRUD controller
            $this->generateCrudController($tableName);

            // Insert data into the new table
            $insertData = $this->prepareInsertData($worksheet, $headers, $dataTypes);
            DB::table($tableName)->insert($insertData);
            Log::info("Excel file imported successfully into table: " . $tableName);

        } catch (\Exception $e) {
            Log::error("Error importing Excel file: " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }




    private function prepareInsertData($worksheet, $headers, $dataTypes)
    {
        $insertData = [];
        foreach ($worksheet->getRowIterator(2) as $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true); // Change to true to only loop set cells

            foreach ($cellIterator as $cell) {
                $columnIndex = Coordinate::columnIndexFromString($cell->getColumn()) - 1;
                if (isset($headers[$columnIndex])) { // Check if the header is set for the cell
                    $header = $headers[$columnIndex];
                    $value = $cell->getValue();
                    if ($value !== null && $value !== '') { // Check if the cell is not empty
                        $value = $this->convertToDataType($value, $dataTypes[$header]);
                        $rowData[$header] = $value;
                    }
                }
            }

            if (!empty($rowData)) {
                // Fill missing headers with null instead of 0
                foreach ($headers as $header) {
                    if (!isset($rowData[$header])) {
                        $rowData[$header] = null;
                    }
                }
                $insertData[] = $rowData;
            }
        }
        return $insertData;
    }


    /**
     * Convert a value to a specific data type.
     *
     * @param mixed $value The value to convert.
     * @param string $dataType The data type to convert to.
     * @return mixed The converted value.
     */
    private function convertToDataType($value, $dataType)
    {
        switch ($dataType) {
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'boolean':
                // Explicitly handle boolean conversion based on expected Excel values
                return $this->convertToBoolean($value);
            case 'string':
            default:
                return (string)$value;
        }
    }

    /**
     * Helper method to convert a value to a boolean.
     *
     * @param mixed $value The value to convert.
     * @return bool The converted boolean value.
     */
    private function convertToBoolean($value)
    {
        $trueValues = ['true', 't', '1', 1];
        $falseValues = ['false', 'f', '0', 0];

        $stringValue = strtolower((string)$value);

        if (in_array($stringValue, $trueValues, true)) {
            return true;
        } elseif (in_array($stringValue, $falseValues, true)) {
            return false;
        }

        // If the value doesn't match any known boolean representations, default to false
        return false;
    }




    private function generateCrudController($tableName)
    {
        $className = Str::studly($tableName) . 'Controller';
        $stubPath = app_path('Http/Controllers/ExcelCrudController/crud_controller.stub');
        $controllerPath = app_path("Http/Controllers/ExcelCrudController/{$className}.php");

        if (!file_exists($stubPath)) {
            Log::error("CRUD controller stub not found.");
            return;
        }

        $stub = file_get_contents($stubPath);

        // Replace placeholders in the stub
        $controllerContent = str_replace(['{{className}}', '{{tableName}}'], [$className, $tableName], $stub);

        // Check if the directory exists and create it if not
        $directory = dirname($controllerPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        // Write the new controller file
        file_put_contents($controllerPath, $controllerContent);

        Log::info("CRUD controller generated: {$className}");
    }




    public function sanitizeTableName($originalFileName)
    {
        $datePrefix = date('Ymd'); // Date of creation
        $randomString = Str::random(6); // 6 random characters
        $cleanFileName = Str::snake(pathinfo($originalFileName, PATHINFO_FILENAME));

        // Concatenate to form the new table name
        return  $cleanFileName;
    }


    private function sanitizeColumnNames($headers)
    {
        return array_map(function ($header) {
            // Replace spaces with underscores, remove special characters
            return preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $header));
        }, $headers);
    }


    public function exportToExcel($tableName)
    {
        if (!Schema::hasTable($tableName)) {
            Log::error("Attempt to export non-existing table.", ['tableName' => $tableName]);
            throw new \Exception("Table does not exist.");
        }

        $data = DB::table($tableName)->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $columns = Schema::getColumnListing($tableName);
        $sheet->fromArray($columns, NULL, 'A1');

        $rowIndex = 2;
        foreach ($data as $rowData) {
            $sheet->fromArray((array)$rowData, NULL, 'A' . $rowIndex);
            $rowIndex++;
        }

        $writer = new WriterXlsx($spreadsheet);
        $fileName = $tableName . '.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);

        Log::info("Excel file exported successfully.", ['tableName' => $tableName, 'fileName' => $fileName]);

        return $temp_file;
    }


    private function inferDataTypes($worksheet, $headers)
    {
        $dataTypes = array_fill_keys($headers, 'string'); // Default to string
        $rowsToCheck = 10; // Define how many rows you want to check to infer data type

        foreach ($worksheet->getRowIterator(2, $rowsToCheck + 1) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set

            foreach ($cellIterator as $cell) {
                $cellValue = $cell->getValue();
                $index = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn()) - 1;
                $header = $headers[$index];

                if (filter_var($cellValue, FILTER_VALIDATE_INT) !== false) {
                    $dataTypes[$header] = 'integer';
                } elseif (filter_var($cellValue, FILTER_VALIDATE_FLOAT) !== false) {
                    $dataTypes[$header] = 'float';
                } elseif ($this->isBooleanValue($cellValue)) {
                    // If a column previously marked as integer or float contains a boolean value,
                    // we mark it as boolean
                    $dataTypes[$header] = 'boolean';
                } elseif ($dataTypes[$header] !== 'integer' && $dataTypes[$header] !== 'float' && $dataTypes[$header] !== 'boolean') {
                    // If it's not already set as a numeric type or boolean, keep it as a string
                    $dataTypes[$header] = 'string';
                }
            }
        }

        return $dataTypes;
    }

    /**
     * Helper method to determine if a value is a boolean-like value.
     *
     * @param mixed $value The value to check.
     * @return bool Whether the value represents a boolean.
     */
    private function isBooleanValue($value)
    {
        $booleanValues = ['true', 'false', 't', 'f', '1', '0', 1, 0, true, false];
        return in_array(strtolower((string)$value), $booleanValues, true);
    }




    public function createMigration($tableName, $headers , $dataTypes)
    {
        $randomString = Str::random(6); // Generate a 6-character random string
        //Generate 6 digit random number
        $randomNumber = rand(100000, 999999);
        $className = 'Create' . Str::studly($tableName) . 'Table';
        $fileName = date('Y_m_d_His') . '_create_' . $tableName . '_table.php'; // Updated format
        $filePath = database_path('migrations/' . $fileName);

        $content = "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nclass $className extends Migration\n{\n    public function up()\n    {\n        Schema::create('$tableName', function (Blueprint \$table) {\n            \$table->id();\n";

        foreach ($headers as $header) {
            // Use the inferred data type to determine the correct column type
            switch ($dataTypes[$header]) {
                case 'integer':
                    $content .= "            \$table->integer('$header')->nullable();\n";
                    break;
                case 'float':
                    $content .= "            \$table->float('$header')->nullable();\n";
                    break;
                case 'boolean':
                    $content .= "            \$table->boolean('$header')->nullable();\n";
                    break;
                case 'string':
                default:
                    $content .= "            \$table->string('$header')->nullable();\n";
                    break;
            }
        }

        $content .= "            \$table->timestamps();\n        });\n    }\n\n    public function down()\n    {\n        Schema::dropIfExists('$tableName');\n    }\n}\n";

        file_put_contents($filePath, $content);
        return $fileName;
    }



}

