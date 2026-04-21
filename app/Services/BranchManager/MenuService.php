<?php

namespace App\Services\BranchManager;

use App\Models\Menu\ItemOption;
use App\Models\Menu\ItemOptionGroup;
use App\Models\Menu\Menu;
use App\Models\Menu\MenuCategory;
use App\Models\Menu\MenuItem;

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
        ]);

        foreach ($request->categories as $categoryData) {

            $category = MenuCategory::create([
                'menu_id' => $menu->id,
                'name' => $categoryData['name'],
            ]);

            foreach ($categoryData['items'] as $itemData) {

                $item = MenuItem::create([
                    'category_id' => $category->id,
                    'name' => $itemData['name'],
                    'description' => $itemData['description'],
                    'image_url' => $itemData['image_url'] ?? '',
                    'price' => $itemData['price'],
                    'preparation_time_minutes' => $itemData['preparation_time_minutes'],
                ]);

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
    }
}
