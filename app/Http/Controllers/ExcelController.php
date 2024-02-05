<?php
namespace App\Http\Controllers;

use App\Http\Requests\ExcelImportRequest;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ExcelController extends Controller
{
    protected $excelService;

    public function __construct(ExcelService $excelService)
    {
        $this->excelService = $excelService;
    }

    public function import(ExcelImportRequest $request)
    {
        try {
            $file = $request->file('excel_file');
            $this->excelService->importFromExcel($file->getPathname(), $file->getClientOriginalName());

            $tableName = $this->getTableNameFromFile($file);
            $modelName = Str::studly(Str::singular($tableName));
            $modelClass = "App\\Models\\$modelName";

            Artisan::call('make:model', ['name' => $modelClass]);



            return back()->with('success', "File imported successfully ");
        } catch (\Exception $e) {
            Log::error('Import failed.', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error during import: ' . $e->getMessage());
        }
    }


    protected function getTableNameFromFile($file)
    {
        // Implement logic to determine table name from file name or content
        $fileName = $file->getClientOriginalName();
        $tableName = strtolower(str_replace([' ', '.xlsx', '.xls'], ['_', '', ''], $fileName));
        return $tableName;
    }


    public function export(Request $request)
    {
        $tableName = $request->input('table_name');

        try {
            $filePath = $this->excelService->exportToExcel($tableName);

            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return back()->with('error', 'Error during export: ' . $e->getMessage());
        }
    }

    public function showTables()
    {
        $tables = DB::select('SHOW TABLES');
        $tables = array_map('current', $tables);

        // Define a list of tables to exclude
        $excludeTables = [
            'migrations',
            'password_reset_tokens',
            'failed_jobs',
            'personal_access_tokens',

            // Add any other tables you want to exclude
        ];

        // Filter out the tables to exclude
        $tables = array_filter($tables, function ($tableName) use ($excludeTables) {
            return !in_array($tableName, $excludeTables);
        });

        Log::debug('Imported tables:', $tables);
        // dd($tables); // Uncomment this line for debugging purposes
        return view('tables_list', ['tables' => $tables]);
    }




    // Method to show the data in a specific table
    public function showTableData($tableName)
    {
        // Ensure the table name is safe to use in a query
        if (!Schema::hasTable($tableName)) {
            abort(404);
        }


        $data = DB::table($tableName)->get();
        $headers = Schema::getColumnListing($tableName);
        return view('table_data', ['tableName' => $tableName, 'headers' => $headers, 'data' => $data]);
    }

    public function showImportForm()
    {
        return view('import');
    }

    public function showExportForm()
    {
        return view('export');
    }
}
