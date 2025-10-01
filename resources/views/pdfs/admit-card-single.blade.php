<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admit Card - {{ $admitCard['student_name'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .admit-card {
            border: 3px solid #000;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
            min-height: 600px;
        }
        
        .school-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .school-address {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .admit-card-title {
            font-size: 20px;
            font-weight: bold;
            color: #e74c3c;
            text-decoration: underline;
            letter-spacing: 2px;
        }
        
        .photo-section {
            float: right;
            width: 120px;
            height: 150px;
            border: 2px solid #000;
            margin-left: 30px;
            margin-bottom: 20px;
            text-align: center;
            line-height: 150px;
            font-size: 12px;
            color: #666;
            background-color: #f8f9fa;
        }
        
        .student-details {
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .details-section {
            width: 60%;
            float: left;
        }
        
        .detail-row {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
        
        .detail-label {
            font-weight: bold;
            width: 140px;
            display: inline-block;
            color: #2c3e50;
        }
        
        .detail-value {
            flex: 1;
            border-bottom: 2px dotted #333;
            padding-bottom: 3px;
            padding-left: 10px;
            font-size: 15px;
        }
        
        .exam-details {
            clear: both;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .exam-details h4 {
            margin: 0 0 15px 0;
            font-size: 16px;
            text-align: center;
        }
        
        .exam-info {
            display: table;
            width: 100%;
        }
        
        .exam-left, .exam-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .exam-item {
            margin-bottom: 8px;
        }
        
        .exam-item strong {
            display: inline-block;
            width: 100px;
        }
        
        .instructions {
            margin-top: 25px;
            border: 1px solid #dee2e6;
            padding: 20px;
            background-color: #f8f9fa;
        }
        
        .instructions h4 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #495057;
            text-align: center;
            text-decoration: underline;
        }
        
        .instructions ul {
            margin: 0;
            padding-left: 25px;
        }
        
        .instructions li {
            margin-bottom: 6px;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        
        .signature-left, .signature-center, .signature-right {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
        }
        
        .signature-line {
            border-top: 2px solid #000;
            margin-top: 50px;
            padding-top: 8px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 15px;
        }
        
        .admit-card-no {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 12px;
            font-weight: bold;
            color: #666;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60px;
            color: rgba(0,0,0,0.05);
            z-index: -1;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="admit-card">
        <div class="admit-card-no">{{ $admitCard['admit_card_no'] }}</div>
        <div class="watermark">{{ $school_name }}</div>
        
        <div class="school-header">
            <div class="school-name">{{ $school_name }}</div>
            <div class="school-address">{{ $school_address }}</div>
            <div class="admit-card-title">EXAMINATION ADMIT CARD</div>
        </div>
        
        <div class="photo-section">
            AFFIX<br>RECENT<br>PASSPORT<br>SIZE<br>PHOTOGRAPH<br>HERE
        </div>
        
        <div class="student-details">
            <div class="details-section">
                <div class="detail-row">
                    <span class="detail-label">Student Name:</span>
                    <span class="detail-value">{{ $admitCard['student_name'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Admission No:</span>
                    <span class="detail-value">{{ $admitCard['admission_no'] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Class & Section:</span>
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
                <div class="detail-row">
                    <span class="detail-label">Date of Birth:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($admitCard['dob'])->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
        
        <div class="exam-details">
            <h4>EXAMINATION DETAILS</h4>
            <div class="exam-info">
                <div class="exam-left">
                    <div class="exam-item">
                        <strong>Subject:</strong> {{ $admitCard['exam_subject'] }}
                    </div>
                    <div class="exam-item">
                        <strong>Date:</strong> {{ \Carbon\Carbon::parse($admitCard['exam_date'])->format('d/m/Y') }}
                    </div>
                    <div class="exam-item">
                        <strong>Day:</strong> {{ \Carbon\Carbon::parse($admitCard['exam_date'])->format('l') }}
                    </div>
                </div>
                <div class="exam-right">
                    <div class="exam-item">
                        <strong>Time:</strong> {{ $admitCard['exam_time'] }}
                    </div>
                    <div class="exam-item">
                        <strong>Duration:</strong> {{ $admitCard['exam_duration'] }}
                    </div>
                    <div class="exam-item">
                        <strong>Total Marks:</strong> {{ $admitCard['total_marks'] }}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="instructions">
            <h4>INSTRUCTIONS FOR CANDIDATES</h4>
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
            <div class="signature-center">
                <div class="signature-line">
                    Invigilator's Signature
                </div>
            </div>
            <div class="signature-right">
                <div class="signature-line">
                    Principal's Signature
                </div>
            </div>
        </div>
        
        <div class="footer">
            <strong>Generated on:</strong> {{ $generated_at->format('d/m/Y H:i:s') }}<br>
            This is a computer generated admit card. Any alteration or damage will make it invalid.<br>
            <strong>Note:</strong> Candidates must bring this admit card to the examination hall.
        </div>
    </div>
</body>
</html>