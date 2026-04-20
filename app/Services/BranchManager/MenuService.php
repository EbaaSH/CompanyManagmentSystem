<?php

namespace App\Services\BranchManager;

use App\Models\Menu\Menu;

class MenuService
{
    public function createMenu($request)
    {
        $user = auth()->user();
        $branch = $user->ownedBranch();
        $menu = Menu::create([
            'branch_id' => $branch->id,
            'name' => $request->name,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_active' => $request->is_active ?? true,
        ]);

    }
}
