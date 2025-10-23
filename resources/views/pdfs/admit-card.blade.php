<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admit Cards - {{ $exam->subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .admit-card {
            border: 2px solid #000;
            margin-bottom: 30px;
            padding: 20px;
            page-break-inside: avoid;
            min-height: 400px;
        }
        
        .school-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 15px;
        }
        
        .school-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .school-address {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .admit-card-title {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            text-decoration: underline;
        }
        
        .student-details {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .details-left, .details-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        
        .detail-row {
            margin-bottom: 8px;
            display: flex;
        }
        
        .detail-label {
            font-weight: bold;
            width: 120px;
            display: inline-block;
        }
        
        .detail-value {
            flex: 1;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 2px;
        }
        
        .exam-details {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        
        .exam-details h4 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 14px;
        }
        
        .instructions {
            margin-top: 20px;
        }
        
        .instructions h4 {
            font-size: 13px;
            margin-bottom: 10px;
            color: #495057;
        }
        
        .instructions ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 3px;
            font-size: 10px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 30px;
        }
        
        .signature-left, .signature-right {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 10px;
        }
        
        @media print {
            .admit-card {
                page-break-after: always;
            }
            
            .admit-card:last-child {
                page-break-after: avoid;
            }
        }
        
        .photo-section {
            float: right;
            width: 100px;
            height: 120px;
            border: 2px solid #000;
            margin-left: 20px;
            margin-bottom: 15px;
            text-align: center;
            line-height: 120px;
            font-size: 11px;
            color: #666;
            background-color: #f8f9fa;
        }
        .qr-section {
            float: right;
            width: 100px;
            height: 100px;
            border: 0;
            margin-left: 20px;
            margin-bottom: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    @foreach($admitCards as $admitCard)
    <div class="admit-card">
        <div class="school-header">
            <div class="school-name">{{ $school_name }}</div>
            <div class="school-address">{{ $school_address }}</div>
            <div class="admit-card-title">ADMIT CARD</div>
        </div>
        
        <div class="photo-section">
            AFFIX<br>PHOTO<br>HERE
        </div>
        
        <div class="student-details">
            <div class="details-left">
                <div class="detail-row">
                    <span class="detail-label">Admit Card No:</span>
                    <span class="detail-value">{{ $admitCard['admit_card_no'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Student Name:</span>
                    <span class="detail-value">{{ $admitCard['student_name'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Admission No:</span>
                    <span class="detail-value">{{ $admitCard['admission_no'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Class:</span>
                    <span class="detail-value">{{ $admitCard['class'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Father's Name:</span>
                    <span class="detail-value">{{ $admitCard['father_name'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Mother's Name:</span>
                    <span class="detail-value">{{ $admitCard['mother_name'] }}</span>
                </div>
            </div>
            
            <div class="details-right">
                <div class="detail-row">
                    <span class="detail-label">Date of Birth:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($admitCard['dob'])->format('d/m/Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Subject:</span>
                    <span class="detail-value">{{ $admitCard['exam_subject'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Exam Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($admitCard['exam_date'])->format('d/m/Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Exam Time:</span>
                    <span class="detail-value">{{ $admitCard['exam_time'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Duration:</span>
                    <span class="detail-value">{{ $admitCard['exam_duration'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Marks:</span>
                    <span class="detail-value">{{ $admitCard['total_marks'] }}</span>
                </div>
            </div>
        </div>
        
        <div style="clear: both;"></div>
        
        <div class="exam-details">
            <h4>Exam Details</h4>
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; width: 50%;">
                    <strong>Subject:</strong> {{ $exam->subject }}<br>
                    <strong>Date:</strong> {{ $exam->exam_date->format('d/m/Y') }}<br>
                    <strong>Day:</strong> {{ $exam->exam_date->format('l') }}
                </div>
                <div style="display: table-cell; width: 50%;">
                    <strong>Time:</strong> {{ $exam->start_time }} - {{ $exam->end_time }}<br>
                    <strong>Duration:</strong> {{ $exam->duration }} minutes<br>
                    <strong>Total Marks:</strong> {{ $exam->total_marks }}
                </div>
            </div>
        </div>
        
        <div class="instructions">
            <h4>Instructions for Candidates:</h4>
            <ul>
                @foreach($admitCard['instructions'] as $instruction)
                    <li>{{ $instruction }}</li>
                @endforeach
            </ul>
        </div>
        
        <div class="signature-section">
            <div class="signature-left">
                <div class="signature-line">
                    Student's Signature
                </div>
            </div>
            <div class="signature-right">
                <div class="signature-line">
                    Invigilator's Signature
                </div>
            </div>
        </div>
        
        <div class="footer">
            Generated on: {{ $generated_at->format('d/m/Y H:i:s') }} | 
            This is a computer generated admit card and does not require signature
        </div>
    </div>
    @endforeach
</body>
</html>