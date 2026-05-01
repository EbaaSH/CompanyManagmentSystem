<?php

namespace Database\Seeders;

use App\Models\Customer\CustomerProfile;
use App\Models\Menu\MenuItem;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AiTrainingDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedMenuItems();
            $customers = $this->seedCustomers(300);
            $this->seedOrders($customers, 5000);
        });
    }

    private function seedMenuItems(): void
    {
        $items = [
            ['name' => 'English Dinner', 'price' => 14.99, 'group' => 'dinner'],
            ['name' => 'Coca Cola', 'price' => 2.49, 'group' => 'drink'],
            ['name' => 'Pepperoni Pizza', 'price' => 12.99, 'group' => 'pizza'],
            ['name' => 'Cheese Pizza', 'price' => 10.99, 'group' => 'pizza'],
            ['name' => 'BBQ Chicken Pizza', 'price' => 13.99, 'group' => 'pizza'],
            ['name' => 'Chicken Wings', 'price' => 8.99, 'group' => 'side'],
            ['name' => 'Buffalo Wings', 'price' => 9.49, 'group' => 'side'],
            ['name' => 'French Fries', 'price' => 4.99, 'group' => 'side'],
            ['name' => 'Garlic Bread', 'price' => 4.49, 'group' => 'side'],
            ['name' => 'Breadsticks', 'price' => 5.49, 'group' => 'side'],
            ['name' => 'Chicken Burger', 'price' => 9.99, 'group' => 'burger'],
            ['name' => 'Beef Burger', 'price' => 10.99, 'group' => 'burger'],
            ['name' => 'Chicken Sandwich', 'price' => 8.99, 'group' => 'sandwich'],
            ['name' => 'Philly Cheesesteak', 'price' => 11.99, 'group' => 'sandwich'],
            ['name' => 'Caesar Salad', 'price' => 7.99, 'group' => 'healthy'],
            ['name' => 'Greek Salad', 'price' => 8.49, 'group' => 'healthy'],
            ['name' => 'Garden Salad', 'price' => 6.99, 'group' => 'healthy'],
            ['name' => 'Water Bottle', 'price' => 1.99, 'group' => 'drink'],
            ['name' => 'Orange Juice', 'price' => 3.49, 'group' => 'drink'],
            ['name' => 'Pepsi', 'price' => 2.49, 'group' => 'drink'],
            ['name' => 'Diet Pepsi', 'price' => 2.49, 'group' => 'drink'],
            ['name' => 'Mountain Dew', 'price' => 2.49, 'group' => 'drink'],
            ['name' => 'Chocolate Cake', 'price' => 5.99, 'group' => 'dessert'],
            ['name' => 'Chocolate Brownie', 'price' => 4.49, 'group' => 'dessert'],
            ['name' => 'Cheesecake', 'price' => 5.49, 'group' => 'dessert'],
            ['name' => 'Ice Cream', 'price' => 3.99, 'group' => 'dessert'],
            ['name' => 'Kids Cheese Pizza', 'price' => 6.99, 'group' => 'kids'],
            ['name' => 'Kids Nuggets', 'price' => 6.49, 'group' => 'kids'],
            ['name' => 'Spaghetti', 'price' => 11.99, 'group' => 'pasta'],
            ['name' => 'Chicken Alfredo', 'price' => 13.49, 'group' => 'pasta'],
        ];

        foreach ($items as $item) {
            MenuItem::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    // Change category_id based on your real database.
                    'category_id' => 1,
                    'description' => $item['name'].' test item',
                    'image_url' => 'default-food.png',
                    'price' => $item['price'],
                    'is_available' => true,
                    'preparation_time_minutes' => rand(5, 25),
                ]
            );
        }
    }

    private function seedCustomers(int $count): array
    {
        $customers = [];

        for ($i = 1; $i <= $count; $i++) {
            $user = User::query()->create([
                'name' => 'AI Customer '.$i,
                'email' => 'ai_customer_'.$i.'@example.com',
                'phone' => '555000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'phone_verified_at' => now(),
            ]);

            $customer = CustomerProfile::query()->create([
                'user_id' => $user->id,
                'loyalty_points' => rand(0, 500),
                'is_active' => true,
            ]);

            $customers[] = $customer;
        }

        return $customers;
    }

    private function seedOrders(array $customers, int $orderCount): void
    {
        $menuItems = MenuItem::query()
            ->where('is_available', true)
            ->get();

        $itemsByName = $menuItems->keyBy('name');

        $behaviorGroups = [
            'pizza_lover' => [
                'core' => ['Pepperoni Pizza', 'Cheese Pizza', 'BBQ Chicken Pizza'],
                'addons' => ['Chicken Wings', 'Breadsticks', 'Pepsi', 'Coca Cola', 'Chocolate Brownie'],
            ],
            'family_dinner' => [
                'core' => ['English Dinner', 'Kids Cheese Pizza', 'Kids Nuggets'],
                'addons' => ['French Fries', 'Coca Cola', 'Chocolate Cake', 'Water Bottle'],
            ],
            'lunch' => [
                'core' => ['Chicken Sandwich', 'Chicken Burger', 'Philly Cheesesteak'],
                'addons' => ['French Fries', 'Coca Cola', 'Orange Juice'],
            ],
            'healthy' => [
                'core' => ['Caesar Salad', 'Greek Salad', 'Garden Salad'],
                'addons' => ['Water Bottle', 'Orange Juice', 'Chicken Sandwich'],
            ],
            'comfort_food' => [
                'core' => ['Beef Burger', 'Spaghetti', 'Chicken Alfredo'],
                'addons' => ['French Fries', 'Garlic Bread', 'Coca Cola', 'Cheesecake'],
            ],
        ];

        $customerGroups = [];

        foreach ($customers as $customer) {
            $customerGroups[$customer->id] = array_rand($behaviorGroups);
        }

        for ($i = 1; $i <= $orderCount; $i++) {
            $customer = $customers[array_rand($customers)];
            $groupName = $customerGroups[$customer->id];
            $group = $behaviorGroups[$groupName];

            $selectedNames = [];

            // Pick 1–2 core items.
            $coreCount = rand(1, 2);
            $selectedNames = array_merge(
                $selectedNames,
                $this->randomItems($group['core'], $coreCount)
            );

            // Pick 1–3 addon items.
            $addonCount = rand(1, 3);
            $selectedNames = array_merge(
                $selectedNames,
                $this->randomItems($group['addons'], $addonCount)
            );

            // Add some randomness from all menu items.
            if (rand(1, 100) <= 20) {
                $selectedNames[] = $menuItems->random()->name;
            }

            $selectedNames = array_values(array_unique($selectedNames));

            $order = Order::query()->create([
                'order_number' => 'AI-'.Str::upper(Str::random(10)).'-'.$i,
                'customer_id' => $customer->id,

                // Change these based on your real database.
                'company_id' => 1,
                'branch_id' => 1,
                'delivery_address_id' => 1,
                'driver_id' => 1,

                'status' => 'delivered',
                'notes' => 'AI training fake order',
            ]);

            foreach ($selectedNames as $itemName) {
                if (! isset($itemsByName[$itemName])) {
                    continue;
                }

                $item = $itemsByName[$itemName];
                $quantity = rand(1, 4);
                $price = (float) $item->price;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item->id,
                    'item_name_snapshot' => $item->name,
                    'item_price_snapshot' => $price,
                    'quantity' => $quantity,
                    'notes' => '',
                    'line_total' => $price * $quantity,
                ]);
            }
        }
    }

    private function randomItems(array $items, int $count): array
    {
        shuffle($items);

        return array_slice($items, 0, min($count, count($items)));
    }
}
