<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Departments\Laquage\Reports;
use App\Livewire\Departments\Laquage\ShowReport;
use App\Models\Department;
use App\Models\LaquageReport;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UiPagesTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?string $department = null): User
    {
        return User::factory()->create([
            'role' => $role,
            'department_id' => $department ? Department::where('slug', $department)->value('id') : null,
        ]);
    }

    private function makeReport(User $user, array $overrides = []): LaquageReport
    {
        $report = LaquageReport::create(array_merge([
            'user_id' => $user->id,
            'report_date' => now()->toDateString(),
            'line' => 'Ligne 1',
            'operators' => 'Ali, Sara',
        ], $overrides));

        $report->rows()->createMany([
            ['order_number' => 'C-100', 'bars_count' => 40, 'rejected' => 2, 'brilliance_average' => 85.5],
            ['order_number' => 'C-101', 'bars_count' => 60, 'rejected' => 0],
        ]);

        return $report;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DepartmentSeeder::class);
    }

    public function test_admin_sees_dashboard_with_stats_and_full_navigation(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeReport($admin);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Taux de rejet')
            ->assertSee('Laquage')
            ->assertSee('Administration')
            ->assertSee('Emballage');
    }

    public function test_laquage_user_navigation_is_scoped_to_their_department(): void
    {
        $user = $this->makeUser('department', 'laquage');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Nouveau rapport')
            ->assertDontSee('Administration')
            ->assertDontSee('departements/emballage');

        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('departments.emballage'))->assertForbidden();
    }

    public function test_emballage_user_gets_placeholder_page_and_no_laquage_access(): void
    {
        $user = $this->makeUser('department', 'emballage');

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->get(route('departments.emballage'))->assertOk()->assertSee('Bientôt disponible');
        $this->get(route('departments.laquage.index'))->assertForbidden();
    }

    public function test_reports_list_filters_and_scopes_to_owner(): void
    {
        $owner = $this->makeUser('department', 'laquage');
        $other = $this->makeUser('department', 'laquage');
        $this->makeReport($owner, ['line' => 'Ligne Alpha']);
        $this->makeReport($other, ['line' => 'Ligne Beta']);

        $this->actingAs($owner);

        Livewire::test(Reports::class)
            ->assertSee('Ligne Alpha')
            ->assertDontSee('Ligne Beta')
            ->set('search', 'zzz')
            ->assertSee('Aucun rapport ne correspond');

        Livewire::actingAs($this->makeUser('admin'))
            ->test(Reports::class)
            ->assertSee('Ligne Alpha')
            ->assertSee('Ligne Beta');
    }

    public function test_report_detail_export_and_delete(): void
    {
        $owner = $this->makeUser('department', 'laquage');
        $stranger = $this->makeUser('department', 'laquage');
        $report = $this->makeReport($owner);

        $this->actingAs($owner)->get(route('departments.laquage.show', $report->id))
            ->assertOk()
            ->assertSee('C-100')
            ->assertSee('Barres traitées');

        $this->get(route('departments.laquage.export', $report->id))->assertOk();

        $this->actingAs($stranger)->get(route('departments.laquage.show', $report->id))->assertNotFound();
        $this->get(route('departments.laquage.export', $report->id))->assertNotFound();

        Livewire::actingAs($owner)
            ->test(ShowReport::class, ['reportId' => $report->id])
            ->call('delete')
            ->assertRedirect(route('departments.laquage.index'));

        $this->assertDatabaseMissing('laquage_reports', ['id' => $report->id]);
        $this->assertDatabaseCount('laquage_report_lines', 0);
    }

    public function test_create_report_page_and_old_urls(): void
    {
        $user = $this->makeUser('department', 'laquage');

        $this->actingAs($user)->get(route('departments.laquage.create'))
            ->assertOk()
            ->assertSee('Nouveau rapport Laquage');

        $this->get('/departments/laquage/report/create')->assertRedirect('/departements/laquage/rapports/nouveau');
        $this->get('/laquage')->assertRedirect('/departements/laquage/rapports');
    }

    public function test_admin_pages_render_in_french(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('Nouvel utilisateur');
        $this->get(route('admin.users.create'))->assertOk()->assertSee('Mot de passe temporaire');
        $this->get(route('admin.users.edit', $admin))->assertOk()->assertSee('Modifier l&#039;utilisateur', false);
        $this->get(route('admin.departments.index'))->assertOk()->assertSee('Ajouter un département');

        Livewire::test(UsersIndex::class)->set('search', 'introuvable')->assertSee('Aucun utilisateur trouvé');
    }

    public function test_validation_messages_are_french(): void
    {
        $this->assertSame('Le champ nom est obligatoire.', trans('validation.required', ['attribute' => trans('validation.attributes.name')]));
        $this->assertSame('Tableau de bord', __('Dashboard'));
    }

    public function test_login_page_uses_brand_layout(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Rapports de laquage, suivi des rejets')
            ->assertSee('Se connecter');
    }

    public function test_dashboard_embeds_chart_config(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeReport($admin);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('apexChart', false)
            ->assertSee('sidebar', false);
    }
}
