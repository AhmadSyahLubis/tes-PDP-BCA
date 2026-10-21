<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Branch;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() && ! config('demo.seed')) {
            return;
        }

        $area = Area::firstOrCreate(['name' => 'Jakarta']);
        Area::firstOrCreate(['name' => 'Bandung']);
        $branch = Branch::firstOrCreate(['area_id' => $area->id, 'name' => 'Jakarta Pusat']);
        $groups = [];
        foreach (['marketing' => 'Marketing', 'approver' => 'Atasan Marketing', 'backoffice' => 'Admin Backoffice'] as $code => $name) {
            $groups[$code] = Group::firstOrCreate(['code' => $code], ['name' => $name]);
        }

        $menus = [
            ['name' => 'Pengajuan', 'url' => '/applications'],
            ['name' => 'Pengajuan Baru', 'url' => '/applications/create'],
            ['name' => 'Laporan', 'url' => '/reports'],
        ];
        foreach ($menus as $menu) {
            DB::table('menus')->updateOrInsert(['url' => $menu['url']], $menu + ['is_active' => true]);
        }
        $menuIds = DB::table('menus')->pluck('id', 'url');
        foreach (['marketing' => ['/applications', '/applications/create'],
            'approver' => ['/applications', '/reports'],
            'backoffice' => ['/applications', '/reports']] as $role => $urls) {
            foreach ($urls as $url) {
                DB::table('group_menu')->insertOrIgnore(['group_id' => $groups[$role]->id, 'menu_id' => $menuIds[$url]]);
            }
        }

        $password = env('DEMO_PASSWORD', 'Demo12345!');
        foreach (['marketing' => 'Petugas Marketing', 'approver' => 'Atasan Marketing', 'backoffice' => 'Admin Backoffice'] as $code => $name) {
            User::updateOrCreate(['username' => $code], [
                'name' => $name, 'email' => $code.'@example.test', 'password' => $password,
                'group_id' => $groups[$code]->id, 'branch_id' => $branch->id, 'is_active' => true,
            ]);
        }

        $this->call(DemoCreditApplicationSeeder::class);
    }
}
