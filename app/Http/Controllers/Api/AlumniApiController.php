<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\AlumniBatch;
use App\Models\AlumniAchievement;
use App\Models\AlumniContribution;
use App\Models\AlumniEvent;
use App\Models\AlumniEventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AlumniApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Alumni::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->string('name')->toString() . '%');
        }
        if ($request->filled('current_status')) {
            $query->where('current_status', $request->string('current_status')->toString());
        }
        if ($request->filled('batch_id')) {
            $query->where('batch_id', (int)$request->get('batch_id'));
        }
        if ($request->filled('pass_year')) {
            $query->where('pass_year', (int)$request->get('pass_year'));
        }
        if ($request->filled('company')) {
            $query->where('company', 'like', '%' . $request->string('company')->toString() . '%');
        }
        if ($request->filled('industry')) {
            $query->where('industry', 'like', '%' . $request->string('industry')->toString() . '%');
        }
        if ($request->filled('location_city')) {
            $query->where('location_city', 'like', '%' . $request->string('location_city')->toString() . '%');
        }
        if ($request->filled('location_state')) {
            $query->where('location_state', 'like', '%' . $request->string('location_state')->toString() . '%');
        }
        if ($request->filled('location_country')) {
            $query->where('location_country', 'like', '%' . $request->string('location_country')->toString() . '%');
        }

        $perPage = (int)($request->get('per_page', 15));
        $alumni = $query->orderBy('name')->paginate($perPage);

        return response()->json(['success' => true, 'data' => $alumni]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'nullable|integer|exists:students,id',
            'batch_id' => 'nullable|integer|exists:alumni_batches,id',
            'name' => 'required|string|max:255',
            'admission_no' => 'nullable|string|max:100',
            'pass_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'leaving_reason' => 'nullable|string|max:255',
            'current_status' => 'nullable|string|in:employed,studying,business,other',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'linkedin_url' => 'nullable|url|max:255',
            'job_title' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'location_city' => 'nullable|string|max:255',
            'location_state' => 'nullable|string|max:255',
            'location_country' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $alumni = Alumni::create($validator->validated());

        return response()->json(['success' => true, 'data' => $alumni], 201);
    }

    public function show($id)
    {
        $alumni = Alumni::with(['batch', 'achievements', 'contributions'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $alumni]);
    }

    public function update(Request $request, $id)
    {
        $alumni = Alumni::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'student_id' => 'nullable|integer|exists:students,id',
            'batch_id' => 'nullable|integer|exists:alumni_batches,id',
            'name' => 'sometimes|required|string|max:255',
            'admission_no' => 'nullable|string|max:100',
            'pass_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'leaving_reason' => 'nullable|string|max:255',
            'current_status' => 'nullable|string|in:employed,studying,business,other',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'linkedin_url' => 'nullable|url|max:255',
            'job_title' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'location_city' => 'nullable|string|max:255',
            'location_state' => 'nullable|string|max:255',
            'location_country' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $alumni->update($validator->validated());

        return response()->json(['success' => true, 'data' => $alumni]);
    }

    public function destroy($id)
    {
        $alumni = Alumni::findOrFail($id);
        $alumni->delete();
        return response()->json(['success' => true]);
    }

    public function batches(Request $request)
    {
        $query = AlumniBatch::query();
        if ($request->filled('year')) {
            $year = (int)$request->get('year');
            $query->where('year_start', '<=', $year)->where('year_end', '>=', $year);
        }
        return response()->json(['success' => true, 'data' => $query->orderByDesc('year_start')->get()]);
    }

    public function storeBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:100',
            'year_start' => 'required|integer|min:1900|max:' . date('Y'),
            'year_end' => 'nullable|integer|min:1900|max:' . date('Y'),
            'description' => 'nullable|string|max:1000',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if ($request->filled('year_end') && (int)$request->get('year_end') < (int)$request->get('year_start')) {
            return response()->json(['success' => false, 'errors' => ['year_end' => ['year_end must be >= year_start']]], 422);
        }

        $batch = AlumniBatch::create($validator->validated());
        return response()->json(['success' => true, 'data' => $batch], 201);
    }

    public function achievements($alumniId)
    {
        $alumni = Alumni::findOrFail($alumniId);
        $achievements = $alumni->achievements()->orderByDesc('achieved_on')->get();
        return response()->json(['success' => true, 'data' => $achievements]);
    }

    public function storeAchievement(Request $request, $alumniId)
    {
        $alumni = Alumni::findOrFail($alumniId);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'achieved_on' => 'nullable|date',
            'category' => 'nullable|string|max:100',
            'url' => 'nullable|url|max:255',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $achievement = new AlumniAchievement($validator->validated());
        $achievement->alumni_id = $alumni->id;
        $achievement->created_by = Auth::id();
        $achievement->save();

        $alumni->increment('achievements_count');

        return response()->json(['success' => true, 'data' => $achievement], 201);
    }

    public function contributions($alumniId)
    {
        $alumni = Alumni::findOrFail($alumniId);
        $contributions = $alumni->contributions()->orderByDesc('contribution_date')->get();
        return response()->json(['success' => true, 'data' => $contributions]);
    }

    public function storeContribution(Request $request, $alumniId)
    {
        $alumni = Alumni::findOrFail($alumniId);

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:donation,volunteer,sponsorship',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'contribution_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $contribution = new AlumniContribution($validator->validated());
        $contribution->alumni_id = $alumni->id;
        $contribution->recorded_by = Auth::id();
        $contribution->save();

        if ($contribution->amount) {
            $alumni->contributions_total = (float)$alumni->contributions_total + (float)$contribution->amount;
            $alumni->save();
        }

        return response()->json(['success' => true, 'data' => $contribution], 201);
    }

    public function events(Request $request)
    {
        $query = AlumniEvent::query();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('from')) {
            $query->whereDate('start_date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('end_date', '<=', $request->date('to'));
        }
        $events = $query->orderByDesc('start_date')->paginate((int)($request->get('per_page', 15)));
        return response()->json(['success' => true, 'data' => $events]);
    }

    public function storeEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:draft,published,archived',
            'registration_url' => 'nullable|url|max:255',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $event = new AlumniEvent($validator->validated());
        $event->created_by = Auth::id();
        $event->save();

        return response()->json(['success' => true, 'data' => $event], 201);
    }

    public function showEvent($eventId)
    {
        $event = AlumniEvent::findOrFail($eventId);
        return response()->json(['success' => true, 'data' => $event]);
    }

    public function registerEvent(Request $request, $eventId)
    {
        $event = AlumniEvent::findOrFail($eventId);

        $validator = Validator::make($request->all(), [
            'alumni_id' => 'nullable|integer|exists:alumni,id',
            'name' => 'required_without:alumni_id|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $existing = AlumniEventRegistration::where('event_id', $event->id)
            ->where('email', $data['email'])
            ->first();
        if ($existing) {
            return response()->json(['success' => false, 'errors' => ['email' => ['Already registered for this event']]], 409);
        }

        $registration = new AlumniEventRegistration($data);
        $registration->event_id = $event->id;
        $registration->checked_in = false;
        $registration->save();

        return response()->json(['success' => true, 'data' => $registration], 201);
    }

    public function checkin(Request $request, $eventId)
    {
        $event = AlumniEvent::findOrFail($eventId);

        $validator = Validator::make($request->all(), [
            'registration_id' => 'required|integer|exists:alumni_event_registrations,id',
            'checked_in' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $registration = AlumniEventRegistration::where('event_id', $event->id)
            ->where('id', $request->get('registration_id'))
            ->firstOrFail();

        $registration->checked_in = (bool)$request->get('checked_in');
        $registration->save();

        return response()->json(['success' => true, 'data' => $registration]);
    }
}