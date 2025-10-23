@extends('layouts.app')

@section('title', 'Teacher Portfolio - ' . ($teacher->user->name ?? 'Teacher'))

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">Teacher Portfolio</h2>
                <p class="text-muted mb-0">Build and track professional experience, certifications, skills, and reviews</p>
            </div>
            <div>
                <a href="{{ route('teachers.show', $teacher->id) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Profile
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Experience Summary -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Experience Summary</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('teachers.portfolio.experience.store', $teacher->id) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Total Years</label>
                        <input type="number" name="total_years" class="form-control" min="0" value="{{ old('total_years', $teacher->experience->total_years ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Primary Specialization</label>
                        <input type="text" name="primary_specialization" class="form-control" value="{{ old('primary_specialization', $teacher->experience->primary_specialization ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Specializations</label>
                        @php($availableSkills = \App\Models\Skill::orderBy('name')->get())
                        <select name="specializations[]" class="form-select" multiple>
                            @foreach($availableSkills as $skill)
                                @php($selectedSpecs = collect($teacher->experience->specializations ?? []))
                                <option value="{{ $skill->name }}" {{ $selectedSpecs->contains($skill->name) ? 'selected' : '' }}>{{ $skill->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Summary</label>
                        <textarea name="summary" class="form-control" rows="3">{{ old('summary', $teacher->experience->summary ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Achievements</label>
                        <textarea name="achievements" class="form-control" rows="3">{{ old('achievements', $teacher->experience->achievements ?? '') }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Last Promotion Date</label>
                        <input type="date" name="last_promotion_date" class="form-control" value="{{ old('last_promotion_date', optional($teacher->experience->last_promotion_date ?? null)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Portfolio Status</label>
                        <select name="portfolio_status" class="form-select">
                            @php($status = old('portfolio_status', $teacher->experience->portfolio_status ?? ''))
                            <option value="">Select status</option>
                            <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="complete" {{ $status === 'complete' ? 'selected' : '' }}>Complete</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Summary</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Employment History -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Employment History</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('teachers.portfolio.employment.store', $teacher->id) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Organization Name</label>
                                <input type="text" name="organization_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role Title</label>
                                <input type="text" name="role_title" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Responsibilities</label>
                                <textarea name="responsibilities" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Subjects Taught</label>
                                @php($subjects = \App\Models\Subject::select('name')->orderBy('name')->get())
                                <select name="subjects_taught[]" multiple class="form-select">
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->name }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Document Path (optional)</label>
                                <input type="text" name="document_path" class="form-control" placeholder="storage/path/to/file.pdf">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Verification Notes</label>
                                <input type="text" name="verification_notes" class="form-control">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus me-2"></i>Add Employment</button>
                        </div>
                    </form>

                    <hr>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Organization</th>
                                    <th>Role</th>
                                    <th>Duration</th>
                                    <th>Subjects</th>
                                    <th>Verified</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($teacher->employmentHistories as $emp)
                                <tr>
                                    <td>{{ $emp->organization_name }}</td>
                                    <td>{{ $emp->role_title }}</td>
                                    <td>{{ $emp->start_date?->format('M Y') }} - {{ $emp->end_date?->format('M Y') ?? 'Present' }}</td>
                                    <td>
                                        @foreach(($emp->subjects_taught ?? []) as $s)
                                            <span class="badge bg-secondary me-1">{{ $s }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if($emp->verified)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('teachers.portfolio.employment.delete', [$teacher->id, $emp->id]) }}" onsubmit="return confirm('Delete this record?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-muted">No employment records yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certifications -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-certificate me-2"></i>Certifications</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('teachers.portfolio.certification.store', $teacher->id) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Certificate Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Issuing Organization</label>
                                <input type="text" name="issuing_organization" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Issue Date</label>
                                <input type="date" name="issue_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Certificate Code</label>
                                <input type="text" name="certificate_code" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Score</label>
                                <input type="number" name="score" step="0.01" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">License Number</label>
                                <input type="text" name="license_number" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Document Path (optional)</label>
                                <input type="text" name="document_path" class="form-control" placeholder="storage/path/to/file.pdf">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus me-2"></i>Add Certification</button>
                        </div>
                    </form>

                    <hr>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Certificate</th>
                                    <th>Issuer</th>
                                    <th>Issue Date</th>
                                    <th>Expiry</th>
                                    <th>Verified</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($teacher->certifications as $cert)
                                <tr>
                                    <td>{{ $cert->name }}</td>
                                    <td>{{ $cert->issuing_organization }}</td>
                                    <td>{{ $cert->issue_date?->format('M d, Y') }}</td>
                                    <td>{{ $cert->expiry_date?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td>
                                        @if($cert->verified)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('teachers.portfolio.certification.delete', [$teacher->id, $cert->id]) }}" onsubmit="return confirm('Delete this certification?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-muted">No certifications yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Skills -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Subject Expertise & Skills</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('teachers.portfolio.skill.attach', $teacher->id) }}">
                        @csrf
                        @php($availableSkills = \App\Models\Skill::orderBy('name')->get())
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Skill</label>
                                <select name="skill_id" class="form-select" required>
                                    <option value="">Select skill</option>
                                    @foreach($availableSkills as $skill)
                                        <option value="{{ $skill->id }}">{{ $skill->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Proficiency Level</label>
                                <select name="proficiency_level" class="form-select" required>
                                    <option value="0">Beginner (0)</option>
                                    <option value="3">Intermediate (3)</option>
                                    <option value="6">Advanced (6)</option>
                                    <option value="9">Expert (9)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Years Experience</label>
                                <input type="number" name="years_experience" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Endorsements</label>
                                <input type="number" name="endorsements_count" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-4 d-flex align-items-center">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="verified" id="skillVerified" value="1">
                                    <label class="form-check-label" for="skillVerified">Verified</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus me-2"></i>Attach Skill</button>
                        </div>
                    </form>

                    <hr>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Skill</th>
                                    <th>Proficiency</th>
                                    <th>Years</th>
                                    <th>Verified</th>
                                    <th>Endorsements</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($teacher->skills as $skill)
                                <tr>
                                    <td>{{ $skill->name }}</td>
                                    <td>
                                        @php($level = (int)($skill->pivot->proficiency_level ?? 0))
                                        @php($label = $level >= 9 ? 'Expert' : ($level >= 6 ? 'Advanced' : ($level >= 3 ? 'Intermediate' : 'Beginner')))
                                        {{ $label }} ({{ $level }})
                                    </td>
                                    <td>{{ $skill->pivot->years_experience }}</td>
                                    <td>
                                        @if($skill->pivot->verified)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $skill->pivot->endorsements_count }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('teachers.portfolio.skill.detach', [$teacher->id, $skill->id]) }}" onsubmit="return confirm('Detach this skill?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-unlink"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-muted">No skills attached yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Reviews -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Performance Reviews</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('teachers.portfolio.performance-review.store', $teacher->id) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Period Start</label>
                                <input type="date" name="period_start" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Period End</label>
                                <input type="date" name="period_end" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Overall Score</label>
                                <input type="number" name="overall_score" step="0.01" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Promotion Recommended</label>
                                <select name="promotion_recommended" class="form-select">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Promotion Title</label>
                                <input type="text" name="promotion_title" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Increment Recommended</label>
                                <select name="increment_recommended" class="form-select">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Increment Amount</label>
                                <input type="number" name="increment_amount" step="0.01" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Comments</label>
                                <textarea name="comments" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus me-2"></i>Add Review</button>
                        </div>
                    </form>

                    <hr>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Score</th>
                                    <th>Promotion</th>
                                    <th>Increment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($teacher->performanceReviews as $rev)
                                <tr>
                                    <td>{{ $rev->period_start?->format('M d, Y') }} - {{ $rev->period_end?->format('M d, Y') }}</td>
                                    <td>{{ $rev->overall_score ?? '—' }}</td>
                                    <td>{{ $rev->promotion_recommended ? 'Yes' : 'No' }}</td>
                                    <td>
                                        @if($rev->increment_recommended)
                                            ₹{{ number_format($rev->increment_amount ?? 0, 2) }}
                                        @else
                                            No
                                        @endif
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('teachers.portfolio.performance-review.delete', [$teacher->id, $rev->id]) }}" onsubmit="return confirm('Delete this review?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-muted">No performance reviews yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection