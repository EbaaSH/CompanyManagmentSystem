<?php

namespace App\Http\Requests\Customer;

use App\Http\Responses\Response;
use App\Models\Menu\MenuItem;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'delivery_address_id' => [
                'required',
                Rule::exists('customer_addresses', 'id')
                    ->where(function ($query) {
                        $query->where('customer_id', auth()->user()->customerProfile->id);
                    }),
            ],
            'notes' => 'nullable|string',
            'payment_method' => 'required|in:cash,card,wallet',

            'items' => 'required|array|min:1',

            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',

            'items.*.options' => 'nullable|array',
            'items.*.options.*.option_id' => 'required|exists:item_options,id',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            foreach ($this->items as $itemIndex => $item) {

                // =========================
                // 1. LOAD MENU ITEM WITH RELATIONS
                // =========================
                $menuItem = MenuItem::with('category.menu', 'itemOptionGroups.itemOptions')
                    ->find($item['menu_item_id']);

                if (!$menuItem) {
                    continue;
                }

                // =========================
                // 2. CHECK ITEM BELONGS TO BRANCH
                // =========================
                if ($menuItem->category->menu->branch_id != $this->branch_id) {
                    $validator->errors()->add(
                        "items.$itemIndex.menu_item_id",
                        "Item does not belong to selected branch"
                    );
                }

                // =========================
                // 3. CHECK ITEM AVAILABLE
                // =========================
                if (!$menuItem->is_available) {
                    $validator->errors()->add(
                        "items.$itemIndex.menu_item_id",
                        "Item is not available"
                    );
                }

                // =========================
                // 4. VALID OPTIONS FOR ITEM
                // =========================
                $validOptionIds = $menuItem->itemOptionGroups
                    ->flatMap(fn($group) => $group->itemOptions->pluck('id'))
                    ->toArray();

                $selectedOptions = collect($item['options'] ?? []);

                foreach ($selectedOptions as $optionIndex => $option) {

                    if (!in_array($option['option_id'], $validOptionIds)) {
                        $validator->errors()->add(
                            "items.$itemIndex.options.$optionIndex.option_id",
                            "Invalid option for this menu item"
                        );
                    }
                }

                // =========================
                // 5. VALIDATE GROUP RULES
                // =========================
                foreach ($menuItem->itemOptionGroups as $group) {

                    $groupOptionIds = $group->itemOptions->pluck('id')->toArray();

                    $count = $selectedOptions
                        ->whereIn('option_id', $groupOptionIds)
                        ->count();

                    if ($group->is_required && $count < $group->min_select) {
                        $validator->errors()->add(
                            "items.$itemIndex.options",
                            "You must select at least {$group->min_select} options for {$group->name}"
                        );
                    }

                    if ($count > $group->max_select) {
                        $validator->errors()->add(
                            "items.$itemIndex.options",
                            "You can select at most {$group->max_select} options for {$group->name}"
                        );
                    }
                }
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException(
            $validator,
            Response::Validation([], $validator->errors())
        );
    }
}
