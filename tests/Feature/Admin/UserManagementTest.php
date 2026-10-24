<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Users\Create as UsersCreate;
use App\Livewire\Admin\Users\Edit as UsersEdit;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Departments\Laquage\Reports;
use App\Models\Department;
use App\Models\LaquageReport;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DepartmentSeeder::class);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin', 'department_id' => null]);
    }

    private function makeLaquageUser(): User
    {
        return User::factory()->create([
            'role' => 'department',
            'department_id' => Department::where('slug', 'laquage')->value('id'),
        ]);
    }

    private function makeReportFor(User $user): LaquageReport
    {
        $report = LaquageReport::create([
            'user_id' => $user->id,
            'report_date' => now()->toDateString(),
            'line' => 'Ligne Orpheline',
        ]);
        $report->rows()->createMany([
            ['order_number' => 'C-1', 'bars_count' => 10],
            ['order_number' => 'C-2', 'bars_count' => 20],
        ]);

        return $report;
    }

    public function test_deleting_a_user_keeps_their_reports_and_lines(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeLaquageUser();
        $report = $this->makeReportFor($user);

        Livewire::actingAs($admin)->test(UsersIndex::class)->call('delete', $user->id);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('laquage_reports', ['id' => $report->id, 'user_id' => null]);
        $this->assertDatabaseCount('laquage_report_lines', 2);
        $this->assertSame('Utilisateur supprimé', $report->fresh()->authorName());
    }

    public function test_orphaned_report_stays_visible_to_the_department_with_a_deleted_author(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeLaquageUser();
        $colleague = $this->makeLaquageUser();
        $this->makeReportFor($user);
        $user->delete();

        foreach ([$admin, $colleague] as $viewer) {
            Livewire::actingAs($viewer)->test(Reports::class)
                ->assertSee('Ligne Orpheline')
                ->assertSee('Utilisateur supprimé');
        }
    }

    public function test_users_created_by_the_admin_are_department_users_never_admins(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin)->test(UsersCreate::class)
            ->set('name', 'Nouvel Opérateur')
            ->set('email', 'operateur@example.com')
            ->set('password', 'a-temporary-password')
            ->set('departmentId', (string) Department::where('slug', 'laquage')->value('id'))
            ->call('createUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'operateur@example.com', 'role' => 'department']);
        $this->assertSame(1, User::where('role', 'admin')->count());
    }

    public function test_deleted_author_is_shown_on_the_report_detail_page(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeLaquageUser();
        $report = $this->makeReportFor($user);
        $user->delete();

        $this->actingAs($admin)->get(route('departments.laquage.show', $report->id))
            ->assertOk()
            ->assertSee('Utilisateur supprimé');
    }

    public function test_an_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin)->test(UsersIndex::class)->call('delete', $admin->id);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_sets_a_new_password_for_a_user(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeLaquageUser();
        $oldRememberToken = $user->remember_token;

        Livewire::actingAs($admin)->test(UsersEdit::class, ['user' => $user])
            ->set('password', 'a-brand-new-password')
            ->call('update')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertTrue(Hash::check('a-brand-new-password', $user->password));
        $this->assertNotSame($oldRememberToken, $user->remember_token);
    }

    public function test_the_user_can_log_in_with_the_password_set_by_the_admin(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeLaquageUser();

        Livewire::actingAs($admin)->test(UsersEdit::class, ['user' => $user])
            ->set('password', 'a-brand-new-password')
            ->call('update');

        auth()->logout();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'a-brand-new-password'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_leaving_the_password_blank_keeps_the_current_one(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeLaquageUser();
        $hash = $user->password;

        Livewire::actingAs($admin)->test(UsersEdit::class, ['user' => $user])
            ->set('name', 'Nouveau Nom')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Nouveau Nom', $user->fresh()->name);
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_a_short_password_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeLaquageUser();
        $hash = $user->password;

        Livewire::actingAs($admin)->test(UsersEdit::class, ['user' => $user])
            ->set('password', 'too-short')
            ->call('update')
            ->assertHasErrors(['password' => 'min']);

        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_password_reset_ends_the_users_other_sessions_only(): void
    {
        config(['session.driver' => 'database']);

        $admin = $this->makeAdmin();
        $user = $this->makeLaquageUser();
        $other = $this->makeLaquageUser();

        foreach ([['u1', $user->id], ['u2', $user->id], ['o1', $other->id]] as [$id, $userId]) {
            DB::table('sessions')->insert([
                'id' => $id, 'user_id' => $userId, 'ip_address' => '127.0.0.1',
                'user_agent' => 'test', 'payload' => '', 'last_activity' => time(),
            ]);
        }

        Livewire::actingAs($admin)->test(UsersEdit::class, ['user' => $user])
            ->set('password', 'a-brand-new-password')
            ->call('update');

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'o1']);
    }

    public function test_only_admins_can_open_the_edit_page(): void
    {
        $user = $this->makeLaquageUser();

        $this->actingAs($user)->get(route('admin.users.edit', $user))->assertForbidden();
    }
}
