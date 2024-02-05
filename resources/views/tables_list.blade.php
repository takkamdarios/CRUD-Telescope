<!-- resources/views/tables_list.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Imported Tables</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            padding: 10px;
            background-color: #f0f0f0;
            margin-bottom: 5px;
            border-radius: 5px;
        }
        li a {
            text-decoration: none;
            color: #333;
            display: block;
        }
        li a:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>
<h1>Imported Tables</h1>
<ul>
    @foreach ($tables as $table)
        <li><a href="{{ route('tables.show', $table) }}">{{ $table }}</a></li>
    @endforeach
</ul>
</body>
</html>
