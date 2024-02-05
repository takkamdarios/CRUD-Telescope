<!-- resources/views/table_data.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta charset="UTF-8">
    <title>Data for Table: {{ $tableName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 23px; /* Adjust the value as needed */

        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f7f7f7;
        }
        tr:nth-child(even) {
            background-color: #f0f0f0;
        }
        tr:hover {
            background-color: #ddd;
        }
        .button {
            padding: 5px 10px;
            text-decoration: none;
            color: white;
            background-color: blue;
            border-radius: 5px;
            margin-right: 5px;
            margin-bottom: 20px; /* Add a bottom margin */
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 70%;
        }
        /* Add this to your existing style tag */
        .action-icon {
            display: inline-block;
            margin-right: 5px;
            color: #007bff; /* Bootstrap primary color */
            text-decoration: none;
        }


        .action-form {
            display: inline-block;
        }

        .action-icon i {
            font-size: 1rem; /* You can adjust the size */
        }

        .action-icon:hover, .action-icon:focus {
            color: #0056b3; /* Bootstrap primary color darken */
        }

        /* Adjust padding as needed */
        .action-form button {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }
        .modal-header {
            display: flex;
            justify-content: space-between; /* Aligns items on the main-axis */
            align-items: center; /* Aligns items on the cross-axis */

        }

        .close {
            cursor: pointer;
            font-size: 24px; /* Adjust size as needed */
            font-weight: bold;
        }


    </style>
</head>
<body>
<h1>Data for Table: {{ $tableName }}</h1>
<a href="javascript:void(0);" onclick="showCreateModal({{ json_encode($headers) }}, '{{ $tableName }}')" class="button" style="margin-bottom: 70px;">New</a>



<table>
    <thead>
    <tr>
        @foreach ($headers as $header)
            <th>{{ $header }}</th>
        @endforeach
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>

    @foreach ($data as $rowData)
        <tr>
            @foreach ($rowData as $cell)
                <td>{{ $cell }}</td>
            @endforeach
                <td>
                    <a href="javascript:void(0);" onclick="showModal({{ json_encode($rowData) }})" class="action-icon"><i class="fas fa-eye"></i></a>
                    <a href="javascript:void(0);" onclick="showUpdateModal({{ json_encode($rowData) }}, {{ json_encode($headers) }}, '{{ $tableName }}')" class="action-icon"><i class="fas fa-edit"></i></a>
                    <form action="{{ route('tables.destroy', ['tableName' => $tableName, 'id' => $rowData->id]) }}" method="POST" onsubmit="return confirm('Are you sure?')" class="action-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-icon"><i class="fas fa-trash-alt"></i></button>
                    </form>
                </td>
        </tr>
    @endforeach
    </tbody>
</table>

<!-- Modal HTML -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <p id="modalText"></p>
    </div>
</div>

<script>
    var modal = document.getElementById("myModal");
    var span = document.getElementsByClassName("close")[0];


    function showModal(rowData) {
        let content = `<div class="modal-header">
                       <h3>Details for ID: ${rowData.id ? rowData.id : 'N/A'}</h3>
                   </div>
                   <div class="modal-body">
                       <table class="modal-table">`;

        for (const key in rowData) {
            content += `<tr>
                        <th>${key}</th>
                        <td>${rowData[key]}</td>
                    </tr>`;
        }

        content += `   </table>
                   </div>`;

        document.getElementById("modalText").innerHTML = content;
        modal.style.display = "block";

        let modalHeader = document.querySelector('.modal-header');

        // Check if a close button already exists
        let existingCloseButton = modalHeader.querySelector('.close');
        if (!existingCloseButton) {
            // Create a new close button if one does not exist
            let closeButton = document.createElement('span');
            closeButton.textContent = '×';
            closeButton.classList.add('close');

            // Append the close button to the modal header
            modalHeader.appendChild(closeButton);

            // Set the onclick event to close the modal
            closeButton.onclick = function() {
                modal.style.display = "none";
            };
        }

    }


    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }


    function showCreateModal(headers, tableName) {
        let content = `<div class="modal-header">
                       <h3>Create New Record in ${tableName}</h3>
                       <span class="close" style="cursor: pointer;">×</span>
                   </div>
                   <div class="modal-body">
                       <form id="createRecordForm" action="/dynamic-crud/${tableName}" method="post">
                           <input type="hidden" name="_token" value="${csrfToken}">
                           <table class="modal-table">`;

        headers.forEach(header => {
            if (!['id', 'created_at', 'updated_at'].includes(header)) { // Skip certain fields
                let label = header.charAt(0).toUpperCase() + header.slice(1).replace(/_/g, ' ');
                content += `<tr>
                            <th>${label}</th>
                            <td><input type="text" name="${header}" placeholder="Enter ${label}" class="form-control" /></td>
                        </tr>`;
            }
        });

        content += `       </table>
                       <div class="form-group" style="text-align: center; margin-top: 20px;">
                           <button type="submit" class="button">Create</button>
                       </div>
                   </form>
               </div>`;

        document.getElementById("modalText").innerHTML = content;
        modal.style.display = "block";

        // Bind click event to the close button
        document.querySelector('.modal-header .close').onclick = function() {
            modal.style.display = "none";
        };
    }



    function showUpdateModal(rowData, headers, tableName) {

        // Create the modal content with a close button and a dynamic form based on rowData
        let content = `
    <div class="modal-header" style="position: relative;">
        <h3>Update Record in ${tableName}</h3>
        <span class="close" style="cursor: pointer; font-size: 24px; font-weight: bold; position: absolute; right: 20px; top: 10px;">×</span>
    </div>
    <div class="modal-body">
        <form id="updateRecordForm" action="/dynamic-crud/${tableName}/${rowData.id}" method="post">
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="_method" value="PUT">
            <table class="modal-table">`;

        headers.forEach(header => {
            if (!['id', 'created_at', 'updated_at'].includes(header)) {
                let label = header.charAt(0).toUpperCase() + header.slice(1).replace(/_/g, ' ');
                let value = rowData[header] ? rowData[header] : '';
                content += `
            <tr>
                <th>${label}</th>
                <td><input type="text" name="${header}" value="${value}" class="form-control" /> </td>
            </tr>`;
            }
        });

        content += `
            </table>
            <div class="form-group" style="text-align: center; margin-top: 20px;">
                <button type="submit" class="button">Update</button>
            </div>
        </form>
    </div>`;

        // Insert the modal content into the page and display the modal
        document.getElementById("modalText").innerHTML = content;
        modal.style.display = "block";

        // Attach an event listener to the close button to hide the modal when clicked
        document.querySelector('.modal-header .close').onclick = function() {
            modal.style.display = "none";
        };
    }





</script>
<script>
    var csrfToken = "{{ csrf_token() }}";
    var errors = @json($errors->messages());
</script>

</body>
</html>
