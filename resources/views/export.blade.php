<!-- resources/views/excel/export.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Export Data to Excel File</title>
</head>
<body>
<h1>Export Data to Excel File</h1>

<!-- resources/views/export.blade.php -->
<form action="{{ route('export') }}" method="post">
    @csrf
    <label for="table_name">Table Name:</label>
    <input type="text" id="table_name" name="table_name" required>
    <button type="submit">Export</button>
</form>

</body>
</html>
