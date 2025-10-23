<?php

namespace App\Http\Controllers;

use App\Models\StudentFee;
use App\Models\FeeTransaction;
use App\Models\FeeReceipt;
use App\Models\PaymentGatewayConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeePaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function initiate(Request $request, StudentFee $studentFee)
    {
        $this->authorize('pay', $studentFee);
        $gateway = PaymentGatewayConfig::where('is_active', true)->first();
        if (!$gateway) {
            return back()->withErrors('Payment gateway not configured');
        }

        // Stub initiate with gateway params
        $order = [
            'amount' => (int) round(($studentFee->amount - $studentFee->paid_amount) * 100),
            'currency' => 'INR',
            'notes' => [
                'student_id' => $studentFee->student_id,
                'student_fee_id' => $studentFee->id,
            ],
        ];

        return view('finance.fees.payment.checkout', compact('studentFee', 'gateway', 'order'));
    }

    public function callback(Request $request)
    {
        $validated = $request->validate([
            'student_fee_id' => 'required|exists:student_fees,id',
            'transaction_id' => 'required|string',
            'gateway' => 'required|string',
            'status' => 'required|in:success,failed,pending',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'paid_at' => 'nullable|date',
        ]);

        $studentFee = StudentFee::findOrFail($validated['student_fee_id']);

        DB::transaction(function () use ($studentFee, $validated) {
            $txn = FeeTransaction::create([
                'student_fee_id' => $studentFee->id,
                'amount' => $validated['amount'],
                'transaction_id' => $validated['transaction_id'],
                'gateway' => $validated['gateway'],
                'status' => $validated['status'],
                'payment_method' => $validated['payment_method'] ?? null,
                'paid_at' => $validated['paid_at'] ?? now(),
                'metadata' => request('metadata', []),
            ]);

            if ($validated['status'] === 'success') {
                $studentFee->update([
                    'paid_amount' => ($studentFee->paid_amount + $validated['amount']),
                    'paid_date' => now(),
                    'status' => (($studentFee->paid_amount + $validated['amount']) >= $studentFee->amount) ? 'paid' : 'partial',
                ]);

                $receipt = FeeReceipt::create([
                    'student_fee_id' => $studentFee->id,
                    'fee_transaction_id' => $txn->id,
                    'receipt_number' => 'RCPT-' . date('Ymd') . '-' . $txn->id,
                    'issued_at' => now(),
                ]);

                $txn->update(['receipt_id' => $receipt->id]);
            }
        });

        return redirect()->route('student-fees.show', $studentFee->id)->with('success', 'Payment processed');
    }

    public function receipt(FeeReceipt $receipt)
    {
        $receipt->load('studentFee.student', 'transaction');
        return view('finance.fees.payment.receipt', compact('receipt'));
    }
}
