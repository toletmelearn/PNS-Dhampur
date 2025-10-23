<!DOCTYPE html>
<html>
<head>
    <title>Fee Structures</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body { font-family: system-ui, Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f7f7f7; text-align: left; }
        .actions a { margin-right: 8px; }
        .header { display:flex; justify-content: space-between; align-items: center; }
        .btn { display:inline-block; padding:8px 12px; background:#007bff; color:#fff; text-decoration:none; border-radius:4px; }
    </style>
    </head>
<body>
    <div class="header">
        <h1>Fee Structures</h1>
        <a class="btn" href="{{ route('fee-structures.create') }}">Create Structure</a>
    </div>

    @if(session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Class</th>
                <th>Academic Year</th>
                <th>Items</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($structures as $structure)
                <tr>
                    <td>{{ $structure->name }}</td>
                    <td>{{ $structure->classModel->name ?? '—' }}</td>
                    <td>{{ $structure->academic_year }}</td>
                    <td>{{ $structure->items->count() }}</td>
                    <td>{{ $structure->is_active ? 'Yes' : 'No' }}</td>
                    <td class="actions">
                        <a href="{{ route('fee-structures.edit', $structure) }}">Edit</a>
                        <form action="{{ route('fee-structures.destroy', $structure) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Delete this structure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top:12px;">{{ $structures->links() }}</p>
    
    <p style="margin-top:20px;">
        <a href="{{ route('student-fees.index') }}">View Student Fees</a> |
        <a href="{{ route('payment.settings') }}">Payment Settings</a>
    </p>
</body>
</html>