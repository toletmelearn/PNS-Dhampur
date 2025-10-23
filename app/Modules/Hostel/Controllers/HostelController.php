<?php

namespace App\Modules\Hostel\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HostelBuilding;
use App\Models\HostelRoom;
use App\Models\HostelAllocation;
use App\Models\Student;

class HostelController extends Controller
{
    public function index()
    {
        $buildings = HostelBuilding::with(['rooms.activeAllocations'])->orderBy('name')->get();
        return view('hostel.index', compact('buildings'));
    }

    public function allocate(Request $request, int $roomId)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $room = HostelRoom::withCount(['activeAllocations'])->findOrFail($roomId);
        if ($room->active_allocations_count >= $room->bed_count) {
            return back()->withErrors(['room' => 'Room is full.']);
        }

        // Ensure student not already allocated
        $already = HostelAllocation::whereNull('vacated_at')->where('student_id', $request->student_id)->exists();
        if ($already) {
            return back()->withErrors(['student_id' => 'Student already has an active allocation.']);
        }

        HostelAllocation::create([
            'room_id' => $roomId,
            'student_id' => $request->student_id,
            'allocated_at' => now(),
            'status' => 'active',
        ]);

        return redirect()->route('hostel.index')->with('status', 'Allocation created');
    }

    public function vacate(int $allocationId)
    {
        $allocation = HostelAllocation::findOrFail($allocationId);
        $allocation->update([
            'vacated_at' => now(),
            'status' => 'vacated',
        ]);

        return redirect()->route('hostel.index')->with('status', 'Allocation vacated');
    }
}
