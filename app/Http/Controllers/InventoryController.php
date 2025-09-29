<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        return response()->json(Inventory::all());
    }

    public function show($id)
    {
        return response()->json(Inventory::findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_name'=>'required|string',
            'quantity'=>'required|integer',
            'purchase_date'=>'nullable|date',
            'assigned_to'=>'nullable|string',
            'remarks'=>'nullable|string'
        ]);

        $inventory = Inventory::create($data);
        return response()->json($inventory);
    }

    public function update(Request $request, $id)
    {
        $inventory = Inventory::findOrFail($id);
        $data = $request->validate([
            'item_name'=>'sometimes|string',
            'quantity'=>'sometimes|integer',
            'purchase_date'=>'sometimes|date',
            'assigned_to'=>'sometimes|string',
            'remarks'=>'sometimes|string'
        ]);
        $inventory->update($data);
        return response()->json($inventory);
    }

    public function destroy($id)
    {
        $inventory = Inventory::findOrFail($id);
        $inventory->delete();
        return response()->json(['message'=>'Inventory deleted']);
    }
}
