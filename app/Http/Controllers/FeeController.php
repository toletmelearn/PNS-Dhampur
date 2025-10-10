<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Student;
use App\Models\Payment;
use App\Models\ClassModel;
use App\Services\UserFriendlyErrorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\SecurityHelper;

class FeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Fee::with(['student.user', 'student.classModel']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student.user', function($q) use ($search) {
                $q->where('name', 'like', SecurityHelper::buildLikePattern($search))
                  ->orWhere('email', 'like', SecurityHelper::buildLikePattern($search));
            });
        }

        if ($request->filled('class_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('fee_type')) {
            $query->where('fee_type', $request->fee_type);
        }

        if ($request->filled('month')) {
            $query->whereMonth('due_date', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('due_date', $request->year);
        }

        $fees = $query->orderBy('due_date', 'desc')->paginate(15);

        // Calculate statistics
        $stats = [
            'total_fees' => Fee::count(),
            'total_amount' => Fee::sum('amount'),
            'total_collected' => Fee::sum('paid_amount'),
            'pending_amount' => Fee::whereRaw('amount > paid_amount')->sum(DB::raw('amount - paid_amount')),
            'overdue_count' => Fee::where('due_date', '<', now())->where('status', '!=', 'paid')->count(),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'fees' => $fees,
                'stats' => $stats
            ]);
        }

        // Fix N+1 query by adding eager loading for students relationship
        $classes = ClassModel::with(['students'])->get();
        return view('finance.fees.index', compact('fees', 'stats', 'classes'));
    }

    public function create()
    {
        // Use pagination for students to avoid memory issues
        $students = Student::with(['user', 'classModel'])->paginate(100);
        // Fix N+1 query by adding eager loading for students relationship
        $classes = ClassModel::with(['students'])->get();
        
        return view('finance.fees.create', compact('students', 'classes'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_type' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'academic_year' => 'required|string|max:20',
            'month' => 'nullable|string|max:20',
            'late_fee' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Calculate final amount
            $finalAmount = $validatedData['amount'] + ($validatedData['late_fee'] ?? 0) - ($validatedData['discount'] ?? 0);

            $fee = Fee::create([
                'student_id' => $validatedData['student_id'],
                'fee_type' => $validatedData['fee_type'],
                'amount' => $finalAmount,
                'due_date' => $validatedData['due_date'],
                'academic_year' => $validatedData['academic_year'],
                'month' => $validatedData['month'],
                'late_fee' => $validatedData['late_fee'] ?? 0,
                'discount' => $validatedData['discount'] ?? 0,
                'paid_amount' => 0,
                'status' => 'unpaid',
                'remarks' => $validatedData['remarks'],
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fee record created successfully',
                    'fee' => $fee->load('student.user')
                ]);
            }

            return redirect()->route('fees.index')->with('success', 'Fee record created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(
                    UserFriendlyErrorService::jsonErrorResponse($e, 'fee_create'),
                    500
                );
            }

            return back()->withInput()->withErrors(['error' => UserFriendlyErrorService::getErrorMessage($e, 'fee_create')]);
        }
    }

    public function show($id)
    {
        $fee = Fee::with(['student.user', 'student.classModel', 'payments'])->findOrFail($id);
        
        if (request()->expectsJson()) {
            return response()->json($fee);
        }

        return view('finance.fees.show', compact('fee'));
    }

    public function edit($id)
    {
        $fee = Fee::with(['student.user', 'student.classModel'])->findOrFail($id);
        // Use pagination for students to avoid memory issues
        $students = Student::with(['user', 'classModel'])->paginate(100);
        // Fix N+1 query by adding eager loading for students relationship
        $classes = ClassModel::with(['students'])->get();
        
        return view('finance.fees.edit', compact('fee', 'students', 'classes'));
    }

    public function update(Request $request, $id)
    {
        $fee = Fee::findOrFail($id);

        $validatedData = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_type' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'academic_year' => 'required|string|max:20',
            'month' => 'nullable|string|max:20',
            'late_fee' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Calculate final amount
            $finalAmount = $validatedData['amount'] + ($validatedData['late_fee'] ?? 0) - ($validatedData['discount'] ?? 0);

            $fee->update([
                'student_id' => $validatedData['student_id'],
                'fee_type' => $validatedData['fee_type'],
                'amount' => $finalAmount,
                'due_date' => $validatedData['due_date'],
                'academic_year' => $validatedData['academic_year'],
                'month' => $validatedData['month'],
                'late_fee' => $validatedData['late_fee'] ?? 0,
                'discount' => $validatedData['discount'] ?? 0,
                'remarks' => $validatedData['remarks'],
            ]);

            // Update status based on payment
            if ($fee->paid_amount >= $fee->amount) {
                $fee->status = 'paid';
            } elseif ($fee->paid_amount > 0) {
                $fee->status = 'partial';
            } else {
                $fee->status = 'unpaid';
            }
            $fee->save();

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fee record updated successfully',
                    'fee' => $fee->load('student.user')
                ]);
            }

            return redirect()->route('fees.index')->with('success', 'Fee record updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(
                    UserFriendlyErrorService::jsonErrorResponse($e, 'fee_update'),
                    500
                );
            }

            return back()->withInput()->withErrors(['error' => UserFriendlyErrorService::getErrorMessage($e, 'fee_update')]);
        }
    }

    public function destroy($id)
    {
        try {
            $fee = Fee::findOrFail($id);
            
            // Check if fee has payments
            if ($fee->paid_amount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete fee record with payments. Please refund payments first.'
                ], 400);
            }

            $fee->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fee record deleted successfully'
                ]);
            }

            return redirect()->route('fees.index')->with('success', 'Fee record deleted successfully');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(
                    UserFriendlyErrorService::jsonErrorResponse($e, 'fee_delete'),
                    500
                );
            }

            return back()->withErrors(['error' => UserFriendlyErrorService::getErrorMessage($e, 'fee_delete')]);
        }
    }

    public function recordPayment(Request $request, $id)
    {
        $fee = Fee::findOrFail($id);

        $validatedData = $request->validate([
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|string|in:cash,online,card,cheque,bank_transfer',
            'payment_date' => 'required|date',
            'transaction_id' => 'nullable|string|max:255',
            'cheque_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $amountPaid = $validatedData['amount_paid'];
            $remainingAmount = $fee->amount - $fee->paid_amount;

            if ($amountPaid > $remainingAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount cannot exceed remaining balance of ₹' . number_format($remainingAmount, 2)
                ], 400);
            }

            // Create payment record
            $payment = FeePayment::create([
                'fee_id' => $fee->id,
                'amount_paid' => $amountPaid,
                'payment_date' => $validatedData['payment_date'],
                'payment_mode' => $validatedData['payment_mode'],
                'transaction_id' => $validatedData['transaction_id'],
                'cheque_number' => $validatedData['cheque_number'],
                'bank_name' => $validatedData['bank_name'],
                'receipt_no' => 'RCP' . str_pad($fee->id, 6, '0', STR_PAD_LEFT) . time(),
                'remarks' => $validatedData['remarks'],
            ]);

            // Update fee record
            $fee->paid_amount += $amountPaid;
            $fee->paid_date = $validatedData['payment_date'];
            
            if ($fee->paid_amount >= $fee->amount) {
                $fee->status = 'paid';
            } else {
                $fee->status = 'partial';
            }
            
            $fee->save();

            // Generate receipt
            $receiptPath = $this->generateReceipt($fee, $payment);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'payment' => $payment,
                'fee' => $fee->fresh(),
                'receipt_url' => asset('storage/' . $receiptPath)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json(
                UserFriendlyErrorService::jsonErrorResponse($e, 'payment_record'),
                500
            );
        }
    }

    public function getStudentFees($studentId)
    {
        $fees = Fee::where('student_id', $studentId)
                   ->with(['student.user', 'payments'])
                   ->orderBy('due_date', 'desc')
                   ->get();

        return response()->json($fees);
    }

    public function bulkCreateFees(Request $request)
    {
        $validatedData = $request->validate([
            'class_id' => 'required|exists:class_models,id',
            'fee_type' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'academic_year' => 'required|string|max:20',
            'month' => 'nullable|string|max:20',
        ]);

        try {
            DB::beginTransaction();

            $students = Student::where('class_id', $validatedData['class_id'])->get();
            $feesCreated = 0;

            foreach ($students as $student) {
                Fee::create([
                    'student_id' => $student->id,
                    'fee_type' => $validatedData['fee_type'],
                    'amount' => $validatedData['amount'],
                    'due_date' => $validatedData['due_date'],
                    'academic_year' => $validatedData['academic_year'],
                    'month' => $validatedData['month'],
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                ]);
                $feesCreated++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully created {$feesCreated} fee records"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating bulk fees', [
                'class_id' => $request->class_id ?? null,
                'fee_type' => $request->fee_type ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => UserFriendlyErrorService::getErrorMessage($e, 'general')
            ], 500);
        }
    }

    private function generateReceipt($fee, $payment)
    {
        $student = $fee->student()->with('user', 'classModel')->first();
        
        $data = [
            'fee' => $fee,
            'payment' => $payment,
            'student' => $student,
            'school_name' => config('app.name', 'PNS Dhampur'),
            'receipt_date' => now()->format('d/m/Y'),
        ];

        $pdf = PDF::loadView('pdfs.fee_receipt', $data)->setPaper('A4', 'portrait');
        
        $filename = 'receipts/fee_receipt_' . $payment->receipt_no . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    public function export(Request $request)
    {
        // Export functionality - placeholder for future implementation
        return response()->json(['message' => 'Export functionality will be implemented']);
    }
}
