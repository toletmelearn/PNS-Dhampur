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
        $q = Student::with('classModel'); // Load class relationship

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

        $students = $q->orderBy('name')->paginate(15); // Reduced to 15 per page for better UX

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
        try {
            // Enhanced validation with custom messages
            $data = $request->validate([
                'first_name' => 'required|string|max:100|regex:/^[a-zA-Z\s]+$/',
                'last_name' => 'required|string|max:100|regex:/^[a-zA-Z\s]+$/',
                'admission_no' => 'nullable|string|unique:students,admission_no|max:20',
                'father_name' => 'nullable|string|max:255|regex:/^[a-zA-Z\s]+$/',
                'mother_name' => 'nullable|string|max:255|regex:/^[a-zA-Z\s]+$/',
                'date_of_birth' => 'nullable|date|before:today|after:1900-01-01',
                'gender' => 'nullable|in:male,female,other',
                'aadhaar' => 'nullable|string|unique:students,aadhaar|regex:/^[0-9]{12}$/',
                'class' => 'nullable|integer|exists:class_models,id',
                'roll_number' => 'nullable|string|max:20',
                'contact_number' => 'nullable|string|regex:/^[\+]?[0-9\s\-\(\)]{10,15}$/',
                'email' => 'nullable|email|unique:students,email|max:255',
                'address' => 'nullable|string|max:500',
                'status' => ['nullable', Rule::in(['active','inactive','left','alumni'])],
                'meta' => 'nullable|array',
                
                // File validation
                'birth_cert' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'aadhaar_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'other_docs.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ], [
                'first_name.required' => 'First name is required.',
                'first_name.regex' => 'First name should only contain letters and spaces.',
                'last_name.required' => 'Last name is required.',
                'last_name.regex' => 'Last name should only contain letters and spaces.',
                'admission_no.unique' => 'This admission number is already taken.',
                'aadhaar.unique' => 'This Aadhaar number is already registered.',
                'aadhaar.regex' => 'Aadhaar number must be exactly 12 digits.',
                'date_of_birth.before' => 'Date of birth must be before today.',
                'date_of_birth.after' => 'Please enter a valid date of birth.',
                'class.exists' => 'Selected class does not exist.',
                'contact_number.regex' => 'Please enter a valid contact number.',
                'email.unique' => 'This email address is already registered.',
                'birth_cert.mimes' => 'Birth certificate must be a PDF, JPG, JPEG, or PNG file.',
                'birth_cert.max' => 'Birth certificate file size must not exceed 2MB.',
                'aadhaar_file.mimes' => 'Aadhaar file must be a PDF, JPG, JPEG, or PNG file.',
                'aadhaar_file.max' => 'Aadhaar file size must not exceed 2MB.',
                'other_docs.*.mimes' => 'Document files must be PDF, JPG, JPEG, or PNG.',
                'other_docs.*.max' => 'Each document file must not exceed 2MB.',
            ]);

            // Generate admission number if not provided
            if (empty($data['admission_no'])) {
                $data['admission_no'] = $this->generateAdmissionNumber();
            }

            // Combine first and last name
            $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);
            
            // Map form fields to database fields
            $studentData = [
                'admission_no' => $data['admission_no'],
                'name' => $data['name'],
                'father_name' => $data['father_name'] ?? null,
                'mother_name' => $data['mother_name'] ?? null,
                'dob' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'aadhaar' => $data['aadhaar'] ?? null,
                'class_id' => $data['class'] ?? null,
                'roll_number' => $data['roll_number'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? 'active',
                'meta' => $data['meta'] ?? [],
            ];

            // Handle secure file uploads
            $documents = [];
            $uploadErrors = [];

            if ($request->hasFile('birth_cert')) {
                try {
                    $documents['birth_cert'] = $this->storeFileSecurely($request->file('birth_cert'), 'students/documents');
                } catch (\Exception $e) {
                    $uploadErrors[] = 'Failed to upload birth certificate: ' . $e->getMessage();
                }
            }

            if ($request->hasFile('aadhaar_file')) {
                try {
                    $documents['aadhaar'] = $this->storeFileSecurely($request->file('aadhaar_file'), 'students/documents');
                } catch (\Exception $e) {
                    $uploadErrors[] = 'Failed to upload Aadhaar file: ' . $e->getMessage();
                }
            }

            if ($request->hasFile('other_docs')) {
                $other = [];
                foreach ($request->file('other_docs') as $index => $file) {
                    try {
                        $other[] = $this->storeFileSecurely($file, 'students/documents');
                    } catch (\Exception $e) {
                        $uploadErrors[] = "Failed to upload document " . ($index + 1) . ": " . $e->getMessage();
                    }
                }
                if (!empty($other)) {
                    $documents['other'] = $other;
                }
            }

            // If there are upload errors, return with errors
            if (!empty($uploadErrors)) {
                return back()->withErrors($uploadErrors)->withInput();
            }

            $studentData['documents'] = $documents;
            $studentData['verification_status'] = 'pending';

            // Create student record
            $student = Student::create($studentData);

            // Log the creation
            \Log::info('Student created successfully', [
                'student_id' => $student->id,
                'admission_no' => $student->admission_no,
                'name' => $student->name,
                'created_by' => auth()->id() ?? 'system'
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Student created successfully',
                    'student' => $student
                ], 201);
            }
            
            return redirect()->route('students.index')->with('success', 'Student "' . $student->name . '" has been successfully registered with admission number: ' . $student->admission_no);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            // Log the error
            \Log::error('Student creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['birth_cert', 'aadhaar_file', 'other_docs'])
            ]);

            // Clean up any uploaded files if student creation failed
            if (!empty($documents)) {
                foreach ($documents as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $path) {
                            Storage::delete($path);
                        }
                    } else {
                        Storage::delete($value);
                    }
                }
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create student. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                ], 500);
            }
            
            return back()->with('error', 'Failed to create student. Please try again.')->withInput();
        }
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

    // Enhanced secure file storage method
    protected function storeFileSecurely($file, $dir)
    {
        // Validate file type and size
        $allowedMimes = ['pdf', 'jpg', 'jpeg', 'png'];
        $maxSize = 2048; // 2MB in KB
        
        if (!in_array($file->getClientOriginalExtension(), $allowedMimes)) {
            throw new \Exception('Invalid file type. Only PDF, JPG, JPEG, and PNG files are allowed.');
        }
        
        if ($file->getSize() > ($maxSize * 1024)) {
            throw new \Exception('File size exceeds 2MB limit.');
        }
        
        // Generate secure filename
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $sanitizedName = Str::slug($originalName);
        $timestamp = time();
        $randomString = Str::random(8);
        $filename = "{$sanitizedName}-{$timestamp}-{$randomString}.{$extension}";
        
        // Ensure directory exists
        $fullDir = storage_path("app/public/{$dir}");
        if (!file_exists($fullDir)) {
            mkdir($fullDir, 0755, true);
        }
        
        // Store file
        $path = $file->storeAs($dir, $filename, 'public');
        
        if (!$path) {
            throw new \Exception('Failed to store file.');
        }
        
        return $path;
    }

    // Generate unique admission number
    protected function generateAdmissionNumber()
    {
        $year = date('Y');
        $prefix = 'PNS' . substr($year, -2);
        
        // Get the last admission number for this year
        $lastStudent = Student::where('admission_no', 'LIKE', $prefix . '%')
                             ->orderBy('admission_no', 'desc')
                             ->first();
        
        if ($lastStudent) {
            // Extract the number part and increment
            $lastNumber = intval(substr($lastStudent->admission_no, -4));
            $newNumber = $lastNumber + 1;
        } else {
            // Start from 1 for the first student of the year
            $newNumber = 1;
        }
        
        // Format with leading zeros
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    protected function normalize($value)
    {
        if ($value === null) return null;
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string)$value)));
    }

    /**
     * Get classes for dropdown
     */
    public function getClasses()
    {
        $classes = ClassModel::select('id', 'name', 'section')
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('section')
            ->get();
        
        return response()->json($classes);
    }

    /**
     * Show the form for creating a new student
     */
    public function create()
    {
        $classes = ClassModel::select('id', 'name', 'section')
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('section')
            ->get();
        
        return view('students.create', compact('classes'));
    }
}