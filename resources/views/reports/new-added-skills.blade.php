<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: DejaVu Sans;
            font-size: 12px;
        }

        h2 {
            background: #f2f2f2;
            padding: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }

        .strong {
            color: green;
            font-weight: bold;
        }

        .partial {
            color: orange;
        }

        .none {
            color: red;
        }
    </style>
</head>

<body>

    <h2>New Created Categories from Command</h2>

    <p>Generated at: {{ now()->format('Y-m-d H:i:s') }}</p>

    <table>
        <thead>
            <tr>
                <th>Created ID</th>
                <th>Created Title</th>
                <th>Parent ID</th>
                <th>Parent Title</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item['id'] }}</td>
                    <td>{{ $item['title'] }}</td>
                    <td>{{ $item['parent_id'] }}</td>
                    <td>{{ $item['parent_title'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
