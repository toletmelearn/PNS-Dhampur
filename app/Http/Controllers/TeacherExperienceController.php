<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\TeacherExperience;
use App\Models\EmploymentHistory;
use App\Models\Certification;
use App\Models\Skill;
use App\Models\PerformanceReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherExperienceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show teacher portfolio page
     */
    public function portfolio($teacherId)
    {
        $teacher = Teacher::with([
            'user',
            'experience',
            'employmentHistories' => function($q){ $q->orderBy('start_date', 'desc'); },
            'certifications' => function($q){ $q->orderBy('issue_date', 'desc'); },
            'performanceReviews' => function($q){ $q->orderBy('period_end', 'desc'); },
            'skills'
        ])->findOrFail($teacherId);

        return view('teachers.portfolio', compact('teacher'));
    }

    /**
     * Create or update TeacherExperience summary
     */
    public function storeExperience(Request $request, $teacherId)
    {
        $rules = [
            'total_years' => 'nullable|integer|min:0',
            'primary_specialization' => 'nullable|string|max:255',
            'specializations' => 'nullable|array',
            'summary' => 'nullable|string',
            'achievements' => 'nullable|string',
            'last_promotion_date' => 'nullable|date',
            'portfolio_status' => 'nullable|in:in_progress,complete',
        ];

        $data = $request->validate($rules);

        $teacher = Teacher::findOrFail($teacherId);
        $experience = TeacherExperience::updateOrCreate(
            ['teacher_id' => $teacher->id],
            array_merge($data, ['specializations' => $data['specializations'] ?? []])
        );

        return back()->with('success', 'Experience summary saved successfully.');
    }

    /**
     * Employment history CRUD
     */
    public function storeEmployment(Request $request, $teacherId)
    {
        $rules = [
            'organization_name' => 'required|string|max:255',
            'role_title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'responsibilities' => 'nullable|string',
            'subjects_taught' => 'nullable|array',
            'achievements' => 'nullable|string',
            'teacher_document_id' => 'nullable|exists:teacher_documents,id',
            'document_path' => 'nullable|string|max:2048',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'verified' => 'nullable|boolean',
            'verification_notes' => 'nullable|string',
        ];
        $data = $request->validate($rules);
        $teacher = Teacher::findOrFail($teacherId);
        $data['teacher_id'] = $teacher->id;
        EmploymentHistory::create($data);
        return back()->with('success', 'Employment history added.');
    }

    public function updateEmployment(Request $request, $teacherId, $id)
    {
        $employment = EmploymentHistory::where('teacher_id', $teacherId)->findOrFail($id);
        $rules = [
            'organization_name' => 'sometimes|string|max:255',
            'role_title' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'responsibilities' => 'nullable|string',
            'subjects_taught' => 'nullable|array',
            'achievements' => 'nullable|string',
            'teacher_document_id' => 'nullable|exists:teacher_documents,id',
            'document_path' => 'nullable|string|max:2048',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'verified' => 'nullable|boolean',
            'verification_notes' => 'nullable|string',
        ];
        $data = $request->validate($rules);
        $employment->update($data);
        return back()->with('success', 'Employment history updated.');
    }

    public function deleteEmployment($teacherId, $id)
    {
        $employment = EmploymentHistory::where('teacher_id', $teacherId)->findOrFail($id);
        $employment->delete();
        return back()->with('success', 'Employment history deleted.');
    }

    /**
     * Certification CRUD
     */
    public function storeCertification(Request $request, $teacherId)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'issuing_organization' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'certificate_code' => 'nullable|string|max:255',
            'score' => 'nullable|numeric|min:0',
            'teacher_document_id' => 'nullable|exists:teacher_documents,id',
            'document_path' => 'nullable|string|max:2048',
            'verified' => 'nullable|boolean',
            'license_number' => 'nullable|string|max:255',
        ];
        $data = $request->validate($rules);
        $data['teacher_id'] = $teacherId;
        Certification::create($data);
        return back()->with('success', 'Certification added.');
    }

    public function updateCertification(Request $request, $teacherId, $id)
    {
        $cert = Certification::where('teacher_id', $teacherId)->findOrFail($id);
        $rules = [
            'name' => 'sometimes|string|max:255',
            'issuing_organization' => 'sometimes|string|max:255',
            'issue_date' => 'sometimes|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'certificate_code' => 'nullable|string|max:255',
            'score' => 'nullable|numeric|min:0',
            'teacher_document_id' => 'nullable|exists:teacher_documents,id',
            'document_path' => 'nullable|string|max:2048',
            'verified' => 'nullable|boolean',
            'license_number' => 'nullable|string|max:255',
        ];
        $data = $request->validate($rules);
        $cert->update($data);
        return back()->with('success', 'Certification updated.');
    }

    public function deleteCertification($teacherId, $id)
    {
        $cert = Certification::where('teacher_id', $teacherId)->findOrFail($id);
        $cert->delete();
        return back()->with('success', 'Certification deleted.');
    }

    /**
     * Skills attachment
     */
    public function attachSkill(Request $request, $teacherId)
    {
        $rules = [
            'skill_id' => 'nullable|exists:skills,id',
            'name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'proficiency_level' => 'nullable|integer|min:0|max:10',
            'years_experience' => 'nullable|integer|min:0',
            'verified' => 'nullable|boolean',
            'endorsements_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ];
        $data = $request->validate($rules);
        $teacher = Teacher::findOrFail($teacherId);
        $skillId = $data['skill_id'] ?? null;
        if (!$skillId) {
            $skill = Skill::firstOrCreate(['name' => $data['name'] ?? 'Unspecified'], [
                'category' => $data['category'] ?? null,
                'description' => $data['description'] ?? null,
            ]);
            $skillId = $skill->id;
        }
        $teacher->skills()->syncWithoutDetaching([
            $skillId => [
                'proficiency_level' => $data['proficiency_level'] ?? 0,
                'years_experience' => $data['years_experience'] ?? 0,
                'verified' => $data['verified'] ?? false,
                'endorsements_count' => $data['endorsements_count'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]
        ]);
        return back()->with('success', 'Skill attached to teacher.');
    }

    public function detachSkill($teacherId, $skillId)
    {
        $teacher = Teacher::findOrFail($teacherId);
        $teacher->skills()->detach($skillId);
        return back()->with('success', 'Skill detached from teacher.');
    }

    /**
     * Performance review CRUD
     */
    public function storePerformanceReview(Request $request, $teacherId)
    {
        $rules = [
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'reviewer_id' => 'nullable|exists:users,id',
            'ratings' => 'nullable|array',
            'overall_score' => 'nullable|numeric|min:0',
            'comments' => 'nullable|string',
            'recommendations' => 'nullable|array',
            'promotion_recommended' => 'nullable|boolean',
            'promotion_title' => 'nullable|string|max:255',
            'increment_recommended' => 'nullable|boolean',
            'increment_amount' => 'nullable|numeric|min:0',
            'teacher_document_id' => 'nullable|exists:teacher_documents,id',
        ];
        $data = $request->validate($rules);
        $data['teacher_id'] = $teacherId;
        PerformanceReview::create($data);
        return back()->with('success', 'Performance review added.');
    }

    public function updatePerformanceReview(Request $request, $teacherId, $id)
    {
        $review = PerformanceReview::where('teacher_id', $teacherId)->findOrFail($id);
        $rules = [
            'period_start' => 'sometimes|date',
            'period_end' => 'sometimes|date|after_or_equal:period_start',
            'reviewer_id' => 'nullable|exists:users,id',
            'ratings' => 'nullable|array',
            'overall_score' => 'nullable|numeric|min:0',
            'comments' => 'nullable|string',
            'recommendations' => 'nullable|array',
            'promotion_recommended' => 'nullable|boolean',
            'promotion_title' => 'nullable|string|max:255',
            'increment_recommended' => 'nullable|boolean',
            'increment_amount' => 'nullable|numeric|min:0',
            'teacher_document_id' => 'nullable|exists:teacher_documents,id',
        ];
        $data = $request->validate($rules);
        $review->update($data);
        return back()->with('success', 'Performance review updated.');
    }

    public function deletePerformanceReview($teacherId, $id)
    {
        $review = PerformanceReview::where('teacher_id', $teacherId)->findOrFail($id);
        $review->delete();
        return back()->with('success', 'Performance review deleted.');
    }
}