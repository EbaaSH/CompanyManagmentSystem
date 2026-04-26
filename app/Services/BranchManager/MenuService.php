<?php

namespace App\Services\BranchManager;

use App\Models\Menu\ItemOption;
use App\Models\Menu\ItemOptionGroup;
use App\Models\Menu\Menu;
use App\Models\Menu\MenuCategory;
use App\Models\Menu\MenuItem;
use App\Traits\UploadImage;
use Illuminate\Support\Facades\Storage;

class MenuService
{
    use UploadImage;

    public function createMenu($request)
    {
        $user = auth()->user();
        $branch = $user->ownedBranch;
        if (!$branch) {
            return [
                'data' => null,
                'message' => 'Branch not found for this user',
                'code' => 404,
            ];
        }
        $menu = Menu::create([
            'branch_id' => $branch->id,
            'name' => $request->name,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        foreach ($request->categories as $cindex => $categoryData) {

            $category = MenuCategory::create([
                'menu_id' => $menu->id,
                'name' => $categoryData['name'],
            ]);

            foreach ($categoryData['items'] as $iindex => $itemData) {

                $item = MenuItem::create([
                    'category_id' => $category->id,
                    'name' => $itemData['name'],
                    'description' => $itemData['description'] ?? null,
                    'image_url' => $path['data'] ?? '',
                    'price' => $itemData['price'],
                    'preparation_time_minutes' => $itemData['preparation_time_minutes'],
                ]);

                // ========================
                $requestFileKey = "categories.$cindex.items.$iindex.image";

                $path = $this->uploadImage(
                    $request,
                    "menus/{$menu->id}/items/{$item->name}",
                    $requestFileKey
                );

                if (!$path['success']) {
                    return [
                        'data' => null,
                        'message' => $path['message'] ?? "File upload failed at index {$iindex}",
                        'code' => 422,
                    ];
                }
                // =======================

                // Option Groups
                if (!empty($itemData['option_groups'])) {
                    foreach ($itemData['option_groups'] as $groupData) {

                        $group = ItemOptionGroup::create([
                            'item_id' => $item->id,
                            'name' => $groupData['name'],
                            'min_select' => $groupData['min_select'] ?? 0,
                            'max_select' => $groupData['max_select'] ?? 1,
                            'is_required' => $groupData['is_required'] ?? 0,
                        ]);

                        // Options
                        if (!empty($groupData['options'])) {
                            foreach ($groupData['options'] as $optionData) {

                                ItemOption::create([
                                    'option_group_id' => $group->id,
                                    'name' => $optionData['name'],
                                    'extra_price' => $optionData['extra_price'] ?? 0,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        return [
            'data' => $menu->load(
                'branch',
                'categories',
                'categories.menuItems',
                'categories.menuItems.itemOptionGroups',
                'categories.menuItems.itemOptionGroups.itemOptions'
            ),
            'message' => 'menu created successfully',
            'code' => 201,

        ];
    }

    public function updateMenu($request, $menuId)
    {
        $user = auth()->user();
        $branch = $user->ownedBranch;

        $menu = Menu::query()
            ->forUserViaPermission($user)
            ->where('branch_id', $branch->id)
            ->find($menuId);

        $menu->update([
            'name' => $request->name,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        foreach ($request->categories as $cIndex => $categoryData) {

            $category = MenuCategory::updateOrCreate(
                ['id' => $categoryData['id'] ?? null],
                [
                    'menu_id' => $menu->id,
                    'name' => $categoryData['name'],
                ]
            );

            foreach ($categoryData['items'] as $iIndex => $itemData) {

                $existingItem = null;

                if (!empty($itemData['id'])) {
                    $existingItem = MenuItem::find($itemData['id']);
                }

                $fileKey = "categories.$cIndex.items.$iIndex.image";
                $imagePath = $existingItem->image_url ?? ""; // ✅ keep old image by default

                if (!$existingItem && !$request->hasFile($fileKey)) {
                    return [
                        'data' => null,
                        'message' => 'Image is required for new item',
                        'code' => 422
                    ];
                }

                if ($request->hasFile($fileKey)) {

                    // delete old image
                    if ($existingItem && $existingItem->image_url && Storage::exists($existingItem->image_url)) {
                        Storage::delete($existingItem->image_url);
                    }

                    // upload new
                    $upload = $this->uploadImage(
                        $request,
                        "menus/{$menu->id}/items/{$itemData['name']}",
                        $fileKey
                    );

                    if (!$upload['success']) {
                        return [
                            'data' => null,
                            'message' => 'file upload failed',
                            'code' => 400
                        ];
                    }

                    $imagePath = $upload['data']; // ✅ new image
                }

                $item = MenuItem::updateOrCreate(
                    ['id' => $itemData['id'] ?? null],
                    [
                        'category_id' => $category->id,
                        'name' => $itemData['name'],
                        'description' => $itemData['description'] ?? null,
                        'image_url' => $imagePath, // ✅ never lost
                        'price' => $itemData['price'],
                        'preparation_time_minutes' => $itemData['preparation_time_minutes'],
                    ]
                );





                // OPTION GROUPS (same as before)
                if (!empty($itemData['option_groups'])) {
                    foreach ($itemData['option_groups'] as $groupData) {

                        $group = ItemOptionGroup::updateOrCreate(
                            ['id' => $groupData['id'] ?? null],
                            [
                                'item_id' => $item->id,
                                'name' => $groupData['name'],
                                'min_select' => $groupData['min_select'] ?? 0,
                                'max_select' => $groupData['max_select'] ?? 1,
                                'is_required' => $groupData['is_required'] ?? 0,
                            ]
                        );

                        if (!empty($groupData['options'])) {
                            foreach ($groupData['options'] as $optionData) {

                                ItemOption::updateOrCreate(
                                    ['id' => $optionData['id'] ?? null],
                                    [
                                        'option_group_id' => $group->id,
                                        'name' => $optionData['name'],
                                        'extra_price' => $optionData['extra_price'] ?? 0,
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }

        $menu->fresh(['categories.menuItems.itemOptionGroups.itemOptions']);

        return [
            'data' => $menu->load(
                'branch',
                'categories',
                'categories.menuItems',
                'categories.menuItems.itemOptionGroups',
                'categories.menuItems.itemOptionGroups.itemOptions'
            ),
            'message' => 'menu updated successfully',
            'code' => 200,

        ];
    }
    public function deleteMenu($menuId)
    {
        $user = auth()->user();

        $menu = Menu::query()
            ->forUserViaPermission($user)
            ->find($menuId);

        if (!$menu) {
            return [
                'data' => null,
                'message' => 'Menu not found',
                'code' => 404,
            ];
        }

        $menu->delete(); // 🔥 cascade handled in model

        return [
            'data' => null,
            'message' => 'Menu deleted successfully',
            'code' => 200,
        ];
    }
    public function restoreMenu($menuId)
    {
        $user = auth()->user();

        $menu = Menu::query()
            ->forUserViaPermission($user)
            ->withTrashed()
            ->find($menuId);

        if (!$menu) {
            return [
                'data' => null,
                'message' => 'Menu not found',
                'code' => 404,
            ];
        }

        $menu->restore(); // 🔥 cascade handled in model

        return [
            'data' => $menu->load(
                'categories.menuItems.itemOptionGroups.itemOptions'
            ),
            'message' => 'Menu restored successfully',
            'code' => 200,
        ];
    }
}
