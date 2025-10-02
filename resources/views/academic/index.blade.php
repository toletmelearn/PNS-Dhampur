@extends('layouts.app')

@section('title', 'Academic Management')

@section('content')
<style>
    .academic-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 20px;
        color: white;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .academic-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        border-left: 4px solid;
        transition: all 0.3s ease;
        margin-bottom: 20px;
    }
    
    .stats-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .stats-card.exams { border-left-color: #e74c3c; }
    .stats-card.results { border-left-color: #2ecc71; }
    .stats-card.subjects { border-left-color: #3498db; }
    .stats-card.syllabus { border-left-color: #f39c12; }
    
    .stats-number {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .stats-label {
        color: #666;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .exam-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        border-left: 4px solid #3498db;
        transition: all 0.3s ease;
    }
    
    .exam-card:hover {
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        transform: translateX(5px);
    }
    
    .exam-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .status-upcoming { background: #fff3cd; color: #856404; }
    .status-ongoing { background: #d4edda; color: #155724; }
    .status-completed { background: #d1ecf1; color: #0c5460; }
    
    .subject-badge {
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        margin: 2px;
        display: inline-block;
    }
    
    .grade-badge {
        padding: 8px 16px;
        border-radius: 25px;
        font-weight: bold;
        font-size: 0.9rem;
        margin: 2px;
        display: inline-block;
    }
    
    .grade-a { background: #d4edda; color: #155724; }
    .grade-b { background: #cce5ff; color: #004085; }
    .grade-c { background: #fff3cd; color: #856404; }
    .grade-d { background: #f8d7da; color: #721c24; }
    
    .chart-container {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        height: 400px;
    }
    
    .quick-action-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 25px;
        font-weight: bold;
        transition: all 0.3s ease;
        margin: 5px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    
    .quick-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        color: white;
    }
    
    .syllabus-progress {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        border-left: 4px solid #28a745;
    }
    
    .progress-bar-custom {
        height: 8px;
        border-radius: 4px;
        background: #e9ecef;
        overflow: hidden;
        margin-top: 10px;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #28a745, #20c997);
        transition: width 0.3s ease;
    }
    
    .result-table {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .result-table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        padding: 15px;
        border: none;
    }
    
    .result-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }
    
    .result-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .modal-header-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.5rem 0.5rem 0 0;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 10px 30px;
        border-radius: 25px;
        font-weight: bold;
        transition: all 0.3s ease;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
</style>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="academic-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="fas fa-graduation-cap me-3"></i>Academic Management</h2>
                <p class="mb-0">Manage exams, results, curriculum, and academic activities</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-light btn-lg" onclick="generateAcademicReport()">
                    <i class="fas fa-chart-line me-2"></i>Academic Report
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="stats-card exams">
                <div class="stats-number" id="totalExams">24</div>
                <div class="stats-label">Total Exams</div>
                <small class="text-muted">This Academic Year</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card results">
                <div class="stats-number" id="publishedResults">18</div>
                <div class="stats-label">Published Results</div>
                <small class="text-muted">Results Declared</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card subjects">
                <div class="stats-number" id="totalSubjects">15</div>
                <div class="stats-label">Active Subjects</div>
                <small class="text-muted">All Classes</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card syllabus">
                <div class="stats-number" id="syllabusProgress">78%</div>
                <div class="stats-label">Syllabus Completed</div>
                <small class="text-muted">Average Progress</small>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <button class="quick-action-btn" onclick="scheduleExam()">
                        <i class="fas fa-calendar-plus me-2"></i>Schedule Exam
                    </button>
                    <button class="quick-action-btn" onclick="publishResults()">
                        <i class="fas fa-trophy me-2"></i>Publish Results
                    </button>
                    <button class="quick-action-btn" onclick="manageSyllabus()">
                        <i class="fas fa-book me-2"></i>Manage Syllabus
                    </button>
                    <button class="quick-action-btn" onclick="generateMarksheet()">
                        <i class="fas fa-certificate me-2"></i>Generate Marksheet
                    </button>
                    <button class="quick-action-btn" onclick="viewAnalytics()">
                        <i class="fas fa-chart-bar me-2"></i>View Analytics
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Upcoming Exams -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-calendar-check me-2"></i>Upcoming Exams</h5>
                </div>
                <div class="card-body">
                    <div class="exam-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">Mathematics - Class X</h6>
                                <p class="text-muted mb-2">Unit Test - Algebra & Geometry</p>
                                <small><i class="fas fa-clock me-1"></i>March 15, 2024 - 10:00 AM</small>
                            </div>
                            <span class="exam-status status-upcoming">Upcoming</span>
                        </div>
                    </div>
                    
                    <div class="exam-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">Science - Class IX</h6>
                                <p class="text-muted mb-2">Monthly Test - Physics & Chemistry</p>
                                <small><i class="fas fa-clock me-1"></i>March 18, 2024 - 2:00 PM</small>
                            </div>
                            <span class="exam-status status-upcoming">Upcoming</span>
                        </div>
                    </div>
                    
                    <div class="exam-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">English - Class VIII</h6>
                                <p class="text-muted mb-2">Term Exam - Literature & Grammar</p>
                                <small><i class="fas fa-clock me-1"></i>March 20, 2024 - 9:00 AM</small>
                            </div>
                            <span class="exam-status status-ongoing">Ongoing</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Syllabus Progress -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-book-open me-2"></i>Syllabus Progress</h5>
                </div>
                <div class="card-body">
                    <div class="syllabus-progress">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Mathematics - Class X</h6>
                                <small class="text-muted">Chapters 1-8 of 12</small>
                            </div>
                            <span class="badge bg-success">85%</span>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" style="width: 85%"></div>
                        </div>
                    </div>
                    
                    <div class="syllabus-progress">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Science - Class IX</h6>
                                <small class="text-muted">Chapters 1-6 of 10</small>
                            </div>
                            <span class="badge bg-warning">60%</span>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" style="width: 60%"></div>
                        </div>
                    </div>
                    
                    <div class="syllabus-progress">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">English - Class VIII</h6>
                                <small class="text-muted">Chapters 1-9 of 10</small>
                            </div>
                            <span class="badge bg-success">90%</span>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" style="width: 90%"></div>
                        </div>
                    </div>
                    
                    <div class="syllabus-progress">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Social Studies - Class VII</h6>
                                <small class="text-muted">Chapters 1-5 of 8</small>
                            </div>
                            <span class="badge bg-info">75%</span>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" style="width: 75%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="chart-container">
                <h5 class="mb-3"><i class="fas fa-chart-line me-2"></i>Exam Performance Trends</h5>
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Subject-wise Results Distribution</h5>
                <canvas id="resultsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Results -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-trophy me-2"></i>Recent Results</h5>
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#publishResultModal">
                        <i class="fas fa-plus me-2"></i>Publish New Result
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table result-table" id="resultsTable">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Students</th>
                                    <th>Average</th>
                                    <th>Pass Rate</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong>Unit Test 1</strong><br>
                                        <small class="text-muted">Mathematics</small>
                                    </td>
                                    <td><span class="badge bg-primary">Class X</span></td>
                                    <td><span class="subject-badge">Mathematics</span></td>
                                    <td>March 10, 2024</td>
                                    <td>45</td>
                                    <td><span class="grade-badge grade-a">85%</span></td>
                                    <td><span class="badge bg-success">92%</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewResult(1)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="downloadResult(1)">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Monthly Test</strong><br>
                                        <small class="text-muted">Science</small>
                                    </td>
                                    <td><span class="badge bg-info">Class IX</span></td>
                                    <td><span class="subject-badge">Science</span></td>
                                    <td>March 8, 2024</td>
                                    <td>38</td>
                                    <td><span class="grade-badge grade-b">78%</span></td>
                                    <td><span class="badge bg-warning">87%</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewResult(2)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="downloadResult(2)">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Term Exam</strong><br>
                                        <small class="text-muted">English</small>
                                    </td>
                                    <td><span class="badge bg-success">Class VIII</span></td>
                                    <td><span class="subject-badge">English</span></td>
                                    <td>March 5, 2024</td>
                                    <td>42</td>
                                    <td><span class="grade-badge grade-a">88%</span></td>
                                    <td><span class="badge bg-success">95%</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewResult(3)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="downloadResult(3)">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Exam Modal -->
<div class="modal fade" id="scheduleExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Schedule New Exam</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleExamForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Exam Name</label>
                                <input type="text" class="form-control" name="exam_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Exam Type</label>
                                <select class="form-control" name="exam_type" required>
                                    <option value="">Select Type</option>
                                    <option value="unit_test">Unit Test</option>
                                    <option value="monthly_test">Monthly Test</option>
                                    <option value="term_exam">Term Exam</option>
                                    <option value="final_exam">Final Exam</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Class</label>
                                <select class="form-control" name="class" required>
                                    <option value="">Select Class</option>
                                    <option value="I">Class I</option>
                                    <option value="II">Class II</option>
                                    <option value="III">Class III</option>
                                    <option value="IV">Class IV</option>
                                    <option value="V">Class V</option>
                                    <option value="VI">Class VI</option>
                                    <option value="VII">Class VII</option>
                                    <option value="VIII">Class VIII</option>
                                    <option value="IX">Class IX</option>
                                    <option value="X">Class X</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <select class="form-control" name="subject" required>
                                    <option value="">Select Subject</option>
                                    <option value="mathematics">Mathematics</option>
                                    <option value="science">Science</option>
                                    <option value="english">English</option>
                                    <option value="hindi">Hindi</option>
                                    <option value="social_studies">Social Studies</option>
                                    <option value="computer">Computer</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Exam Date</label>
                                <input type="date" class="form-control" name="exam_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Exam Time</label>
                                <input type="time" class="form-control" name="exam_time" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" name="duration" min="30" max="180" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Total Marks</label>
                                <input type="number" class="form-control" name="total_marks" min="10" max="100" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Syllabus/Topics</label>
                        <textarea class="form-control" name="syllabus" rows="3" placeholder="Enter exam syllabus or topics to be covered"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary-custom" onclick="saveExam()">
                    <i class="fas fa-save me-2"></i>Schedule Exam
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Publish Result Modal -->
<div class="modal fade" id="publishResultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title"><i class="fas fa-trophy me-2"></i>Publish Exam Result</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="publishResultForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Select Exam</label>
                                <select class="form-control" name="exam_id" required>
                                    <option value="">Select Exam</option>
                                    <option value="1">Mathematics Unit Test - Class X</option>
                                    <option value="2">Science Monthly Test - Class IX</option>
                                    <option value="3">English Term Exam - Class VIII</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Result Date</label>
                                <input type="date" class="form-control" name="result_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Result File</label>
                        <input type="file" class="form-control" name="result_file" accept=".xlsx,.csv" required>
                        <small class="text-muted">Upload Excel or CSV file with student results</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Result Summary</label>
                        <textarea class="form-control" name="summary" rows="3" placeholder="Enter result summary or remarks"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Class Average (%)</label>
                                <input type="number" class="form-control" name="class_average" min="0" max="100" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Pass Rate (%)</label>
                                <input type="number" class="form-control" name="pass_rate" min="0" max="100" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Highest Score</label>
                                <input type="number" class="form-control" name="highest_score" min="0" max="100">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary-custom" onclick="publishResult()">
                    <i class="fas fa-upload me-2"></i>Publish Result
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#resultsTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[3, 'desc']]
    });
    
    // Initialize Charts
    initializeCharts();
    
    // Animate counters
    animateCounters();
});

function initializeCharts() {
    // Performance Trends Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Class Average',
                data: [75, 78, 82, 79, 85, 88],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Pass Rate',
                data: [85, 87, 90, 88, 92, 95],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    
    // Results Distribution Chart
    const resultsCtx = document.getElementById('resultsChart').getContext('2d');
    new Chart(resultsCtx, {
        type: 'doughnut',
        data: {
            labels: ['A Grade (90-100%)', 'B Grade (80-89%)', 'C Grade (70-79%)', 'D Grade (60-69%)', 'Below 60%'],
            datasets: [{
                data: [25, 35, 25, 10, 5],
                backgroundColor: [
                    '#28a745',
                    '#17a2b8',
                    '#ffc107',
                    '#fd7e14',
                    '#dc3545'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

function animateCounters() {
    const counters = document.querySelectorAll('.stats-number');
    counters.forEach(counter => {
        const target = parseInt(counter.textContent);
        const increment = target / 100;
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.textContent = target;
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current);
            }
        }, 20);
    });
}

function scheduleExam() {
    $('#scheduleExamModal').modal('show');
}

function saveExam() {
    const form = document.getElementById('scheduleExamForm');
    const formData = new FormData(form);
    
    // Here you would typically send the data to your backend
    console.log('Scheduling exam with data:', Object.fromEntries(formData));
    
    // Show success message
    alert('Exam scheduled successfully!');
    $('#scheduleExamModal').modal('hide');
    form.reset();
}

function publishResults() {
    $('#publishResultModal').modal('show');
}

function publishResult() {
    const form = document.getElementById('publishResultForm');
    const formData = new FormData(form);
    
    // Here you would typically send the data to your backend
    console.log('Publishing result with data:', Object.fromEntries(formData));
    
    // Show success message
    alert('Result published successfully!');
    $('#publishResultModal').modal('hide');
    form.reset();
}

function manageSyllabus() {
    alert('Redirecting to Syllabus Management...');
    // Here you would redirect to syllabus management page
}

function generateMarksheet() {
    alert('Generating marksheets...');
    // Here you would trigger marksheet generation
}

function viewAnalytics() {
    alert('Redirecting to Academic Analytics...');
    // Here you would redirect to analytics page
}

function generateAcademicReport() {
    alert('Generating comprehensive academic report...');
    // Here you would generate and download academic report
}

function viewResult(id) {
    alert('Viewing result details for ID: ' + id);
    // Here you would show result details
}

function downloadResult(id) {
    alert('Downloading result for ID: ' + id);
    // Here you would trigger result download
}
</script>
@endsection