<?php

namespace App\Http\Controllers;

use App\Models\AnnualBudget;
use App\Models\DepartmentBudget;
use App\Models\MonthlyExpense;
use App\Models\BudgetCategory;
use App\Models\ExpenseApproval;
use App\Models\BudgetReport;
use App\Models\Transaction;
use App\Services\DepartmentBudgetService;
use App\Services\BudgetTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BudgetManagementController extends Controller
{
    protected DepartmentBudgetService $departmentBudgetService;
    protected BudgetTrackingService $budgetTrackingService;

    public function __construct(
        DepartmentBudgetService $departmentBudgetService,
        BudgetTrackingService $budgetTrackingService
    ) {
        $this->departmentBudgetService = $departmentBudgetService;
        $this->budgetTrackingService = $budgetTrackingService;
    }

    /**
     * Annual budget allocation by management (department-wise)
     */
    public function allocateAnnual(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2020|max:2035',
            'allocations' => 'required|array|min:1',
            'allocations.*.department' => 'required|string|max:50',
            'allocations.*.allocated_budget' => 'required|numeric|min:0',
            'allocations.*.budget_manager_id' => 'nullable|exists:users,id',
            'allocations.*.notes' => 'nullable|string',
            'allocations.*.auto_allocate' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $year = $data['year'];

        DB::beginTransaction();
        try {
            // Create/update department budgets
            $totalAllocated = 0;
            foreach ($data['allocations'] as $allocation) {
                $allocation['budget_year'] = $year;
                $budget = $this->departmentBudgetService->createOrUpdateBudget($allocation);
                $totalAllocated += (float) $budget->allocated_budget;
            }

            // Upsert annual budget summary
            $annual = AnnualBudget::updateOrCreate(
                ['year' => $year],
                [
                    'total_allocated' => $totalAllocated,
                    'total_spent' => (float) DepartmentBudget::where('budget_year', $year)->sum('spent_amount'),
                    'status' => 'planned',
                ]
            );

            DB::commit();
            return response()->json([
                'success' => true,
                'annual_budget' => $annual,
                'utilization' => $annual->utilization,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to allocate annual budget', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Monthly expense tracking against budget
     */
    public function monthlyExpenses(Request $request)
    {
        $year = (int) $request->get('year', Carbon::now()->year);
        $department = $request->get('department');
        $category = $request->get('category');

        $query = Transaction::whereYear('transaction_date', $year)
            ->where('type', 'expense');
        if ($department) { $query->where('department', $department); }
        if ($category) { $query->where('category', $category); }

        $transactions = $query->get();

        $byMonth = $transactions->groupBy(function ($t) { return Carbon::parse($t->transaction_date)->format('n'); })
            ->map(function ($group) {
                return [
                    'amount' => round((float) $group->sum('amount'), 2),
                    'transaction_count' => $group->count(),
                ];
            });

        // Optionally snapshot into MonthlyExpense table
        foreach ($byMonth as $month => $summary) {
            MonthlyExpense::updateOrCreate(
                [
                    'year' => $year,
                    'month' => (int) $month,
                    'department' => $department ?? 'ALL',
                    'category' => $category,
                ],
                [
                    'amount' => $summary['amount'],
                    'transaction_count' => $summary['transaction_count'],
                    'snapshot_at' => now(),
                ]
            );
        }

        return response()->json([
            'year' => $year,
            'department' => $department,
            'category' => $category,
            'monthly' => $byMonth,
        ]);
    }

    /**
     * Budget utilization (real-time dashboard)
     */
    public function utilization(Request $request)
    {
        $year = (int) $request->get('year', Carbon::now()->year);
        $department = $request->get('department');

        $dashboard = $this->budgetTrackingService->getRealTimeDashboard($year, $department);

        return response()->json([
            'overview' => $dashboard['overview'] ?? null,
            'dashboard' => $dashboard,
        ]);
    }

    /**
     * Budget utilization alerts
     */
    public function alerts(Request $request)
    {
        $year = (int) $request->get('year', Carbon::now()->year);
        $department = $request->get('department');

        $alerts = $this->budgetTrackingService->getBudgetAlerts($year, $department);
        return response()->json($alerts);
    }

    /**
     * Department-wise allocation comparison
     */
    public function departmentAllocation(Request $request)
    {
        $year = (int) $request->get('year', Carbon::now()->year);
        $comparison = $this->departmentBudgetService->getDepartmentComparison($year);
        return response()->json($comparison);
    }

    /**
     * Variance analysis and forecasting
     */
    public function varianceAndForecast(Request $request)
    {
        $year = (int) $request->get('year', Carbon::now()->year);
        $department = $request->get('department');

        $dashboard = $this->budgetTrackingService->getRealTimeDashboard($year, $department);
        $variance = $dashboard['variance_analysis'] ?? [];
        $forecast = $dashboard['forecasting'] ?? [];

        // Persist a report snapshot
        BudgetReport::create([
            'year' => $year,
            'department' => $department,
            'type' => 'variance',
            'data' => $variance,
            'generated_at' => now(),
        ]);
        BudgetReport::create([
            'year' => $year,
            'department' => $department,
            'type' => 'forecast',
            'data' => $forecast,
            'generated_at' => now(),
        ]);

        return response()->json([
            'variance' => $variance,
            'forecast' => $forecast,
        ]);
    }

    /**
     * Budget categories CRUD
     */
    public function listCategories(Request $request)
    {
        $department = $request->get('department');
        $query = BudgetCategory::query();
        if ($department) { $query->where('department', $department); }
        return response()->json($query->orderBy('name')->get());
    }

    public function createCategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:budget_categories,name',
            'description' => 'nullable|string',
            'department' => 'nullable|string|max:50',
            'annual_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $category = BudgetCategory::create($data);
        return response()->json($category, 201);
    }

    public function updateCategory(Request $request, BudgetCategory $category)
    {
        $data = $request->validate([
            'description' => 'nullable|string',
            'department' => 'nullable|string|max:50',
            'annual_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $category->update($data);
        return response()->json($category);
    }

    public function deleteCategory(BudgetCategory $category)
    {
        $category->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Expense approvals (approve/reject)
     */
    public function approveExpense(Request $request, Transaction $transaction)
    {
        if ($transaction->type !== 'expense') {
            return response()->json(['error' => 'Only expense transactions can be approved'], 422);
        }
        $transaction->approve(auth()->id());

        ExpenseApproval::create([
            'transaction_id' => $transaction->id,
            'approver_id' => auth()->id(),
            'status' => 'approved',
            'approved_at' => now(),
            'notes' => $request->get('notes')
        ]);

        // Update linked department budget spending
        if ($transaction->department) {
            $this->departmentBudgetService->updateBudgetSpending($transaction->department, Carbon::parse($transaction->transaction_date)->year);
        }

        return response()->json(['success' => true, 'transaction' => $transaction]);
    }

    public function rejectExpense(Request $request, Transaction $transaction)
    {
        if ($transaction->type !== 'expense') {
            return response()->json(['error' => 'Only expense transactions can be rejected'], 422);
        }
        $transaction->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        ExpenseApproval::create([
            'transaction_id' => $transaction->id,
            'approver_id' => auth()->id(),
            'status' => 'rejected',
            'approved_at' => now(),
            'notes' => $request->get('notes')
        ]);

        return response()->json(['success' => true, 'transaction' => $transaction]);
    }

    /**
     * List generated budget reports
     */
    public function listReports(Request $request)
    {
        $year = $request->get('year');
        $department = $request->get('department');
        $type = $request->get('type');

        $query = BudgetReport::query();
        if ($year) { $query->where('year', $year); }
        if ($department) { $query->where('department', $department); }
        if ($type) { $query->where('type', $type); }

        return response()->json($query->orderByDesc('generated_at')->get());
    }
}