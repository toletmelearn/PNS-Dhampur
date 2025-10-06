<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BiometricDevice;
use App\Models\BiometricAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BiometricControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $teacher;
    protected $device;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user with admin role
        $this->user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);

        // Create test teacher
        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'employee_id' => '12345',
            'name' => 'Test Teacher'
        ]);

        // Create test biometric device
        $this->device = BiometricDevice::create([
            'device_id' => 'TEST_DEVICE_001',
            'name' => 'Test Fingerprint Scanner',
            'type' => 'fingerprint',
            'location' => 'Main Entrance',
            'ip_address' => '192.168.1.100',
            'port' => 4370,
            'connection_type' => 'tcp',
            'status' => 'active',
            'is_online' => true,
            'registered_by' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_import_csv_data()
    {
        Storage::fake('local');
        
        $csvContent = "employee_id,date,check_in_time,check_out_time\n12345,2025-01-20,09:00:00,17:00:00";
        $file = UploadedFile::fake()->createWithContent('attendance.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson('/api/biometric/import-data', [
                'type' => 'csv',
                'file' => $file
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $this->assertDatabaseHas('biometric_attendances', [
            'teacher_id' => $this->teacher->id,
            'date' => '2025-01-20',
            'import_source' => 'csv'
        ]);
    }

    /** @test */
    public function it_can_import_real_time_data()
    {
        $realTimeData = [
            [
                'employee_id' => '12345',
                'timestamp' => '2025-01-20 09:15:00',
                'event_type' => 'check_in',
                'device_id' => 'TEST_DEVICE_001'
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/biometric/import-data', [
                'type' => 'real_time',
                'data' => $realTimeData
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'processed' => 1,
                'duplicates' => 0,
                'errors' => 0
            ]);

        $this->assertDatabaseHas('biometric_attendances', [
            'teacher_id' => $this->teacher->id,
            'date' => '2025-01-20',
            'import_source' => 'real_time_device'
        ]);
    }

    /** @test */
    public function it_validates_real_time_data_structure()
    {
        $invalidData = [
            [
                'employee_id' => '', // Missing employee_id
                'timestamp' => '2025-01-20 09:15:00',
                'event_type' => 'check_in'
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/biometric/import-data', [
                'type' => 'real_time',
                'data' => $invalidData
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'processed' => 0,
                'errors' => 1
            ]);
    }

    /** @test */
    public function it_detects_duplicate_records()
    {
        // Create existing attendance record
        BiometricAttendance::create([
            'teacher_id' => $this->teacher->id,
            'date' => '2025-01-20',
            'check_in_time' => '09:15:00',
            'status' => 'present',
            'import_source' => 'manual'
        ]);

        $duplicateData = [
            [
                'employee_id' => '12345',
                'timestamp' => '2025-01-20 09:15:00',
                'event_type' => 'check_in',
                'device_id' => 'TEST_DEVICE_001'
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/biometric/import-data', [
                'type' => 'real_time',
                'data' => $duplicateData
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'processed' => 0,
                'duplicates' => 1,
                'errors' => 0
            ]);
    }

    /** @test */
    public function it_can_register_biometric_device()
    {
        $deviceData = [
            'device_id' => 'NEW_DEVICE_002',
            'name' => 'New Face Scanner',
            'type' => 'face',
            'location' => 'Staff Room',
            'ip_address' => '192.168.1.101',
            'port' => 8080,
            'connection_type' => 'http',
            'api_endpoint' => 'http://192.168.1.101:8080/api'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/biometric/device-register', $deviceData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Device registered successfully'
            ]);

        $this->assertDatabaseHas('biometric_devices', [
            'device_id' => 'NEW_DEVICE_002',
            'name' => 'New Face Scanner',
            'type' => 'face'
        ]);
    }

    /** @test */
    public function it_can_get_registered_devices()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/biometric/devices');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'success',
                'devices' => [
                    '*' => [
                        'device_id',
                        'name',
                        'type',
                        'location',
                        'status',
                        'is_online'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_test_device_connection()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/biometric/test-connection', [
                'device_id' => $this->device->device_id
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'success',
                'device_id',
                'connection_test' => [
                    'success',
                    'message',
                    'response_time'
                ]
            ]);
    }

    /** @test */
    public function it_can_get_device_status()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/biometric/device-status/' . $this->device->device_id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'device_id' => $this->device->device_id
            ])
            ->assertJsonStructure([
                'success',
                'device_id',
                'status',
                'is_online',
                'last_heartbeat',
                'last_sync',
                'configuration'
            ]);
    }

    /** @test */
    public function it_handles_device_stream_data()
    {
        $streamData = [
            'device_id' => $this->device->device_id,
            'stream_data' => [
                [
                    'employee_id' => '12345',
                    'timestamp' => now()->toISOString(),
                    'event_type' => 'check_in',
                    'biometric_data' => [
                        'type' => 'fingerprint',
                        'template' => base64_encode('fake_fingerprint_data'),
                        'quality' => 85
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/biometric/import-data', [
                'type' => 'device_stream',
                'device_id' => $this->device->device_id,
                'stream_data' => $streamData['stream_data']
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }

    /** @test */
    public function it_handles_bulk_sync_from_device()
    {
        $bulkData = [
            'device_id' => $this->device->device_id,
            'start_date' => '2025-01-20',
            'end_date' => '2025-01-20',
            'records' => [
                [
                    'employee_id' => '12345',
                    'timestamp' => '2025-01-20 09:00:00',
                    'event_type' => 'check_in'
                ],
                [
                    'employee_id' => '12345',
                    'timestamp' => '2025-01-20 17:00:00',
                    'event_type' => 'check_out'
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/biometric/import-data', [
                'type' => 'bulk_sync',
                'device_id' => $this->device->device_id,
                'start_date' => $bulkData['start_date'],
                'end_date' => $bulkData['end_date'],
                'records' => $bulkData['records']
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_api_endpoints()
    {
        $response = $this->postJson('/api/biometric/import-data', [
            'type' => 'csv'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_import_type()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/biometric/import-data', [
                'type' => 'invalid_type'
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid import type. Supported types: csv, real_time, device_stream, bulk_sync'
            ]);
    }
}