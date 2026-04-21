<?php

namespace App\Services\PlatformQueryServices;

use App\Models\Menu\Menu;

class MenuQueryService
{
    public function getMenuById($id)
    {

        $user = auth()->user();
        $branch = $user->ownedBranch;
        $menu = Menu::forUserViaPermission($user)
            ->where('branch_id', $branch->id)
            ->find($id);
        if (! $menu) {
            return [
                'data' => $menu,
                'message' => 'menu not found',
                'code' => 404,
            ];
        }

        return [
            'data' => $menu->load(
                'branch',
                'categories',
                'categories.menuItems',
                'categories.menuItems.itemOptionGroups',
                'categories.menuItems.itemOptionGroups.itemOptions'
            ),
            'message' => 'menu retrevied successfully',
            'code' => 200,
        ];
    }

    public function getAllMenus()
    {
        $user = auth()->user();
        $menu = Menu::forUserViaPermission($user)
            ->with('branch',
                'categories',
                'categories.menuItems',
                'categories.menuItems.itemOptionGroups',
                'categories.menuItems.itemOptionGroups.itemOptions')
            ->paginate(10);

        return [
            'data' => $menu,
            'message' => 'menu retrevied successfully',
            'code' => 200,
        ];
    }
}
