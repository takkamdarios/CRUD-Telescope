<!-- resources/views/import.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Excel File</title>
    <style>
        .alert {
            padding: 10px;
            color: white;
            margin-bottom: 15px;
        }
        .alert-success { background-color: green; }
        .alert-danger { background-color: red; }
    </style>
</head>
<body>
<h1>Import Excel File</h1>

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('import') }}" method="post" enctype="multipart/form-data">
    @csrf
    <label for="excel_file">Choose Excel File:</label>
    <input type="file" id="excel_file" name="excel_file" required>
    <button type="submit" id="import_button">Import</button>
</form>
<a href="{{ route('tables.index') }}" class="btn btn-primary">View Imported Tables</a>

<script>
    document.getElementById('import_button').addEventListener('click', function() {
        this.disabled = true;
        this.form.submit();
    });
</script>
</body>
</html>
