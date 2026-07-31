<?php

namespace Tests\Feature;

use App\Models\Station;
use App\Models\User;
use App\Models\WorkResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkResultTest extends TestCase
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
            'role' => 'Porter Apron',
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
            'role' => 'Porter Apron',
            'station' => 'CGK',
            'is_active' => true,
            'gender' => 'Male',
            'join_date' => '2026-01-01',
            'salary' => '4000000',
        ]);
    }

    public function test_guest_cannot_access_create_form(): void
    {
        $this->get(route('work_results.create'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_create_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('work_results.create'))
            ->assertOk()
            ->assertViewIs('work_result.create')
            ->assertViewHas('stations')
            ->assertViewHas('staffs');
    }

    public function test_user_can_submit_work_result_as_dci(): void
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('evidence.jpg');

        $response = $this->actingAs($this->user)
            ->post(route('work_results.store'), [
                'date' => '2026-07-31',
                'station' => 'CGK',
                'aircraft_reg' => 'PK-LGH',
                'ex_flight' => 'JT 371',
                'to_flight' => 'JT 202',
                'parking_stand' => 'A12',
                'wo_number' => 'WO-2026-001',
                'start_time' => '08:00',
                'end_time' => '09:30',
                'photo' => $photo,
                'staff_members' => [$this->staff1->id, $this->staff2->id],
                'action' => 'DCI',
            ]);

        $response->assertRedirect(route('work_results.index'));
        
        $this->assertDatabaseHas('work_results', [
            'station' => 'CGK',
            'aircraft_reg' => 'PK-LGH',
            'wo_number' => 'WO-2026-001',
            'type' => 'DCI',
        ]);

        $this->assertDatabaseHas('flights', [
            'station' => 'CGK',
            'flight_number' => 'JT 371',
            'registasi' => 'PK-LGH',
            'status' => 1,
        ]);

        $workResult = WorkResult::first();
        $this->assertNotNull($workResult->photo_path);
        Storage::disk('public')->assertExists($workResult->photo_path);

        $this->assertDatabaseHas('work_result_user', [
            'work_result_id' => $workResult->id,
            'user_id' => $this->staff1->id,
        ]);
        $this->assertDatabaseHas('work_result_user', [
            'work_result_id' => $workResult->id,
            'user_id' => $this->staff2->id,
        ]);
    }

    public function test_validation_requires_minimum_two_staff(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('work_results.create'))
            ->post(route('work_results.store'), [
                'date' => '2026-07-31',
                'station' => 'CGK',
                'aircraft_reg' => 'PK-LGH',
                'parking_stand' => 'A12',
                'wo_number' => 'WO-2026-001',
                'start_time' => '08:00',
                'end_time' => '09:30',
                'staff_members' => [$this->staff1->id], // Only one staff
                'action' => 'DCI',
            ]);

        $response->assertRedirect(route('work_results.create'));
        $response->assertSessionHasErrors('staff_members');
        $this->assertDatabaseEmpty('work_results');
    }

    public function test_validation_requires_end_time_after_start_time(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('work_results.create'))
            ->post(route('work_results.store'), [
                'date' => '2026-07-31',
                'station' => 'CGK',
                'aircraft_reg' => 'PK-LGH',
                'parking_stand' => 'A12',
                'wo_number' => 'WO-2026-001',
                'start_time' => '10:00',
                'end_time' => '09:00', // end time is before start time
                'staff_members' => [$this->staff1->id, $this->staff2->id],
                'action' => 'DCI',
            ]);

        $response->assertRedirect(route('work_results.create'));
        $response->assertSessionHasErrors('end_time');
        $this->assertDatabaseEmpty('work_results');
    }

    public function test_user_can_import_work_results_via_excel(): void
    {
        \Maatwebsite\Excel\Facades\Excel::fake();

        $file = UploadedFile::fake()->create('work_results.xlsx', 100);

        $response = $this->actingAs($this->user)
            ->post(route('work_results.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('work_results.index'));
        \Maatwebsite\Excel\Facades\Excel::assertImported('work_results.xlsx');
    }

    public function test_authenticated_user_can_view_work_results_index(): void
    {
        $this->actingAs($this->user)
            ->get(route('work_results.index'))
            ->assertOk()
            ->assertViewIs('work_result.index')
            ->assertViewHas('workResults');
    }

    public function test_admin_can_delete_work_result(): void
    {
        $workResult = WorkResult::create([
            'date' => '2026-07-31',
            'station' => 'CGK',
            'aircraft_reg' => 'PK-LGH',
            'parking_stand' => 'A12',
            'wo_number' => 'WO-2026-001',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'type' => 'DCI',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('work_results.destroy', $workResult->id));

        $response->assertRedirect(route('work_results.index'));
        $this->assertDatabaseMissing('work_results', ['id' => $workResult->id]);
    }

    public function test_user_can_download_excel_template(): void
    {
        \Maatwebsite\Excel\Facades\Excel::fake();

        $this->actingAs($this->user)
            ->get(route('work_results.template'))
            ->assertOk();

        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('Template_Import_Pekerjaan_APS.xlsx');
    }

    public function test_user_can_fetch_flight_data_dynamically(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('work_results.fetch_flight_data'), [
                'aircraft_reg' => 'PK-LGH',
                'station' => 'CGK',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotNull($response->json('data.station'));
        $this->assertEquals('CGK', $response->json('data.station'));
    }

    public function test_user_can_export_single_wo_pdf(): void
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('evidence.jpg');
        $path = $photo->store('work_results', 'public');

        $workResult = WorkResult::create([
            'date' => '2026-07-31',
            'station' => 'CGK',
            'aircraft_reg' => 'PK-AZH',
            'ex_flight' => 'QZ803',
            'to_flight' => 'QZ802',
            'parking_stand' => 'A12',
            'wo_number' => 'WO-2026-001',
            'start_time' => '09:06',
            'end_time' => '09:36',
            'type' => 'DCI',
            'photo_path' => $path,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('work_results.export_single_pdf', $workResult->id));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_user_can_upload_photo_later(): void
    {
        Storage::fake('public');

        $workResult = WorkResult::create([
            'date' => '2026-07-31',
            'station' => 'CGK',
            'aircraft_reg' => 'PK-AZH',
            'parking_stand' => 'A12',
            'wo_number' => 'WO-2026-002',
            'start_time' => '09:06',
            'end_time' => '09:36',
            'type' => 'DCI',
        ]);

        $this->assertNull($workResult->photo_path);

        $photo = UploadedFile::fake()->image('later_evidence.jpg');

        $response = $this->actingAs($this->user)
            ->post(route('work_results.upload_photo', $workResult->id), [
                'photo' => $photo,
            ]);

        $response->assertRedirect();
        
        $workResult->refresh();
        $this->assertNotNull($workResult->photo_path);
        Storage::disk('public')->assertExists($workResult->photo_path);
    }
}
