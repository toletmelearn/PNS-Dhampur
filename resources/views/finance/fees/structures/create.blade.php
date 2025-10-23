<!DOCTYPE html>
<html>
<head>
    <title>Create Fee Structure</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style> body{font-family:system-ui,Arial,sans-serif;margin:20px;} label{display:block;margin-top:8px;} </style>
</head>
<body>
    <h1>Create Fee Structure</h1>

    <form action="{{ route('fee-structures.store') }}" method="POST">
        @csrf
        <label>Class
            <select name="class_model_id" required>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </select>
        </label>

        <label>Name
            <input type="text" name="name" required />
        </label>

        <label>Academic Year
            <input type="text" name="academic_year" required placeholder="2025-26" />
        </label>

        <label>Description
            <textarea name="description" rows="3"></textarea>
        </label>

        <fieldset style="margin-top:12px;">
            <legend>Items (optional)</legend>
            <div id="items"></div>
            <button type="button" onclick="addItem()">Add Item</button>
        </fieldset>

        <button type="submit" style="margin-top:12px;">Save Structure</button>
    </form>

    <script>
        function addItem() {
            const container = document.getElementById('items');
            const idx = container.children.length;
            const wrapper = document.createElement('div');
            wrapper.style.marginTop = '8px';
            wrapper.innerHTML = `
                <input type="text" name="items[${idx}][item_name]" placeholder="Item name" required />
                <input type="number" step="0.01" name="items[${idx}][amount]" placeholder="Amount" required />
                <select name="items[${idx}][frequency]" required>
                    <option value="monthly">Monthly</option>
                    <option value="annual">Annual</option>
                    <option value="one_time">One-time</option>
                </select>
                <input type="number" name="items[${idx}][due_day]" placeholder="Due day" min="1" max="31" />
            `;
            container.appendChild(wrapper);
        }
    </script>
    
    <p style="margin-top:16px;"><a href="{{ route('fee-structures.index') }}">Back to list</a></p>
</body>
</html>