<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Student;
use App\Models\FeePayment;
use App\Models\ClassModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeeService
{
    /**
     * Get fee structure for all classes
     */
    public function getFeeStructure()
    {
        $classes = ClassModel::with('feeStructure')->get();
        return $classes;
    }

    /**
     * Process fee payment
     */
    public function processPayment($studentId, $feeIds, $amount, $paymentMethod)
    {
        // Create payment record
        $payment = FeePayment::create([
            'student_id' => $studentId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'transaction_id' => $this->generateTransactionId(),
            'payment_date' => now(),
            'status' => 'completed'
        ]);

        // Update fee records
        $remainingAmount = $amount;
        foreach (Fee::whereIn('id', $feeIds)->orderBy('due_date')->get() as $fee) {
            $dueAmount = $fee->calculateDueAmount();
            
            if ($remainingAmount <= 0) {
                break;
            }
            
            $amountToApply = min($remainingAmount, $dueAmount);
            $fee->paid_amount += $amountToApply;
            $fee->paid_date = now();
            $fee->status = $fee->paid_amount >= $fee->amount ? 'paid' : 'partial';
        }
        
    /**
     * Generate fee receipt
     *
     * @param int $paymentId
     * @return array
     */
    public function generateReceipt($paymentId)
    {
        try {
            $payment = FeePayment::with(['fee.student.user', 'fee.student.classModel'])
                ->findOrFail($paymentId);

            // Generate receipt logic here
            // This would typically involve creating a PDF

            return [
                'success' => true,
                'data' => [
                    'payment' => $payment,
                    'receipt_url' => url("/fees/receipts/{$payment->id}")
                ],
                'message' => 'Receipt generated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Error generating receipt: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while generating receipt'
            ];
        }
    }
            $fee->save();
            
            // Associate fee with payment
            $payment->fees()->attach($fee->id, ['amount_applied' => $amountToApply]);
            
            $remainingAmount -= $amountToApply;
        }

        return $payment;
    }

    /**
     * Complete online payment after gateway verification
     */
    public function completeOnlinePayment($transactionId, $studentId, $feeIds, $amount)
    {
        $payment = FeePayment::create([
            'student_id' => $studentId,
            'amount' => $amount,
            'payment_method' => 'online',
            'transaction_id' => $transactionId,
            'payment_date' => now(),
            'status' => 'completed'
        ]);

        // Update fee records
        $this->applyPaymentToFees($payment, $feeIds, $amount);

        return $payment;
    }

    /**
     * Apply payment amount to fee records
     */
    private function applyPaymentToFees($payment, $feeIds, $amount)
    {
        $remainingAmount = $amount;
        foreach (Fee::whereIn('id', $feeIds)->orderBy('due_date')->get() as $fee) {
            $dueAmount = $fee->calculateDueAmount();
            
            if ($remainingAmount <= 0) {
                break;
            }
            
            $amountToApply = min($remainingAmount, $dueAmount);
            $fee->paid_amount += $amountToApply;
            $fee->paid_date = now();
            $fee->status = $fee->paid_amount >= $fee->amount ? 'paid' : 'partial';
            $fee->save();
            
            // Associate fee with payment
            $payment->fees()->attach($fee->id, ['amount_applied' => $amountToApply]);
            
            $remainingAmount -= $amountToApply;
        }
    }

    /**
     * Generate reports based on filters
     */
    public function generateReports($fromDate, $toDate, $classId, $feeType)
    {
        $query = FeePayment::with(['student.classModel']);

        if ($fromDate) {
            $query->whereDate('payment_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('payment_date', '<=', $toDate);
        }

        if ($classId) {
            $query->whereHas('student', function($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }

        if ($feeType) {
            $query->whereHas('fees', function($q) use ($feeType) {
                $q->where('fee_type', $feeType);
            });
        }

        return $query->orderBy('payment_date', 'desc')->get();
    }

    /**
     * Generate a unique transaction ID
     */
    private function generateTransactionId()
    {
        return 'TXN' . time() . rand(1000, 9999);
    }
}