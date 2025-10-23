<!DOCTYPE html>
<html>
<head>
    <title>Edit Fee Structure</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style> body{font-family:system-ui,Arial,sans-serif;margin:20px;} label{display:block;margin-top:8px;} </style>
</head>
<body>
    <h1>Edit Fee Structure</h1>

    <form action="{{ route('fee-structures.update', $feeStructure) }}" method="POST">
        @csrf
        @method('PUT')
        <label>Class
            <select name="class_model_id" required>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ $feeStructure->class_model_id == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                @endforeach
            </select>
        </label>

        <label>Name
            <input type="text" name="name" value="{{ $feeStructure->name }}" required />
        </label>

        <label>Academic Year
            <input type="text" name="academic_year" value="{{ $feeStructure->academic_year }}" required />
        </label>

        <label>Description
            <textarea name="description" rows="3">{{ $feeStructure->description }}</textarea>
        </label>

        <fieldset style="margin-top:12px;">
            <legend>Items</legend>
            <div id="items">
                @foreach($feeStructure->items as $idx => $item)
                    <div style="margin-top:8px;">
                        <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}" />
                        <input type="text" name="items[{{ $idx }}][item_name]" value="{{ $item->item_name }}" required />
                        <input type="number" step="0.01" name="items[{{ $idx }}][amount]" value="{{ $item->amount }}" required />
                        <select name="items[{{ $idx }}][frequency]" required>
                            <option value="monthly" {{ $item->frequency==='monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="annual" {{ $item->frequency==='annual' ? 'selected' : '' }}>Annual</option>
                            <option value="one_time" {{ $item->frequency==='one_time' ? 'selected' : '' }}>One-time</option>
                        </select>
                        <input type="number" name="items[{{ $idx }}][due_day]" value="{{ $item->due_day }}" min="1" max="31" />
                    </div>
                @endforeach
            </div>
            <button type="button" onclick="addItem()">Add Item</button>
        </fieldset>

        <button type="submit" style="margin-top:12px;">Update Structure</button>
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