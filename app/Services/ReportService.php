<?php

namespace App\Services;

use App\Models\Area;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function summary(): array
    {
        if (DB::getDriverName() === 'pgsql') {
            return [
                'active_users' => DB::table('active_users')->orderBy('username')->get(),
                'access' => DB::table('user_menu_access')->orderBy('username')->orderBy('menu_name')->get(),
                'areas' => DB::table('active_user_count_per_area')->orderBy('area_name')->get(),
            ];
        }

        $activeUsers = User::query()->join('groups', 'groups.id', '=', 'users.group_id')
            ->join('branches', 'branches.id', '=', 'users.branch_id')
            ->join('areas', 'areas.id', '=', 'branches.area_id')
            ->where('users.is_active', true)
            ->select('users.id', 'users.username', 'users.name', 'groups.name as group_name',
                'branches.name as branch_name', 'areas.name as area_name')
            ->orderBy('users.username')->get();
        $access = DB::table('users')->join('groups', 'groups.id', '=', 'users.group_id')
            ->join('group_menu', 'group_menu.group_id', '=', 'groups.id')
            ->join('menus', 'menus.id', '=', 'group_menu.menu_id')
            ->where('users.is_active', true)->where('menus.is_active', true)
            ->select('users.id as user_id', 'users.username', 'groups.name as group_name',
                'menus.id as menu_id', 'menus.name as menu_name', 'menus.url', 'menus.parent_id')
            ->orderBy('users.username')->orderBy('menus.name')->get();
        $areas = Area::query()->leftJoin('branches', 'branches.area_id', '=', 'areas.id')
            ->leftJoin('users', function ($join) {
                $join->on('users.branch_id', '=', 'branches.id')->where('users.is_active', '=', true);
            })
            ->select('areas.id as area_id', 'areas.name as area_name')
            ->selectRaw('COUNT(users.id) as active_user_count')
            ->groupBy('areas.id', 'areas.name')->orderBy('areas.name')->get();

        return ['active_users' => $activeUsers, 'access' => $access, 'areas' => $areas];
    }
}
