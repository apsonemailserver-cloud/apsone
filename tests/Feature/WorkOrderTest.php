<?php

namespace Tests\Feature;

use App\Models\Station;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Station $station;
    private User $staff1;
    private User $staff2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->station = Station::create([
            'code' => 'CGK',
            'name' => 'Jakarta Cengkareng',
            'is_active' => true,
            'latitude' => -6.1256,
            'longitude' => 106.6558,
            'radius' => 100,
        ]);

        $this->user = User::create([
            'id' => '2207004',
            'fullname' => 'UMAR MARUF M',
            'email' => 'umar@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'station' => 'CGK',
            'is_active' => true,
            'gender' => 'Male',
            'join_date' => '2026-01-01',
            'salary' => '5000000',
        ]);

        $this->staff1 = User::create([
            'id' => '10240444',
            'fullname' => 'Staff Satu',
            'email' => 'staff1@example.com',
            'password' => bcrypt('password'),
            'role' => 'Staff Aircraft Interior Exterior Cleaning',
            'station' => 'CGK',
            'is_active' => true,
            'gender' => 'Male',
            'join_date' => '2026-01-01',
            'salary' => '4000000',
        ]);

        $this->staff2 = User::create([
            'id' => '10240445',
            'fullname' => 'Staff Dua',
            'email' => 'staff2@example.com',
            'password' => bcrypt('password'),
            'role' => 'Staff Aircraft Interior Exterior Cleaning',
            'station' => 'CGK',
            'is_active' => true,
            'gender' => 'Male',
            'join_date' => '2026-01-01',
            'salary' => '4000000',
        ]);
    }

    public function test_guest_cannot_access_create_form(): void
    {
        $response = $this->get(route('work_orders.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_create_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('work_orders.create'));
        $response->assertStatus(200);
        $response->assertSee('Create Assignment');
    }

    public function test_user_can_submit_work_result_as_dci(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('proof.jpg', 600, 400);

        $payload = [
            'date' => '2026-07-31',
            'station' => 'CGK',
            'aircraft_reg' => 'PK-LGH',
            'ex_flight' => 'JT 371',
            'to_flight' => 'JT 202',
            'parking_stand' => 'A12',
            'wo_number' => 'WO-2026-001',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'action' => 'DCI',
            'staff_members' => [$this->staff1->id, $this->staff2->id],
            'photo' => $photo,
        ];

        $response = $this->actingAs($this->user)->post(route('work_orders.store'), $payload);

        $response->assertRedirect(route('work_orders.index'));

        $this->assertDatabaseHas('assignments', [
            'station' => 'CGK',
            'aircraft_reg' => 'PK-LGH',
            'wo_number' => 'WO-2026-001',
            'type' => 'DCI',
        ]);

        $workOrder = WorkOrder::first();
        $this->assertCount(2, $workOrder->users);
        $this->assertNotNull($workOrder->photo_path);
        Storage::disk('public')->assertExists($workOrder->photo_path);
    }

    public function test_validation_requires_minimum_two_staff(): void
    {
        $payload = [
            'date' => '2026-07-31',
            'station' => 'CGK',
            'aircraft_reg' => 'PK-LGH',
            'parking_stand' => 'A12',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'action' => 'DCI',
            'staff_members' => [$this->staff1->id],
        ];

        $response = $this->actingAs($this->user)->post(route('work_orders.store'), $payload);
        $response->assertSessionHasErrors(['staff_members']);
    }

    public function test_validation_requires_end_time_after_start_time(): void
    {
        $payload = [
            'date' => '2026-07-31',
            'station' => 'CGK',
            'aircraft_reg' => 'PK-LGH',
            'parking_stand' => 'A12',
            'start_time' => '09:30',
            'end_time' => '08:00',
            'action' => 'DCI',
            'staff_members' => [$this->staff1->id, $this->staff2->id],
        ];

        $response = $this->actingAs($this->user)->post(route('work_orders.store'), $payload);
        $response->assertSessionHasErrors(['end_time']);
    }

    public function test_user_can_import_work_results_via_excel(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->createWithContent(
            'import.xlsx',
            "Tanggal,Station,AircraftReg,ExFlight,ToFlight,ParkingStand,NoWO,StartTime,EndTime,Type,StaffNIKs\n" .
            "2026-07-31,CGK,PK-LGH,JT371,JT202,A12,WO-2026-999,08:00,09:30,DCI,\"{$this->staff1->id},{$this->staff2->id}\"\n"
        );

        $response = $this->actingAs($this->user)->post(route('work_orders.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('work_orders.index'));
    }

    public function test_authenticated_user_can_view_work_results_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('work_orders.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_delete_work_result(): void
    {
        $workOrder = WorkOrder::create([
            'date' => '2026-07-31',
            'station' => 'CGK',
            'aircraft_reg' => 'PK-LGH',
            'parking_stand' => 'A12',
            'wo_number' => 'WO-2026-001',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'type' => 'DCI',
            'photo_path' => null,
        ]);

        $response = $this->actingAs($this->user)->delete(route('work_orders.destroy', $workOrder->id));
        $response->assertRedirect(route('work_orders.index'));

        $this->assertDatabaseMissing('assignments', ['id' => $workOrder->id]);
    }

    public function test_user_can_download_excel_template(): void
    {
        $response = $this->actingAs($this->user)->get(route('work_orders.template'));
        $response->assertStatus(200);
    }

    public function test_user_can_fetch_flight_data_dynamically(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('work_orders.fetch_flight_data'), [
            'aircraft_reg' => 'PK-LGH',
            'station' => 'CGK',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'source',
            'flights',
            'data' => [
                'aircraft_reg',
                'ex_flight',
                'to_flight',
                'station',
                'start_time',
                'end_time',
            ]
        ]);
    }

    public function test_user_can_export_single_wo_pdf(): void
    {
        Storage::fake('public');

        $workOrder = WorkOrder::create([
            'date' => '2026-07-31',
            'station' => 'CGK',
            'aircraft_reg' => 'PK-LGH',
            'parking_stand' => 'A12',
            'wo_number' => 'WO-2026-001',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'type' => 'DCI',
            'photo_path' => 'work_orders/proof.jpg',
        ]);

        Storage::disk('public')->put('work_orders/proof.jpg', 'dummy image content');

        $response = $this->actingAs($this->user)->get(route('work_orders.export_single_pdf', $workOrder->id));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_user_can_upload_photo_later(): void
    {
        Storage::fake('public');

        $workOrder = WorkOrder::create([
            'date' => '2026-07-31',
            'station' => 'CGK',
            'aircraft_reg' => 'PK-LGH',
            'parking_stand' => 'A12',
            'wo_number' => 'WO-2026-001',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'type' => 'DCI',
            'photo_path' => null,
        ]);

        $photo = UploadedFile::fake()->image('late_proof.jpg', 600, 400);

        $response = $this->actingAs($this->user)->post(route('work_orders.upload_photo', $workOrder->id), [
            'photo' => $photo,
        ]);

        $response->assertRedirect();
        $workOrder->refresh();
        $this->assertNotNull($workOrder->photo_path);
        Storage::disk('public')->assertExists($workOrder->photo_path);
    }
}
