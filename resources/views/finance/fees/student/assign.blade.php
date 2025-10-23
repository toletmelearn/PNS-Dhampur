<!DOCTYPE html>
<html>
<head>
    <title>Assign Fees to Student</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style> body{font-family:system-ui,Arial,sans-serif;margin:20px;} label{display:block;margin-top:8px;} </style>
</head>
<body>
    <h1>Assign Fees to {{ $student->name }}</h1>

    <form action="{{ route('student-fees.storeAssignment', $student) }}" method="POST">
        @csrf
        <label>Fee Structure
            <select name="fee_structure_id" required>
                @foreach($structures as $structure)
                    <option value="{{ $structure->id }}">{{ $structure->name }} ({{ $structure->academic_year }})</option>
                @endforeach
            </select>
        </label>

        <label>Academic Year
            <input type="text" name="academic_year" required placeholder="2025-26" />
        </label>

        <button type="submit" style="margin-top:12px;">Assign Fees</button>
    </form>

    <p style="margin-top:16px;"><a href="{{ route('student-fees.index') }}">Back to fees</a></p>
</body>
</html>