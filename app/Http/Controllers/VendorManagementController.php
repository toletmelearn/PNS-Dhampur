<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorPerformance;
use App\Services\VendorManagementService;
use App\Notifications\VendorPerformanceAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Traits\EmailValidationTrait;

class VendorManagementController extends Controller
{
    use EmailValidationTrait;
    
    protected $vendorService;

    public function __construct(VendorManagementService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    /**
     * Display vendor management dashboard
     */
    public function dashboard()
    {
        $data = $this->vendorService->getDashboardData();
        
        return view('vendor-management.dashboard', compact('data'));
    }

    /**
     * Display vendor list
     */
    public function index(Request $request)
    {
        $query = Vendor::with(['vendorPerformances' => function ($q) {
            $q->latest('evaluation_date')->first();
        }]);

        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->inactive();
            } elseif ($request->status === 'blacklisted') {
                $query->blacklisted();
            }
        }

        if ($request->filled('rating')) {
            $query->byRating($request->rating);
        }

        if ($request->filled('location')) {
            $query->where('state', $request->location);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('vendor_code', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $vendors = $query->paginate(20);

        return view('vendor-management.vendors.index', compact('vendors'));
    }

    /**
     * Show vendor details
     */
    public function show($id)
    {
        $vendor = Vendor::with([
            'vendorPerformances' => function ($query) {
                $query->orderBy('evaluation_date', 'desc');
            },
            'purchaseOrders' => function ($query) {
                $query->orderBy('order_date', 'desc')->limit(10);
            }
        ])->findOrFail($id);

        $performanceTrends = $this->vendorService->getVendorPerformanceTrends($id);
        
        return view('vendor-management.vendors.show', compact('vendor', 'performanceTrends'));
    }

    /**
     * Show create vendor form
     */
    public function create()
    {
        return view('vendor-management.vendors.create');
    }

    /**
     * Store new vendor
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            ...$this->getEmailValidationRules('vendors'),
            'phone' => 'required|string|max:20',
            'contact_person' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:255',
            'bank_details' => 'nullable|array',
            'notes' => 'nullable|string'
        ], $this->getEmailValidationMessages());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $vendor = Vendor::create($request->all());

            DB::commit();

            return redirect()->route('vendor-management.vendors.show', $vendor->id)
                ->with('success', 'Vendor created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to create vendor: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show edit vendor form
     */
    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);
        return view('vendor-management.vendors.edit', compact('vendor'));
    }

    /**
     * Update vendor
     */
    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            ...$this->getEmailValidationRulesForUpdate($id, 'vendors'),
            'phone' => 'required|string|max:20',
            'contact_person' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:255',
            'bank_details' => 'nullable|array',
            'notes' => 'nullable|string'
        ], $this->getEmailValidationMessages());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $vendor->update($request->all());

            // Clear cache
            Cache::forget('vendor_management_dashboard');

            return redirect()->route('vendor-management.vendors.show', $vendor->id)
                ->with('success', 'Vendor updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update vendor: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Activate vendor
     */
    public function activate($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            $vendor->activate();

            return response()->json([
                'success' => true,
                'message' => 'Vendor activated successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate vendor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate vendor
     */
    public function deactivate($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            $vendor->deactivate();

            return response()->json([
                'success' => true,
                'message' => 'Vendor deactivated successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate vendor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Blacklist vendor
     */
    public function blacklist(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Reason is required for blacklisting.'
            ], 422);
        }

        try {
            $vendor = Vendor::findOrFail($id);
            $vendor->blacklist($request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Vendor blacklisted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to blacklist vendor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Performance evaluation methods
     */
    public function performanceIndex(Request $request)
    {
        $query = VendorPerformance::with(['vendor', 'evaluator']);

        // Apply filters
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('grade')) {
            $query->byGrade($request->grade);
        }

        if ($request->filled('year')) {
            $query->whereYear('evaluation_date', $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $performances = $query->orderBy('evaluation_date', 'desc')->paginate(20);
        $vendors = Vendor::active()->orderBy('name')->get();

        return view('vendor-management.performance.index', compact('performances', 'vendors'));
    }

    /**
     * Show performance evaluation form
     */
    public function createPerformanceEvaluation($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);
        
        // Get suggested evaluation period
        $lastEvaluation = VendorPerformance::where('vendor_id', $vendorId)
            ->latest('evaluation_date')
            ->first();

        $startDate = $lastEvaluation ? 
            $lastEvaluation->evaluation_date->addDay() : 
            now()->subMonths(6);
        
        $endDate = now();

        return view('vendor-management.performance.create', compact('vendor', 'startDate', 'endDate'));
    }

    /**
     * Store performance evaluation
     */
    public function storePerformanceEvaluation(Request $request, $vendorId)
    {
        $validator = Validator::make($request->all(), [
            'evaluation_period_start' => 'required|date',
            'evaluation_period_end' => 'required|date|after:evaluation_period_start',
            'quality_rating' => 'required|numeric|min:1|max:5',
            'delivery_rating' => 'required|numeric|min:1|max:5',
            'service_rating' => 'required|numeric|min:1|max:5',
            'pricing_rating' => 'required|numeric|min:1|max:5',
            'communication_rating' => 'required|numeric|min:1|max:5',
            'strengths' => 'nullable|array',
            'weaknesses' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $performance = $this->vendorService->createPerformanceEvaluation(
                $vendorId,
                $request->evaluation_period_start,
                $request->evaluation_period_end,
                $request->all()
            );

            return redirect()->route('vendor-management.performance.show', $performance->id)
                ->with('success', 'Performance evaluation created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create performance evaluation: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show performance evaluation
     */
    public function showPerformanceEvaluation($id)
    {
        $performance = VendorPerformance::with(['vendor', 'evaluator'])->findOrFail($id);
        
        return view('vendor-management.performance.show', compact('performance'));
    }

    /**
     * Update performance evaluation
     */
    public function updatePerformanceEvaluation(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quality_rating' => 'required|numeric|min:1|max:5',
            'delivery_rating' => 'required|numeric|min:1|max:5',
            'service_rating' => 'required|numeric|min:1|max:5',
            'pricing_rating' => 'required|numeric|min:1|max:5',
            'communication_rating' => 'required|numeric|min:1|max:5',
            'strengths' => 'nullable|array',
            'weaknesses' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $performance = $this->vendorService->updatePerformanceEvaluation($id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Performance evaluation updated successfully.',
                'performance' => $performance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update performance evaluation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vendor recommendations
     */
    public function getVendorRecommendations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:inventory_items,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $recommendations = $this->vendorService->getVendorRecommendations($request->item_ids);

            return response()->json([
                'success' => true,
                'recommendations' => $recommendations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get vendor recommendations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate vendor comparison report
     */
    public function generateComparison(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor_ids' => 'required|array|min:2',
            'vendor_ids.*' => 'integer|exists:vendors,id',
            'metrics' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $comparison = $this->vendorService->generateVendorComparison(
                $request->vendor_ids,
                $request->metrics ?? []
            );

            return response()->json([
                'success' => true,
                'comparison' => $comparison
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate comparison: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export vendor performance report
     */
    public function exportPerformanceReport(Request $request)
    {
        $format = $request->get('format', 'excel');
        $vendorIds = $request->get('vendor_ids', []);
        $startDate = $request->get('start_date', now()->subYear());
        $endDate = $request->get('end_date', now());

        try {
            $query = VendorPerformance::with(['vendor', 'evaluator'])
                ->whereBetween('evaluation_date', [$startDate, $endDate]);

            if (!empty($vendorIds)) {
                $query->whereIn('vendor_id', $vendorIds);
            }

            $performances = $query->orderBy('evaluation_date', 'desc')->get();

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('vendor-management.reports.performance-pdf', compact('performances'));
                return $pdf->download('vendor-performance-report.pdf');
            } elseif ($format === 'csv') {
                return $this->exportPerformanceCSV($performances);
            } else {
                return Excel::download(new VendorPerformanceExport($performances), 'vendor-performance-report.xlsx');
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to export report: ' . $e->getMessage());
        }
    }

    /**
     * Get performance trends data for charts
     */
    public function getPerformanceTrends(Request $request)
    {
        $vendorId = $request->get('vendor_id');
        $periods = $request->get('periods', 6);

        try {
            $trends = $this->vendorService->getVendorPerformanceTrends($vendorId, $periods);

            return response()->json([
                'success' => true,
                'trends' => $trends
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get performance trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending evaluations
     */
    public function getPendingEvaluations()
    {
        try {
            $pendingEvaluations = VendorPerformance::dueForEvaluation()
                ->with('vendor')
                ->orderBy('next_evaluation_date', 'asc')
                ->get()
                ->map(function ($performance) {
                    return [
                        'vendor_id' => $performance->vendor_id,
                        'vendor_name' => $performance->vendor->name,
                        'last_evaluation' => $performance->evaluation_date->format('M d, Y'),
                        'due_date' => $performance->next_evaluation_date ? 
                            $performance->next_evaluation_date->format('M d, Y') : 'Overdue',
                        'days_overdue' => $performance->getDaysUntilNextEvaluation(),
                        'priority' => $performance->isOverdue() ? 'high' : 'medium'
                    ];
                });

            return response()->json([
                'success' => true,
                'pending_evaluations' => $pendingEvaluations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get pending evaluations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vendor statistics
     */
    public function getVendorStatistics(Request $request)
    {
        $period = $request->get('period', 'monthly'); // monthly, quarterly, yearly

        try {
            $statistics = [];
            
            if ($period === 'monthly') {
                $statistics = $this->getMonthlyVendorStatistics();
            } elseif ($period === 'quarterly') {
                $statistics = $this->getQuarterlyVendorStatistics();
            } else {
                $statistics = $this->getYearlyVendorStatistics();
            }

            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get vendor statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper methods for statistics
     */
    private function getMonthlyVendorStatistics()
    {
        $months = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            
            $months[] = [
                'period' => $date->format('M Y'),
                'new_vendors' => Vendor::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
                'active_vendors' => Vendor::active()->count(),
                'total_orders' => \DB::table('purchase_orders')
                    ->whereBetween('order_date', [$monthStart, $monthEnd])
                    ->count(),
                'total_spend' => \DB::table('purchase_orders')
                    ->whereBetween('order_date', [$monthStart, $monthEnd])
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_amount'),
                'evaluations_completed' => VendorPerformance::whereBetween('evaluation_date', [$monthStart, $monthEnd])->count()
            ];
        }
        
        return $months;
    }

    private function getQuarterlyVendorStatistics()
    {
        $quarters = [];
        
        for ($i = 3; $i >= 0; $i--) {
            $date = now()->subQuarters($i);
            $quarterStart = $date->copy()->startOfQuarter();
            $quarterEnd = $date->copy()->endOfQuarter();
            
            $quarters[] = [
                'period' => 'Q' . $date->quarter . ' ' . $date->year,
                'new_vendors' => Vendor::whereBetween('created_at', [$quarterStart, $quarterEnd])->count(),
                'active_vendors' => Vendor::active()->count(),
                'total_orders' => \DB::table('purchase_orders')
                    ->whereBetween('order_date', [$quarterStart, $quarterEnd])
                    ->count(),
                'total_spend' => \DB::table('purchase_orders')
                    ->whereBetween('order_date', [$quarterStart, $quarterEnd])
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_amount'),
                'evaluations_completed' => VendorPerformance::whereBetween('evaluation_date', [$quarterStart, $quarterEnd])->count()
            ];
        }
        
        return $quarters;
    }

    private function getYearlyVendorStatistics()
    {
        $years = [];
        
        for ($i = 4; $i >= 0; $i--) {
            $year = now()->subYears($i)->year;
            $yearStart = Carbon::create($year, 1, 1)->startOfYear();
            $yearEnd = Carbon::create($year, 12, 31)->endOfYear();
            
            $years[] = [
                'period' => $year,
                'new_vendors' => Vendor::whereBetween('created_at', [$yearStart, $yearEnd])->count(),
                'active_vendors' => Vendor::active()->count(),
                'total_orders' => \DB::table('purchase_orders')
                    ->whereBetween('order_date', [$yearStart, $yearEnd])
                    ->count(),
                'total_spend' => \DB::table('purchase_orders')
                    ->whereBetween('order_date', [$yearStart, $yearEnd])
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_amount'),
                'evaluations_completed' => VendorPerformance::whereBetween('evaluation_date', [$yearStart, $yearEnd])->count()
            ];
        }
        
        return $years;
    }

    /**
     * Export performance data as CSV
     */
    private function exportPerformanceCSV($performances)
    {
        $filename = 'vendor-performance-report-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($performances) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Vendor Name',
                'Evaluation Date',
                'Performance Score',
                'Performance Grade',
                'Quality Rating',
                'Delivery Rating',
                'Service Rating',
                'Pricing Rating',
                'Communication Rating',
                'On-Time Delivery %',
                'Risk Score',
                'Total Orders',
                'Total Value',
                'Evaluator'
            ]);

            // CSV data
            foreach ($performances as $performance) {
                fputcsv($file, [
                    $performance->vendor->name,
                    $performance->evaluation_date->format('Y-m-d'),
                    $performance->performance_score,
                    $performance->performance_grade,
                    $performance->quality_rating,
                    $performance->delivery_rating,
                    $performance->service_rating,
                    $performance->pricing_rating,
                    $performance->communication_rating,
                    $performance->on_time_delivery_percentage,
                    $performance->risk_score,
                    $performance->total_orders,
                    $performance->total_order_value,
                    $performance->evaluator->name ?? 'System'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}