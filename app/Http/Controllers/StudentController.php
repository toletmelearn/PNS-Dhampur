<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    // GET /api/students or /students
    public function index(Request $request)
    {
        $q = Student::query();

        // optional filters: class_id, status, search by name/admission_no
        if ($request->filled('class_id')) {
            $q->where('class_id', $request->class_id);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $q->where(function($w) use ($search) {
                $w->where('name', 'like', "%$search%")
                  ->orWhere('admission_no', 'like', "%$search%")
                  ->orWhere('aadhaar', 'like', "%$search%");
            });
        }

        $students = $q->orderBy('name')->paginate(25);

        // Return JSON for API requests, view for web requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($students);
        }

        $classes = ClassModel::all();
        return view('students.index', compact('students', 'classes'));
    }

    // POST /api/students
    public function store(Request $request)
    {
        $data = $request->validate([
            'admission_no' => 'nullable|string|unique:students,admission_no',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'aadhaar' => 'nullable|string|max:20|unique:students,aadhaar',
            'class' => 'nullable|integer|exists:class_models,id',
            'status' => ['nullable', Rule::in(['active','left','alumni'])],
            'gender' => 'nullable|in:male,female,other',
            'roll_number' => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);
        
        $data['name'] = $request->first_name . ' ' . $request->last_name;
        $data['dob'] = $request->date_of_birth;
        $data['class_id'] = $request->class;
        $data['meta'] = [
            'gender' => $request->gender,
            'roll_number' => $request->roll_number,
            'contact_number' => $request->contact_number,
            'email' => $request->email,
            'address' => $request->address,
        ];
        
        // handle document uploads if any (birth_cert, aadhaar, other_docs[] )
        $documents = [];
        if ($request->hasFile('birth_cert')) {
            $documents['birth_cert'] = $this->storeFile($request->file('birth_cert'), 'students/documents');
        }
        if ($request->hasFile('aadhaar_file')) {
            $documents['aadhaar'] = $this->storeFile($request->file('aadhaar_file'), 'students/documents');
        }
        if ($request->hasFile('other_docs')) {
            $other = [];
            foreach ($request->file('other_docs') as $f) {
                $other[] = $this->storeFile($f, 'students/documents');
            }
            $documents['other'] = $other;
        }

        $student = Student::create(array_merge($data, [
            'documents' => $documents,
            'verification_status' => 'pending'
        ]));

        // Return JSON for API, redirect for web
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($student, 201);
        }
        
        return redirect()->route('students.index')->with('success', 'Student created successfully');
    }

    // GET /api/students/{id} or /students/{id}
    public function show(Request $request, Student $student)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($student);
        }
        
        return view('students.show', compact('student'));
    }

    // PUT /api/students/{id}
    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'admission_no' => ['nullable','string', Rule::unique('students','admission_no')->ignore($student->id)],
            'name' => 'sometimes|required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'aadhaar' => ['nullable','string', Rule::unique('students','aadhaar')->ignore($student->id)],
            'class_id' => 'nullable|integer|exists:class_models,id',
            'status' => ['nullable', Rule::in(['active','left','alumni'])],
            'meta' => 'nullable|array',
        ]);

        // files - optional replace
        $documents = $student->documents ?? [];

        if ($request->hasFile('birth_cert')) {
            if(isset($documents['birth_cert'])) Storage::delete($documents['birth_cert']);
            $documents['birth_cert'] = $this->storeFile($request->file('birth_cert'), 'students/documents');
        }
        if ($request->hasFile('aadhaar_file')) {
            if(isset($documents['aadhaar'])) Storage::delete($documents['aadhaar']);
            $documents['aadhaar'] = $this->storeFile($request->file('aadhaar_file'), 'students/documents');
        }
        if ($request->hasFile('other_docs')) {
            $other = $documents['other'] ?? [];
            foreach ($request->file('other_docs') as $f) {
                $other[] = $this->storeFile($f, 'students/documents');
            }
            $documents['other'] = $other;
        }

        $student->update(array_merge($data, ['documents' => $documents]));

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($student);
        }
        
        return redirect()->route('students.index')->with('success', 'Student updated successfully');
    }

    // DELETE /api/students/{id}
    public function destroy(Request $request, Student $student)
    {
        if ($student->documents) {
            foreach ($student->documents as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $p) Storage::delete($p);
                } else {
                    Storage::delete($v);
                }
            }
        }
        $student->delete();
        
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Deleted']);
        }
        
        return redirect()->route('students.index')->with('success', 'Student deleted successfully');
    }

    // ... rest of your methods (verify, storeFile, normalize) remain the same ...
    // POST /api/students/{id}/verify
    public function verify(Request $request, Student $student)
    {
        $payload = $request->validate([
            'verified_data' => 'required|array',
            'verified_data.name' => 'sometimes|string',
            'verified_data.father_name' => 'sometimes|string',
            'verified_data.mother_name' => 'sometimes|string',
            'verified_data.dob' => 'sometimes|date',
            'verified_data.aadhaar' => 'sometimes|string',
            'force' => 'nullable|boolean'
        ]);

        $verifiedData = $payload['verified_data'];

        $mismatches = [];
        $fieldsToCheck = ['name','father_name','mother_name','dob','aadhaar'];

        foreach ($fieldsToCheck as $f) {
            if (isset($verifiedData[$f])) {
                $left = $this->normalize($student->$f);
                $right = $this->normalize($verifiedData[$f]);
                if ($left !== null && $right !== null && $left !== $right) {
                    $mismatches[$f] = ['student' => $student->$f, 'doc' => $verifiedData[$f]];
                }
            }
        }

        if (count($mismatches) === 0 || ($payload['force'] ?? false)) {
            $student->update([
                'documents_verified_data' => $verifiedData,
                'verification_status' => 'verified'
            ]);
            return response()->json(['status' => 'verified', 'mismatches' => $mismatches]);
        }

        $student->update([
            'documents_verified_data' => $verifiedData,
            'verification_status' => 'mismatch'
        ]);

        return response()->json(['status' => 'mismatch', 'mismatches' => $mismatches], 422);
    }

    // helper - store a file and return path
    protected function storeFile($file, $dir)
    {
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($dir, $filename, 'public');
        return $path;
    }

    protected function normalize($value)
    {
        if ($value === null) return null;
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string)$value)));
    }
}