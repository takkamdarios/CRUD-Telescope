<?php
namespace App\Http\Controllers\ExcelCrudController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class DynamicCrudController extends Controller
{
    protected $tableName = ''; // Set the table name in the child controller

    public function __construct(Request $request)
    {
        // Make sure to only set the tableName if it's part of the route parameters
        if ($request->route()) {
            $this->tableName = $request->route('tableName');
        }
    }

    public function index($tableName)
    {
        $data = DB::table($this->tableName)->get();
        $headers = Schema::getColumnListing($this->tableName);
        return view('excelCrudController.table_data', [
            'tableName' => $this->tableName,
            'headers' => $headers,
            'data' => $data,
        ]);
    }

    public function create()
    {
        return view('excelCrudController.create', ['tableName' => $this->tableName]);
    }


    protected function getValidationRules($tableName)
    {
        $columns = Schema::getColumnListing($tableName);
        $rules = [];
        $messages = []; // Custom messages for each field.

        foreach ($columns as $column) {
            if (in_array($column, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            $columnType = Schema::getColumnType($tableName, $column);
            switch ($columnType) {
                case 'integer':
                    $rules[$column] = 'nullable|integer';
                    $messages[$column.'.integer'] = 'The :attribute must be an integer.';
                    break;
                case 'string':
                    $rules[$column] = 'nullable|string|max:255';
                    $messages[$column.'.string'] = 'The :attribute must be a string.';
                    break;
                case 'boolean':
                    $rules[$column] = 'nullable|boolean';
                    $messages[$column.'.boolean'] = 'The :attribute must be either true or false.';
                    break;
                // Define other types as needed.
                default:
                    $rules[$column] = 'nullable|string';
                    $messages[$column.'.string'] = 'The :attribute must be a valid value.';
                    break;
            }
        }

        return ['rules' => $rules, 'messages' => $messages];
    }





    public function store(Request $request, $tableName)
    {
       $validationData = $this->getValidationRules($tableName);
        $validator = Validator::make($request->all(), $validationData['rules'], $validationData['messages']);

        if (!$tableName || !Schema::hasTable($tableName)) {
            Log::error('Table not found: ' . $tableName);
            abort(404, "Table not found.");
        }

        $this->tableName = $tableName;

        // Dynamically generate validation rules based on the table structure
        $rules = $this->getValidationRules($tableName);
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            Log::error('Validation failed: ' . json_encode($validator->errors()->toArray()));
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Insert data into the specified table, excluding the token and other non-fillable fields
        $insertData = $request->except(['_token', '_method', 'id', 'created_at', 'updated_at']);
        $inserted = DB::table($this->tableName)->insert($insertData);

        if ($inserted) {
            Log::info('Data inserted successfully into table: ' . $this->tableName);
            return redirect()->route('tables.index', ['tableName' => $tableName])
                ->with('success', 'Data added successfully.');
        } else {
            Log::error('Failed to insert data into table: ' . $this->tableName);
            return redirect()->back()->with('error', 'Failed to insert data.');
        }
    }





    public function show($id)
    {
        $record = DB::table($this->tableName)->where('id', $id)->first();
        if (!$record) {
            abort(404);
        }
        return view('excelCrudController.show', ['record' => $record]);
    }

    public function edit($id)
    {
        $record = DB::table($this->tableName)->where('id', $id)->first();
        if (!$record) {
            abort(404);
        }
        return view('excelCrudController.edit', ['record' => $record, 'tableName' => $this->tableName]);
    }

    public function update(Request $request,  $tableName , $id)
    {
        $validationData = $this->getValidationRules($tableName);
        $validator = Validator::make($request->all(), $validationData['rules'], $validationData['messages']);
        if (!$this->tableName || !Schema::hasTable($this->tableName)) {
            Log::error('Table not found: ' . $this->tableName);
            abort(404, "Table not found.");
        }

        $record = DB::table($this->tableName)->where('id', $id)->first();
        if (!$record) {
            abort(404);
        }

        $rules = $this->getValidationRules($this->tableName);
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            Log::error('Validation failed: ' . json_encode($validator->errors()->toArray()));
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $updateData = $request->except(['_token', '_method', 'id', 'created_at', 'updated_at']);
        $updated = DB::table($this->tableName)->where('id', $id)->update($updateData);

        if ($updated) {
            Log::info('Data updated successfully for ID: ' . $id);
            return redirect()->route('tables.index', ['tableName' => $this->tableName])
                ->with('success', 'Data updated successfully.');
        } else {
            Log::error('Failed to update data for ID: ' . $id);
            return redirect()->back()->with('error', 'Failed to update data.');
        }
    }



    public function destroy($tableName, $id)
    {
        $this->tableName = $tableName;

        if (!Schema::hasTable($this->tableName)) {
            Log::error('Table not found: ' . $this->tableName);
            abort(404, "Table not found.");
        }

        $deleted = DB::table($this->tableName)->where('id', $id)->delete();

        if ($deleted) {
            Log::info('Data deleted successfully for ID: ' . $id);
            return redirect()->route('tables.index', ['tableName' => $this->tableName])
                ->with('success', 'Data deleted successfully.');
        } else {
            Log::error('Failed to delete data for ID: ' . $id);
            return redirect()->back()->with('error', 'Failed to delete data.');
        }
    }


}
