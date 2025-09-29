<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PDF; // from barryvdh/laravel-dompdf
use Illuminate\Support\Facades\Storage;

class FeePaymentController extends Controller
{
    /**
     * Record a payment for a fee item.
     * Request expects:
     *  - paid_amount (numeric)
     *  - paid_date (date) optional
     *  - remarks (string) optional
     */
    public function pay(Request $request, $id)
    {
        $fee = Fee::findOrFail($id);

        $v = Validator::make($request->all(), [
            'paid_amount' => 'required|numeric|min:0.01',
            'paid_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'payment_mode' => 'nullable|string' // cash/cheque/online
        ]);

        if ($v->fails()) {
            return response()->json(['errors'=>$v->errors()], 422);
        }

        // Simple payment handling: add to paid_amount and set status
        $paid = (float)$request->input('paid_amount', 0);
        $fee->paid_amount += $paid;
        $fee->paid_date = $request->input('paid_date', now());
        $fee->remarks = $request->input('remarks', $fee->remarks);
        $fee->status = $fee->paid_amount >= $fee->amount ? 'paid' : 'partial';
        $fee->save();

        // Store a payment record in a payments sub-table (optional) - if you want, create payments table later.
        // For now we'll embed receipt data generation.

        // Generate receipt PDF now and store in storage/app/public/receipts/fee_{id}_{timestamp}.pdf
        $student = $fee->student()->first();
        $data = [
            'fee' => $fee,
            'student' => $student,
            'paid' => $paid,
            'payment_mode' => $request->input('payment_mode', 'cash'),
            'date' => $fee->paid_date,
            'school' => config('app.name', 'PNS Dhampur')
        ];

        $pdf = PDF::loadView('pdfs.fee_receipt', $data)->setPaper('A4', 'portrait');

        $filename = 'receipts/fee_'.$fee->id.'_'.time().'.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        // Save path into fee record if you want
        $documents = $fee->documents ?? [];
        if (!is_array($documents)) $documents = [];
        $documents[] = $filename;
        $fee->documents = $documents;
        $fee->save();

        return response()->json([
            'message' => 'Payment recorded',
            'fee' => $fee,
            'receipt_path' => $filename,
            'receipt_url' => asset('storage/'.$filename)
        ]);
    }

    /**
     * Return the PDF receipt as a response (download/view).
     */
    public function receipt($id)
    {
        $fee = Fee::findOrFail($id);

        // find latest stored receipt in documents array
        $docs = $fee->documents ?? [];
        if (!is_array($docs) || count($docs) === 0) {
            return response()->json(['message'=>'No receipt found for this fee'], 404);
        }

        // assume last element is the receipt we stored
        $filename = end($docs);

        if (!Storage::disk('public')->exists($filename)) {
            return response()->json(['message'=>'Receipt file not found on server'], 404);
        }

        $path = storage_path('app/public/'.$filename);
        return response()->file($path, [
            'Content-Type' => 'application/pdf'
        ]);
    }
}
