<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class ExcelImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function testImportExcelFile()
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('test.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->post(route('import'), [
            'excel_file' => $file,
        ]);

        $response->assertStatus(200);
        // Add more assertions here to verify that the file was processed correctly
    }

    public function testExportExcelFile()
    {
        // Assuming you have a route named 'export' and it expects a table name as a parameter
        // You might need to adjust this according to your actual route and logic

        // Create a test table and populate it with test data
        Schema::create('test_table', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        \DB::table('test_table')->insert([
            'name' => 'Test Item'
        ]);

        $response = $this->get(route('export', ['table_name' => 'test_table']));

        $response->assertStatus(200);
        // Add more assertions here to verify that an Excel file was returned correctly
    }
}
