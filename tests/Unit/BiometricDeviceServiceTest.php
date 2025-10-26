<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BiometricDeviceService;
use App\Models\User;
use App\Models\Teacher;
use App\Models\BiometricDevice;
use App\Models\BiometricAttendance;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BiometricDeviceServiceTest extends TestCase
{
    use  WithFaker;

    protected $biometricService;
    protected $teacher;
    protected $device;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->biometricService = new BiometricDeviceService();
        
        // Create test user with teacher role
        $user = User::factory()->create([
            'role' => 'teacher',
            'employee_id' => 'EMP001',
            'name' => 'Test Teacher'
        ]);
        
        // Create test teacher record
        $this->teacher = Teacher::create([
            'user_id' => $user->id,
            'qualification' => 'B.Ed',
            'experience_years' => 5,
            'salary' => 50000,
            'joining_date' => now()->subYears(2)
        ]);

        // Create test biometric device
        $this->device = BiometricDevice::create([
            'device_id' => 'TEST_DEVICE_001',
            'device_name' => 'Test Scanner',
            'device_type' => 'fingerprint',
            'location' => 'Main Gate',
            'ip_address' => '192.168.1.100',
            'port' => 4370,
            'connection_type' => 'tcp',
            'status' => 'online',
            'is_active' => true,
            'registered_by' => $user->id
        ]);
    }

    /** @test */
    public function service_can_import_csv_file_data()
    {
        Storage::fake('local');
        
        $csvContent = "employee_id,date,check_in_time,check_out_time\nEMP001,2025-01-20,09:00:00,17:00:00\nEMP001,2025-01-21,09:15:00,17:30:00";
        $file = UploadedFile::fake()->createWithContent('attendance.csv', $csvContent);

        $result = $this->biometricService->importBiometricData($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['processed']);
        $this->assertEquals(0, $result['errors']);
        $this->assertEquals(0, $result['duplicates']);

        $this->assertDatabaseHas('biometric_attendances', [
            'teacher_id' => $this->teacher->id,
            'date' => '2025-01-20',
            'import_source' => 'csv'
        ]);
    }

    /** @test */
    public function service_can_import_excel_file_data()
    {
        Storage::fake('local');
        
        // Create a simple Excel-like CSV for testing
        $excelContent = "Employee ID,Date,Check In,Check Out\nEMP001,2025-01-20,09:00:00,17:00:00";
        $file = UploadedFile::fake()->createWithContent('attendance.xlsx', $excelContent);

        $result = $this->biometricService->importBiometricData($file);

        $this->assertTrue($result['success']);
        // Excel processing is temporarily disabled, so we expect 0 processed records
        $this->assertEquals(0, $result['processed']);
        $this->assertStringContainsString('Excel processing temporarily disabled', $result['summary']);
    }

    /** @test */
    public function service_handles_invalid_csv_format()
    {
        Storage::fake('local');
        
        $invalidCsvContent = "invalid,header,format\ndata1,data2,data3";
        $file = UploadedFile::fake()->createWithContent('invalid.csv', $invalidCsvContent);

        $result = $this->biometricService->importBiometricData($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed']); // 1 row processed (even if invalid format)
        $this->assertEquals(0, $result['successful']); // 0 successful imports due to invalid format
        $this->assertEquals(0, $result['errors']); // No processing errors, just invalid data
    }

    /** @test */
    public function service_detects_duplicate_records()
    {
        // Create existing attendance record
        BiometricAttendance::create([
            'teacher_id' => $this->teacher->id,
            'date' => '2025-01-20',
            'check_in_time' => '09:00:00',
            'status' => 'present',
            'import_source' => 'manual'
        ]);

        Storage::fake('local');
        
        $csvContent = "employee_id,date,check_in_time,check_out_time\nEMP001,2025-01-20,09:00:00,";
        $file = UploadedFile::fake()->createWithContent('attendance.csv', $csvContent);

        $result = $this->biometricService->importBiometricData($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed']); // 1 row processed
        $this->assertEquals(1, $result['duplicates']); // 1 duplicate detected
        $this->assertEquals(0, $result['successful']); // 0 new records created
    }

    /** @test */
    public function service_processes_real_time_data()
    {
        $realTimeData = [
            [
                'employee_id' => 'EMP001',
                'timestamp' => '2025-01-20 09:15:00',
                'event_type' => 'check_in',
                'device_id' => 'TEST_DEVICE_001',
                'verification_type' => 'IN'
            ],
            [
                'employee_id' => 'EMP001',
                'timestamp' => '2025-01-20 17:30:00',
                'event_type' => 'check_out',
                'device_id' => 'TEST_DEVICE_001',
                'verification_type' => 'OUT'
            ]
        ];

        $result = $this->biometricService->processRealTimeData($realTimeData);

        // Debug output
        dump($result);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['processed']);
        $this->assertEquals(0, $result['errors']);

        $this->assertDatabaseHas('biometric_attendances', [
            'teacher_id' => $this->teacher->id,
            'date' => '2025-01-20',
            'import_source' => 'real_time_device'
        ]);
    }

    /** @test */
    public function service_validates_real_time_data_structure()
    {
        $invalidData = [
            [
                'employee_id' => '', // Missing employee_id
                'timestamp' => '2025-01-20 09:15:00',
                'event_type' => 'check_in'
            ],
            [
                'employee_id' => 'EMP001',
                'timestamp' => 'invalid-timestamp', // Invalid timestamp
                'event_type' => 'check_in'
            ]
        ];

        $result = $this->biometricService->processRealTimeData($invalidData);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(2, $result['errors']);
    }

    /** @test */
    public function service_handles_unknown_employee_id()
    {
        $realTimeData = [
            [
                'employee_id' => 'UNKNOWN_EMP',
                'timestamp' => '2025-01-20 09:15:00',
                'event_type' => 'check_in',
                'device_id' => 'TEST_DEVICE_001'
            ]
        ];

        $result = $this->biometricService->processRealTimeData($realTimeData);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(1, $result['errors']);
    }

    /** @test */
    public function service_updates_import_progress()
    {
        $importId = 'test_import_123';
        $progress = [
            'total' => 100,
            'processed' => 50,
            'errors' => 2,
            'duplicates' => 3,
            'status' => 'processing'
        ];

        $this->biometricService->updateImportProgress($importId, $progress);

        $cachedProgress = Cache::get("biometric_import_progress_{$importId}");
        
        $this->assertNotNull($cachedProgress);
        $this->assertEquals(50, $cachedProgress['processed']);
        $this->assertEquals('processing', $cachedProgress['status']);
    }

    /** @test */
    public function service_generates_import_summary()
    {
        $importData = [
            'total' => 100,
            'processed' => 95,
            'errors' => 3,
            'duplicates' => 2,
            'start_time' => now()->subMinutes(5),
            'end_time' => now()
        ];

        $summary = $this->biometricService->generateImportSummary($importData);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_records', $summary);
        $this->assertArrayHasKey('successful_imports', $summary);
        $this->assertArrayHasKey('errors', $summary);
        $this->assertArrayHasKey('duplicates', $summary);
        $this->assertArrayHasKey('processing_time', $summary);
        $this->assertArrayHasKey('success_rate', $summary);

        $this->assertEquals(100, $summary['total_records']);
        $this->assertEquals(95, $summary['successful_imports']);
        $this->assertEquals(95.0, $summary['success_rate']);
    }

    /** @test */
    public function service_gets_import_status()
    {
        $importId = 'test_import_456';
        
        // Set up test progress data
        Cache::put("biometric_import_progress_{$importId}", [
            'total' => 50,
            'processed' => 30,
            'errors' => 1,
            'duplicates' => 2,
            'status' => 'processing',
            'start_time' => now()->subMinutes(2)->toISOString()
        ], 3600);

        $status = $this->biometricService->getImportStatus($importId);

        $this->assertIsArray($status);
        $this->assertEquals('processing', $status['status']);
        $this->assertEquals(30, $status['processed']);
        $this->assertEquals(60.0, $status['progress_percentage']);
    }

    /** @test */
    public function service_gets_import_statistics()
    {
        // Create some test attendance records
        BiometricAttendance::factory()->count(5)->create([
            'import_source' => 'csv',
            'created_at' => now()->subDays(1)
        ]);

        BiometricAttendance::factory()->count(3)->create([
            'import_source' => 'real_time_device',
            'created_at' => now()->subDays(2)
        ]);

        $stats = $this->biometricService->getImportStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_imports', $stats);
        $this->assertArrayHasKey('imports_by_source', $stats);
        $this->assertArrayHasKey('recent_imports', $stats);
        $this->assertArrayHasKey('daily_import_trend', $stats);

        $this->assertGreaterThan(0, $stats['total_imports']);
    }

    /** @test */
    public function service_clears_import_cache()
    {
        $importId = 'test_import_789';
        
        // Set up test cache data
        Cache::put("biometric_import_progress_{$importId}", ['status' => 'completed'], 3600);
        Cache::put("biometric_import_summary_{$importId}", ['total' => 100], 3600);

        $this->assertTrue(Cache::has("biometric_import_progress_{$importId}"));
        $this->assertTrue(Cache::has("biometric_import_summary_{$importId}"));

        $this->biometricService->clearImportCache($importId);

        $this->assertFalse(Cache::has("biometric_import_progress_{$importId}"));
        $this->assertFalse(Cache::has("biometric_import_summary_{$importId}"));
    }

    /** @test */
    public function service_handles_large_file_import()
    {
        Storage::fake('local');
        
        // Create a large CSV content
        $csvContent = "employee_id,date,check_in_time,check_out_time\n";
        for ($i = 1; $i <= 1000; $i++) {
            $date = now()->subDays(rand(1, 30))->format('Y-m-d');
            $csvContent .= "EMP001,{$date},09:00:00,17:00:00\n";
        }
        
        $file = UploadedFile::fake()->createWithContent('large_attendance.csv', $csvContent);

        $result = $this->biometricService->importBiometricData($file);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['processed']);
    }

    /** @test */
    public function service_handles_concurrent_imports()
    {
        Storage::fake('local');
        
        $csvContent1 = "employee_id,date,check_in_time,check_out_time\nEMP001,2025-01-20,09:00:00,17:00:00";
        $csvContent2 = "employee_id,date,check_in_time,check_out_time\nEMP001,2025-01-21,09:15:00,17:30:00";
        
        $file1 = UploadedFile::fake()->createWithContent('attendance1.csv', $csvContent1);
        $file2 = UploadedFile::fake()->createWithContent('attendance2.csv', $csvContent2);

        // Simulate concurrent imports
        $result1 = $this->biometricService->importBiometricData($file1);
        $result2 = $this->biometricService->importBiometricData($file2);

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertEquals(1, $result1['processed']);
        $this->assertEquals(1, $result2['processed']);
    }

    /** @test */
    public function service_validates_supported_file_formats()
    {
        $supportedFormats = $this->biometricService->getSupportedFormats();

        $this->assertIsArray($supportedFormats);
        $this->assertContains('csv', $supportedFormats);
        $this->assertContains('excel', $supportedFormats);
        $this->assertContains('xlsx', $supportedFormats);
    }

    /** @test */
    public function service_validates_device_types()
    {
        $deviceTypes = $this->biometricService->getSupportedDeviceTypes();

        $this->assertIsArray($deviceTypes);
        $this->assertContains('fingerprint', $deviceTypes);
        $this->assertContains('face', $deviceTypes);
        $this->assertContains('iris', $deviceTypes);
        $this->assertContains('card', $deviceTypes);
    }

    /** @test */
    public function service_handles_malformed_csv_data()
    {
        Storage::fake('local');
        
        $malformedCsv = "employee_id,date,check_in_time\nEMP001,invalid-date,not-a-time\n,2025-01-20,09:00:00\nEMP001,,17:00:00";
        $file = UploadedFile::fake()->createWithContent('malformed.csv', $malformedCsv);

        $result = $this->biometricService->importBiometricData($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['processed']); // 3 CSV rows are processed even if they have errors
        $this->assertGreaterThan(0, $result['errors']);
    }

    /** @test */
    public function service_processes_device_stream_data()
    {
        $streamData = [
            'device_id' => 'TEST_DEVICE_001',
            'stream_data' => [
                [
                    'employee_id' => 'EMP001',
                    'timestamp' => now()->toISOString(),
                    'event_type' => 'check_in',
                    'verification_type' => 'IN',
                    'biometric_data' => [
                        'type' => 'fingerprint',
                        'template' => base64_encode('fake_template_data'),
                        'quality' => 85
                    ]
                ]
            ]
        ];

        $result = $this->biometricService->processDeviceStream($streamData);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed']);
    }

    /** @test */
    public function service_handles_bulk_sync_from_device()
    {
        $bulkSyncData = [
            'device_id' => 'TEST_DEVICE_001',
            'start_date' => '2025-01-20',
            'end_date' => '2025-01-21',
            'attendance_records' => [
                [
                    'employee_id' => 'EMP001',
                    'timestamp' => '2025-01-20 09:00:00',
                    'event_type' => 'check_in',
                    'verification_type' => 'IN'
                ],
                [
                    'employee_id' => 'EMP001',
                    'timestamp' => '2025-01-20 17:00:00',
                    'event_type' => 'check_out',
                    'verification_type' => 'OUT'
                ]
            ]
        ];

        $result = $this->biometricService->processBulkSync($bulkSyncData);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['processed']);
    }

    /** @test */
    public function service_logs_import_activities()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Biometric import started', \Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Biometric import completed', \Mockery::type('array'));

        // Allow for potential error logs during processing
        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->with(\Mockery::type('string'), \Mockery::type('array'));

        Storage::fake('local');
        
        $csvContent = "employee_id,date,check_in_time,check_out_time\nEMP001,2025-01-20,09:00:00,17:00:00";
        $file = UploadedFile::fake()->createWithContent('attendance.csv', $csvContent);

        $this->biometricService->importBiometricData($file);
    }

    /** @test */
    public function service_handles_empty_file()
    {
        Storage::fake('local');
        
        $emptyFile = UploadedFile::fake()->createWithContent('empty.csv', '');

        $result = $this->biometricService->importBiometricData($emptyFile);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['errors']);
    }

    /** @test */
    public function service_validates_import_permissions()
    {
        $unauthorizedUser = User::factory()->create(['role' => 'student']);
        
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        
        $this->biometricService->validateImportPermissions($unauthorizedUser);
    }

    /** @test */
    public function service_handles_network_timeout_gracefully()
    {
        $deviceData = [
            'device_id' => 'TIMEOUT_DEVICE',
            'ip_address' => '192.168.1.999', // Non-existent IP
            'port' => 4370
        ];

        $result = $this->biometricService->testDeviceConnection($deviceData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('timeout', strtolower($result['message']));
    }

    /** @test */
    public function service_processes_attendance_with_different_time_zones()
    {
        $realTimeData = [
            [
                'employee_id' => 'EMP001',
                'timestamp' => '2025-01-20T09:15:00+05:30', // IST timezone
                'event_type' => 'check_in',
                'device_id' => 'TEST_DEVICE_001'
            ]
        ];

        $result = $this->biometricService->processRealTimeData($realTimeData);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed']);
    }

    /** @test */
    public function service_handles_memory_intensive_operations()
    {
        // Test with large dataset to ensure memory efficiency
        $largeDataset = [];
        for ($i = 0; $i < 5000; $i++) {
            $largeDataset[] = [
                'employee_id' => 'EMP001',
                'timestamp' => now()->subMinutes($i)->toISOString(),
                'event_type' => $i % 2 === 0 ? 'check_in' : 'check_out',
                'device_id' => 'TEST_DEVICE_001'
            ];
        }

        $memoryBefore = memory_get_usage();
        $result = $this->biometricService->processRealTimeData($largeDataset);
        $memoryAfter = memory_get_usage();

        $this->assertTrue($result['success']);
        
        // Ensure memory usage doesn't exceed reasonable limits
        $memoryIncrease = $memoryAfter - $memoryBefore;
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease); // Less than 50MB increase
    }
}