<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceSchedule;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of maintenance schedules
     */
    public function index(Request $request)
    {
        $query = MaintenanceSchedule::with(['inventoryItem', 'assignedTo', 'completedBy']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->filled('maintenance_type')) {
            $query->byMaintenanceType($request->maintenance_type);
        }

        if ($request->filled('inventory_item_id')) {
            $query->byInventoryItem($request->inventory_item_id);
        }

        if ($request->filled('assigned_to')) {
            $query->byAssignedTo($request->assigned_to);
        }

        if ($request->filled('date_from')) {
            $query->where('scheduled_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('scheduled_date', '<=', $request->date_to);
        }

        if ($request->filled('overdue_only') && $request->overdue_only) {
            $query->overdue();
        }

        if ($request->filled('due_today') && $request->due_today) {
            $query->dueToday();
        }

        if ($request->filled('due_tomorrow') && $request->due_tomorrow) {
            $query->dueTomorrow();
        }

        if ($request->filled('due_this_week') && $request->due_this_week) {
            $query->dueThisWeek();
        }

        if ($request->filled('recurring_only') && $request->recurring_only) {
            $query->recurring();
        }

        if ($request->filled('requires_downtime') && $request->requires_downtime) {
            $query->requiresDowntime();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('work_performed', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('vendor_name', 'like', "%{$search}%")
                  ->orWhereHas('inventoryItem', function ($itemQuery) use ($search) {
                      $itemQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('barcode', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'scheduled_date');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $maintenances = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $maintenances,
            'summary' => [
                'total_schedules' => MaintenanceSchedule::count(),
                'pending_schedules' => MaintenanceSchedule::pending()->count(),
                'in_progress_schedules' => MaintenanceSchedule::inProgress()->count(),
                'completed_schedules' => MaintenanceSchedule::completed()->count(),
                'overdue_schedules' => MaintenanceSchedule::overdue()->count(),
                'due_today' => MaintenanceSchedule::dueToday()->count(),
                'due_tomorrow' => MaintenanceSchedule::dueTomorrow()->count(),
                'high_priority' => MaintenanceSchedule::highPriority()->count(),
                'critical' => MaintenanceSchedule::critical()->count(),
            ]
        ]);
    }

    /**
     * Store a newly created maintenance schedule
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'maintenance_type' => 'required|in:preventive,corrective,emergency,inspection,calibration,upgrade',
            'title' => 'required|string|max:255',
            'scheduled_date' => 'required|date',
            'estimated_duration' => 'nullable|integer|min:1',
            'frequency' => 'nullable|in:daily,weekly,monthly,quarterly,semi-annually,annually,one-time',
            'priority' => 'required|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:users,id',
            'estimated_cost' => 'nullable|numeric|min:0',
            'vendor_name' => 'nullable|string|max:255',
            'work_performed' => 'nullable|string',
            'parts_replaced' => 'nullable|string',
            'notes' => 'nullable|string',
            'requires_downtime' => 'required|boolean',
        ]);

        // Check if the inventory item exists and is an asset
        $inventoryItem = InventoryItem::findOrFail($validated['inventory_item_id']);
        
        if (!$inventoryItem->is_asset) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance can only be scheduled for assets'
            ], 422);
        }

        $maintenance = MaintenanceSchedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance schedule created successfully',
            'data' => $maintenance->load(['inventoryItem', 'assignedTo'])
        ], 201);
    }

    /**
     * Display the specified maintenance schedule
     */
    public function show($id)
    {
        $maintenance = MaintenanceSchedule::with([
                                           'inventoryItem',
                                           'assignedTo',
                                           'completedBy'
                                       ])
                                       ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $maintenance,
            'computed' => [
                'status_badge' => $maintenance->status_badge,
                'priority_badge' => $maintenance->priority_badge,
                'is_overdue' => $maintenance->is_overdue,
                'days_overdue' => $maintenance->days_overdue,
                'days_until_due' => $maintenance->days_until_due,
                'maintenance_duration' => $maintenance->maintenance_duration,
                'estimated_vs_actual_duration' => $maintenance->estimated_vs_actual_duration,
                'estimated_vs_actual_cost' => $maintenance->estimated_vs_actual_cost,
                'completion_status' => $maintenance->completion_status,
                'next_maintenance_date' => $maintenance->next_maintenance_date,
                'efficiency_score' => $maintenance->efficiency_score,
                'can_be_started' => $maintenance->canBeStarted(),
                'can_be_completed' => $maintenance->canBeCompleted(),
                'can_be_cancelled' => $maintenance->canBeCancelled(),
                'can_be_rescheduled' => $maintenance->canBeRescheduled(),
            ]
        ]);
    }

    /**
     * Update the specified maintenance schedule
     */
    public function update(Request $request, $id)
    {
        $maintenance = MaintenanceSchedule::findOrFail($id);

        // Check if maintenance can be updated
        if ($maintenance->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update completed maintenance'
            ], 422);
        }

        $validated = $request->validate([
            'maintenance_type' => 'sometimes|in:preventive,corrective,emergency,inspection,calibration,upgrade',
            'title' => 'sometimes|string|max:255',
            'scheduled_date' => 'sometimes|date',
            'estimated_duration' => 'nullable|integer|min:1',
            'frequency' => 'nullable|in:daily,weekly,monthly,quarterly,semi-annually,annually,one-time',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:users,id',
            'estimated_cost' => 'nullable|numeric|min:0',
            'vendor_name' => 'nullable|string|max:255',
            'work_performed' => 'nullable|string',
            'parts_replaced' => 'nullable|string',
            'notes' => 'nullable|string',
            'requires_downtime' => 'sometimes|boolean',
        ]);

        $maintenance->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance schedule updated successfully',
            'data' => $maintenance->load(['inventoryItem', 'assignedTo'])
        ]);
    }

    /**
     * Remove the specified maintenance schedule
     */
    public function destroy($id)
    {
        $maintenance = MaintenanceSchedule::findOrFail($id);
        
        // Check if maintenance can be deleted
        if ($maintenance->status === 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete maintenance that is in progress'
            ], 422);
        }

        $maintenance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance schedule deleted successfully'
        ]);
    }

    /**
     * Start a maintenance schedule
     */
    public function startMaintenance(Request $request, $id)
    {
        $maintenance = MaintenanceSchedule::findOrFail($id);
        
        if (!$maintenance->canBeStarted()) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance cannot be started in current status'
            ], 422);
        }

        $maintenance->startMaintenance();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance started successfully',
            'data' => $maintenance
        ]);
    }

    /**
     * Complete a maintenance schedule
     */
    public function completeMaintenance(Request $request, $id)
    {
        $maintenance = MaintenanceSchedule::findOrFail($id);
        
        $validated = $request->validate([
            'actual_duration' => 'nullable|integer|min:1',
            'actual_cost' => 'nullable|numeric|min:0',
            'work_performed' => 'nullable|string',
            'parts_replaced' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if (!$maintenance->canBeCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance cannot be completed in current status'
            ], 422);
        }

        $maintenance->completeMaintenance(
            $validated['actual_duration'] ?? null,
            $validated['actual_cost'] ?? null,
            $validated['work_performed'] ?? null,
            $validated['parts_replaced'] ?? null,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Maintenance completed successfully',
            'data' => $maintenance->load(['inventoryItem', 'assignedTo', 'completedBy'])
        ]);
    }

    /**
     * Cancel a maintenance schedule
     */
    public function cancelMaintenance(Request $request, $id)
    {
        $maintenance = MaintenanceSchedule::findOrFail($id);
        
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        if (!$maintenance->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance cannot be cancelled in current status'
            ], 422);
        }

        $maintenance->cancelMaintenance($validated['cancellation_reason']);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance cancelled successfully',
            'data' => $maintenance
        ]);
    }

    /**
     * Reschedule a maintenance
     */
    public function rescheduleMaintenance(Request $request, $id)
    {
        $maintenance = MaintenanceSchedule::findOrFail($id);
        
        $validated = $request->validate([
            'new_scheduled_date' => 'required|date|after:today',
            'reschedule_reason' => 'required|string|max:500',
        ]);

        if (!$maintenance->canBeRescheduled()) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance cannot be rescheduled in current status'
            ], 422);
        }

        $maintenance->rescheduleMaintenance(
            $validated['new_scheduled_date'],
            $validated['reschedule_reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Maintenance rescheduled successfully',
            'data' => $maintenance
        ]);
    }

    /**
     * Send reminder for maintenance
     */
    public function sendReminder($id)
    {
        $maintenance = MaintenanceSchedule::findOrFail($id);
        
        $maintenance->sendReminder();

        return response()->json([
            'success' => true,
            'message' => 'Reminder sent successfully',
            'data' => $maintenance
        ]);
    }

    /**
     * Get maintenance history for an inventory item
     */
    public function maintenanceHistory($inventoryItemId)
    {
        $inventoryItem = InventoryItem::findOrFail($inventoryItemId);
        
        $history = $inventoryItem->getMaintenanceHistory();

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    /**
     * Get maintenance report for an inventory item
     */
    public function maintenanceReport($inventoryItemId)
    {
        $inventoryItem = InventoryItem::findOrFail($inventoryItemId);
        
        $report = $inventoryItem->getMaintenanceReport();

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Duplicate a maintenance schedule
     */
    public function duplicate($id)
    {
        $maintenance = MaintenanceSchedule::findOrFail($id);
        
        $duplicatedMaintenance = $maintenance->duplicate();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance schedule duplicated successfully',
            'data' => $duplicatedMaintenance->load(['inventoryItem', 'assignedTo'])
        ]);
    }

    /**
     * Get overdue maintenance schedules
     */
    public function overdueSchedules(Request $request)
    {
        $query = MaintenanceSchedule::overdue()
                                   ->with(['inventoryItem', 'assignedTo']);

        if ($request->filled('days_overdue')) {
            $daysOverdue = $request->days_overdue;
            $query->whereRaw('DATEDIFF(NOW(), scheduled_date) >= ?', [$daysOverdue]);
        }

        $schedules = $query->orderBy('scheduled_date', 'asc')
                          ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Get maintenance schedules due today
     */
    public function dueToday()
    {
        $schedules = MaintenanceSchedule::dueToday()
                                       ->with(['inventoryItem', 'assignedTo'])
                                       ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Get maintenance schedules due tomorrow
     */
    public function dueTomorrow()
    {
        $schedules = MaintenanceSchedule::dueTomorrow()
                                       ->with(['inventoryItem', 'assignedTo'])
                                       ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Get maintenance schedules due this week
     */
    public function dueThisWeek()
    {
        $schedules = MaintenanceSchedule::dueThisWeek()
                                       ->with(['inventoryItem', 'assignedTo'])
                                       ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Get maintenance schedules due this month
     */
    public function dueThisMonth()
    {
        $schedules = MaintenanceSchedule::dueThisMonth()
                                       ->with(['inventoryItem', 'assignedTo'])
                                       ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Get maintenance statistics
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subMonth());
        $dateTo = $request->get('date_to', now());

        $totalSchedules = MaintenanceSchedule::whereBetween('scheduled_date', [$dateFrom, $dateTo])->count();

        // Status distribution
        $statusDistribution = MaintenanceSchedule::whereBetween('scheduled_date', [$dateFrom, $dateTo])
                                                 ->selectRaw('status, COUNT(*) as count')
                                                 ->groupBy('status')
                                                 ->get();

        // Priority distribution
        $priorityDistribution = MaintenanceSchedule::whereBetween('scheduled_date', [$dateFrom, $dateTo])
                                                   ->selectRaw('priority, COUNT(*) as count')
                                                   ->groupBy('priority')
                                                   ->get();

        // Maintenance type distribution
        $typeDistribution = MaintenanceSchedule::whereBetween('scheduled_date', [$dateFrom, $dateTo])
                                              ->selectRaw('maintenance_type, COUNT(*) as count')
                                              ->groupBy('maintenance_type')
                                              ->get();

        // Top maintained items
        $topItems = MaintenanceSchedule::with('inventoryItem')
                                      ->whereBetween('scheduled_date', [$dateFrom, $dateTo])
                                      ->selectRaw('inventory_item_id, COUNT(*) as maintenance_count')
                                      ->groupBy('inventory_item_id')
                                      ->orderBy('maintenance_count', 'desc')
                                      ->limit(10)
                                      ->get();

        // Monthly trends
        $monthlyTrends = MaintenanceSchedule::whereBetween('scheduled_date', [$dateFrom, $dateTo])
                                           ->selectRaw('DATE_FORMAT(scheduled_date, "%Y-%m") as month, COUNT(*) as count')
                                           ->groupBy('month')
                                           ->orderBy('month')
                                           ->get();

        // Cost analysis
        $costAnalysis = MaintenanceSchedule::whereBetween('scheduled_date', [$dateFrom, $dateTo])
                                          ->selectRaw('
                                              SUM(estimated_cost) as total_estimated_cost,
                                              SUM(actual_cost) as total_actual_cost,
                                              AVG(estimated_cost) as avg_estimated_cost,
                                              AVG(actual_cost) as avg_actual_cost
                                          ')
                                          ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_schedules' => $totalSchedules,
                    'pending_schedules' => MaintenanceSchedule::pending()->count(),
                    'in_progress_schedules' => MaintenanceSchedule::inProgress()->count(),
                    'completed_schedules' => MaintenanceSchedule::completed()->count(),
                    'overdue_schedules' => MaintenanceSchedule::overdue()->count(),
                ],
                'status_distribution' => $statusDistribution,
                'priority_distribution' => $priorityDistribution,
                'type_distribution' => $typeDistribution,
                'top_items' => $topItems,
                'monthly_trends' => $monthlyTrends,
                'cost_analysis' => $costAnalysis,
            ]
        ]);
    }

    /**
     * Generate maintenance report
     */
    public function report(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:summary,detailed,overdue,cost_analysis,efficiency',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled,overdue',
            'priority' => 'nullable|in:low,medium,high,critical',
            'maintenance_type' => 'nullable|in:preventive,corrective,emergency,inspection,calibration,upgrade',
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $query = MaintenanceSchedule::with(['inventoryItem', 'assignedTo', 'completedBy']);

        // Apply filters
        if ($validated['date_from'] ?? null) {
            $query->where('scheduled_date', '>=', $validated['date_from']);
        }

        if ($validated['date_to'] ?? null) {
            $query->where('scheduled_date', '<=', $validated['date_to']);
        }

        if ($validated['status'] ?? null) {
            $query->where('status', $validated['status']);
        }

        if ($validated['priority'] ?? null) {
            $query->where('priority', $validated['priority']);
        }

        if ($validated['maintenance_type'] ?? null) {
            $query->where('maintenance_type', $validated['maintenance_type']);
        }

        if ($validated['inventory_item_id'] ?? null) {
            $query->where('inventory_item_id', $validated['inventory_item_id']);
        }

        if ($validated['assigned_to'] ?? null) {
            $query->where('assigned_to', $validated['assigned_to']);
        }

        $schedules = $query->orderBy('scheduled_date', 'desc')->get();

        $reportData = [
            'type' => $validated['type'],
            'filters' => $validated,
            'generated_at' => now(),
            'schedules' => $schedules,
            'summary' => [
                'total_schedules' => $schedules->count(),
                'pending_schedules' => $schedules->where('status', 'pending')->count(),
                'in_progress_schedules' => $schedules->where('status', 'in_progress')->count(),
                'completed_schedules' => $schedules->where('status', 'completed')->count(),
                'overdue_schedules' => $schedules->where('status', 'overdue')->count(),
                'total_estimated_cost' => $schedules->sum('estimated_cost'),
                'total_actual_cost' => $schedules->sum('actual_cost'),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $reportData
        ]);
    }
}
