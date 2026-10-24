<?php

namespace Tests\Feature\Laquage;

use App\Livewire\Dashboard;
use App\Livewire\Departments\Laquage\ReportForm;
use App\Livewire\Departments\Laquage\Reports;
use App\Livewire\Departments\Laquage\ShowReport;
use App\Models\Department;
use App\Models\LaquageReport;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Laquage reports belong to the department: every Laquage user (and the administrator)
 * sees and edits all of them; only the author or an administrator can delete one.
 */
class ReportSharingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DepartmentSeeder::class);
    }

    private function makeUser(string $role, ?string $department = null): User
    {
        return User::factory()->create([
            'role' => $role,
            'department_id' => $department ? Department::where('slug', $department)->value('id') : null,
        ]);
    }

    private function makeReport(User $author, string $line = 'Ligne Alpha'): LaquageReport
    {
        $report = LaquageReport::create([
            'user_id' => $author->id,
            'report_date' => now()->toDateString(),
            'line' => $line,
        ]);
        $report->rows()->createMany([
            ['order_number' => 'C-100', 'bars_count' => 40, 'rejected' => 2],
            ['order_number' => 'C-101', 'bars_count' => 60, 'rejected' => 0],
        ]);

        return $report;
    }

    public function test_colleagues_see_each_others_reports_with_the_author_name(): void
    {
        $author = $this->makeUser('department', 'laquage');
        $colleague = $this->makeUser('department', 'laquage');
        $this->makeReport($author);

        Livewire::actingAs($colleague)->test(Reports::class)
            ->assertSee('Ligne Alpha')
            ->assertSee($author->name);
    }

    public function test_a_colleague_can_edit_the_header_and_the_lines_of_someone_elses_report(): void
    {
        $author = $this->makeUser('department', 'laquage');
        $colleague = $this->makeUser('department', 'laquage');
        $report = $this->makeReport($author);
        $lineId = $report->rows()->orderBy('id')->value('id');

        $form = Livewire::actingAs($colleague)->test(ReportForm::class, ['reportId' => $report->id])
            ->assertSet('line', 'Ligne Alpha');

        $form->set('line', 'Ligne modifiée')->call('saveHeader')->assertHasNoErrors();
        $this->assertDatabaseHas('laquage_reports', ['id' => $report->id, 'line' => 'Ligne modifiée', 'user_id' => $author->id]);

        $form->call('editLine', $lineId)
            ->set('lineForm.order_number', 'C-EDIT')
            ->call('saveLine')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('laquage_report_lines', ['id' => $lineId, 'order_number' => 'C-EDIT']);

        $form->call('deleteLine', $lineId);
        $this->assertDatabaseMissing('laquage_report_lines', ['id' => $lineId]);
    }

    public function test_only_the_author_or_an_admin_can_delete_a_report(): void
    {
        $author = $this->makeUser('department', 'laquage');
        $colleague = $this->makeUser('department', 'laquage');
        $admin = $this->makeUser('admin');

        $report = $this->makeReport($author);
        Livewire::actingAs($colleague)->test(Reports::class)->call('delete', $report->id)->assertForbidden();
        $this->assertDatabaseHas('laquage_reports', ['id' => $report->id]);

        Livewire::actingAs($author)->test(Reports::class)->call('delete', $report->id);
        $this->assertDatabaseMissing('laquage_reports', ['id' => $report->id]);

        $report = $this->makeReport($author);
        Livewire::actingAs($admin)->test(Reports::class)->call('delete', $report->id);
        $this->assertDatabaseMissing('laquage_reports', ['id' => $report->id]);
    }

    public function test_the_delete_button_is_only_offered_to_those_who_may_use_it(): void
    {
        $author = $this->makeUser('department', 'laquage');
        $colleague = $this->makeUser('department', 'laquage');
        $report = $this->makeReport($author);
        $deleteCall = 'delete('.$report->id.')';

        Livewire::actingAs($author)->test(Reports::class)->assertSeeHtml($deleteCall);
        Livewire::actingAs($colleague)->test(Reports::class)->assertDontSeeHtml($deleteCall);

        Livewire::actingAs($author)->test(ShowReport::class, ['reportId' => $report->id])->assertSeeHtml('wire:click="delete"');
        Livewire::actingAs($colleague)->test(ShowReport::class, ['reportId' => $report->id])->assertDontSeeHtml('wire:click="delete"');
    }

    public function test_a_report_whose_author_was_deleted_can_only_be_deleted_by_an_admin(): void
    {
        $author = $this->makeUser('department', 'laquage');
        $colleague = $this->makeUser('department', 'laquage');
        $admin = $this->makeUser('admin');
        $report = $this->makeReport($author);
        $author->delete();

        Livewire::actingAs($colleague)->test(Reports::class)->call('delete', $report->id)->assertForbidden();
        $this->assertDatabaseHas('laquage_reports', ['id' => $report->id]);

        Livewire::actingAs($admin)->test(Reports::class)->call('delete', $report->id);
        $this->assertDatabaseMissing('laquage_reports', ['id' => $report->id]);
    }

    public function test_dashboard_totals_cover_the_whole_department(): void
    {
        $author = $this->makeUser('department', 'laquage');
        $colleague = $this->makeUser('department', 'laquage');
        $this->makeReport($author);
        $this->makeReport($colleague, 'Ligne Beta');

        Livewire::actingAs($colleague)->test(Dashboard::class)
            ->assertViewHas('totalReports', 2)
            ->assertViewHas('totalBars', 200);
    }

    public function test_users_of_other_departments_see_nothing(): void
    {
        $author = $this->makeUser('department', 'laquage');
        $packer = $this->makeUser('department', 'emballage');
        $report = $this->makeReport($author);

        $this->assertSame(0, LaquageReport::query()->visibleTo($packer)->count());

        $this->actingAs($packer);
        $this->get(route('departments.laquage.index'))->assertForbidden();
        $this->get(route('departments.laquage.show', $report->id))->assertForbidden();
        $this->get(route('departments.laquage.edit', $report->id))->assertForbidden();
        $this->get(route('departments.laquage.export', $report->id))->assertForbidden();
    }
}
