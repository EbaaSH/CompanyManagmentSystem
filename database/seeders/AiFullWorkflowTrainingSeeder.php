<?php

namespace Database\Seeders;

use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\Customer\CustomerAddress;
use App\Models\Customer\CustomerProfile;
use App\Models\Driver\DriverProfile;
use App\Models\Menu\Menu;
use App\Models\Menu\MenuCategory;
use App\Models\Menu\MenuItem;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AiFullWorkflowTrainingSeeder extends Seeder
{
    private int $companiesCount = 2;

    private int $branchesPerCompany = 2;

    private int $driversPerBranch = 4;

    private int $customersCount = 300;

    private int $ordersCount = 5000;

    public function run(): void
    {
        DB::transaction(function () {
            $companies = $this->createCompaniesWithBranchesAndDrivers();
            $customers = $this->createCustomersWithAddresses();
            $this->createOrdersWithItems($companies, $customers);
        });
    }

    private function createCompaniesWithBranchesAndDrivers(): array
    {
        $companies = [];

        for ($companyIndex = 1; $companyIndex <= $this->companiesCount; $companyIndex++) {
            $companyAdmin = $this->createUser(
                name: "AI Company Admin {$companyIndex}",
                email: "ai_company_admin_{$companyIndex}@example.com",
                phone: "555100{$companyIndex}"
            );

            $company = Company::query()->create([
                'user_id' => $companyAdmin->id,
                'name' => "AI Restaurant Company {$companyIndex}",
                'legal_name' => "AI Restaurant Company {$companyIndex} LLC",
                'email' => "company_{$companyIndex}@example.com",
                'phone' => "555200{$companyIndex}",
                'status' => 'active',
            ]);

            $branches = [];

            for ($branchIndex = 1; $branchIndex <= $this->branchesPerCompany; $branchIndex++) {
                $branchManager = $this->createUser(
                    name: "AI Branch Manager {$companyIndex}-{$branchIndex}",
                    email: "ai_branch_manager_{$companyIndex}_{$branchIndex}@example.com",
                    phone: "555300{$companyIndex}{$branchIndex}"
                );

                $branch = Branch::query()->create([
                    'company_id' => $company->id,
                    'user_id' => $branchManager->id,
                    'name' => "AI Branch {$companyIndex}-{$branchIndex}",
                    'code' => "AI-BR-{$companyIndex}-{$branchIndex}-".Str::upper(Str::random(4)),
                    'address' => "{$branchIndex} AI Food Street",
                    'city' => 'New York',
                    'latitude' => 40.7128000 + ($branchIndex / 1000),
                    'longitude' => -74.0060000 - ($branchIndex / 1000),
                    'phone' => "555400{$companyIndex}{$branchIndex}",
                    'is_active' => true,
                ]);

                $this->createMenuForBranch($branch);

                $drivers = $this->createDriversForBranch($company, $branch);

                $branches[] = [
                    'model' => $branch,
                    'drivers' => $drivers,
                ];
            }

            $companies[] = [
                'model' => $company,
                'branches' => $branches,
            ];
        }

        return $companies;
    }

    private function createMenuForBranch(Branch $branch): void
    {
        $menu = Menu::query()->create([
            'branch_id' => $branch->id,
            'name' => "Main Menu - {$branch->name}",
            'description' => "AI training menu for {$branch->name}",
            'is_active' => true,
            'start_time' => '08:00:00',
            'end_time' => '23:00:00',
        ]);

        $categories = [
            'Pizza' => [
                ['Pepperoni Pizza', 12.99, 18],
                ['Cheese Pizza', 10.99, 15],
                ['BBQ Chicken Pizza', 13.99, 20],
                ['Veggie Pizza', 11.99, 18],
                ['Meat Lovers Pizza', 14.99, 22],
            ],
            'Sides' => [
                ['Chicken Wings', 8.99, 12],
                ['Buffalo Wings', 9.49, 13],
                ['French Fries', 4.99, 7],
                ['Garlic Bread', 4.49, 6],
                ['Breadsticks', 5.49, 7],
            ],
            'Burgers & Sandwiches' => [
                ['Chicken Burger', 9.99, 10],
                ['Beef Burger', 10.99, 12],
                ['Chicken Sandwich', 8.99, 10],
                ['Philly Cheesesteak', 11.99, 13],
            ],
            'Healthy' => [
                ['Caesar Salad', 7.99, 8],
                ['Greek Salad', 8.49, 8],
                ['Garden Salad', 6.99, 7],
                ['Water Bottle', 1.99, 1],
            ],
            'Drinks' => [
                ['Coca Cola', 2.49, 1],
                ['Pepsi', 2.49, 1],
                ['Diet Pepsi', 2.49, 1],
                ['Mountain Dew', 2.49, 1],
                ['Orange Juice', 3.49, 1],
            ],
            'Desserts' => [
                ['Chocolate Cake', 5.99, 3],
                ['Chocolate Brownie', 4.49, 3],
                ['Cheesecake', 5.49, 3],
                ['Ice Cream', 3.99, 2],
            ],
            'Dinner' => [
                ['English Dinner', 14.99, 20],
                ['Spaghetti', 11.99, 16],
                ['Chicken Alfredo', 13.49, 18],
                ['Lasagna', 13.99, 20],
            ],
            'Kids' => [
                ['Kids Cheese Pizza', 6.99, 10],
                ['Kids Nuggets', 6.49, 8],
            ],
        ];

        $sortOrder = 1;

        foreach ($categories as $categoryName => $items) {
            $category = MenuCategory::query()->create([
                'menu_id' => $menu->id,
                'name' => $categoryName,
                'sort_order' => $sortOrder++,
                'is_active' => true,
            ]);

            foreach ($items as [$name, $price, $prepTime]) {
                MenuItem::query()->create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'description' => "{$name} generated for AI recommendation training.",
                    'image_url' => 'default-food.png',
                    'price' => $price,
                    'is_available' => true,
                    'preparation_time_minutes' => $prepTime,
                ]);
            }
        }
    }

    private function createDriversForBranch(Company $company, Branch $branch): array
    {
        $drivers = [];

        for ($i = 1; $i <= $this->driversPerBranch; $i++) {
            $driverUser = $this->createUser(
                name: "AI Driver {$company->id}-{$branch->id}-{$i}",
                email: "ai_driver_{$company->id}_{$branch->id}_{$i}@example.com",
                phone: "555500{$company->id}{$branch->id}{$i}",

            );

            $drivers[] = DriverProfile::query()->create([
                'user_id' => $driverUser->id,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'vehicle_type' => fake()->randomElement(['car', 'bike', 'scooter']),
                'plate_number' => 'AI-'.Str::upper(Str::random(6)),
                'availability_status' => 'available',
                'current_latitude' => $branch->latitude,
                'current_longitude' => $branch->longitude,
                'is_active' => true,
            ]);
        }

        return $drivers;
    }

    private function createCustomersWithAddresses(): array
    {
        $customers = [];

        for ($i = 1; $i <= $this->customersCount; $i++) {
            $user = $this->createUser(
                name: "AI Customer {$i}",
                email: "ai_customer_{$i}@example.com",
                phone: '555900'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),

            );

            $customer = CustomerProfile::query()->create([
                'user_id' => $user->id,
                'loyalty_points' => rand(0, 1000),
                'is_active' => true,
            ]);

            $customer->ai_address = CustomerAddress::query()->create([
                'customer_id' => $customer->id,
                'label' => 'Home',
                'address_line' => fake()->streetAddress(),
                'city' => 'New York',
                'latitude' => 40.7128000 + (rand(-100, 100) / 10000),
                'longitude' => -74.0060000 + (rand(-100, 100) / 10000),
                'is_default' => true,
            ]);

            $customer->ai_behavior_group = fake()->randomElement([
                'pizza_lover',
                'family_dinner',
                'lunch',
                'healthy',
                'comfort_food',
                'dessert_lover',
                'kids_family',
            ]);

            $customers[] = $customer;
        }

        return $customers;
    }

    private function createOrdersWithItems(array $companies, array $customers): void
    {
        for ($i = 1; $i <= $this->ordersCount; $i++) {
            $companyData = fake()->randomElement($companies);

            $company = $companyData['model'];
            $branchData = fake()->randomElement($companyData['branches']);

            $branch = $branchData['model'];
            $driver = fake()->randomElement($branchData['drivers']);

            $customer = fake()->randomElement($customers);

            $menuItems = $this->getMenuItemsForBranch($branch);

            if ($menuItems->isEmpty()) {
                continue;
            }

            $selectedItems = $this->selectItemsForBehaviorGroup(
                $customer->ai_behavior_group,
                $menuItems
            );

            if ($selectedItems->isEmpty()) {
                continue;
            }

            $order = Order::query()->create([
                'order_number' => 'AI-'.now()->format('YmdHis').'-'.$i.'-'.Str::upper(Str::random(5)),
                'customer_id' => $customer->id,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'delivery_address_id' => $customer->ai_address->id,
                'driver_id' => $driver->id,
                'status' => 'delivered',
                'notes' => 'AI training fake delivered order',
            ]);

            foreach ($selectedItems as $item) {
                $quantity = fake()->numberBetween(1, 4);
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

    private function getMenuItemsForBranch(Branch $branch)
    {
        return MenuItem::query()
            ->where('is_available', true)
            ->whereHas('category.menu', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            })
            ->get();
    }

    private function selectItemsForBehaviorGroup(string $group, $menuItems)
    {
        $patterns = [
            'pizza_lover' => [
                'core' => ['Pepperoni Pizza', 'Cheese Pizza', 'BBQ Chicken Pizza', 'Veggie Pizza'],
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
                'core' => ['Beef Burger', 'Spaghetti', 'Chicken Alfredo', 'Lasagna'],
                'addons' => ['French Fries', 'Garlic Bread', 'Coca Cola', 'Cheesecake'],
            ],
            'dessert_lover' => [
                'core' => ['Chocolate Cake', 'Chocolate Brownie', 'Cheesecake', 'Ice Cream'],
                'addons' => ['Coca Cola', 'Orange Juice', 'Kids Cheese Pizza'],
            ],
            'kids_family' => [
                'core' => ['Kids Cheese Pizza', 'Kids Nuggets', 'Cheese Pizza'],
                'addons' => ['French Fries', 'Water Bottle', 'Ice Cream'],
            ],
        ];

        $pattern = $patterns[$group] ?? $patterns['pizza_lover'];

        $selectedNames = [];

        $selectedNames = array_merge(
            $selectedNames,
            $this->randomNames($pattern['core'], fake()->numberBetween(1, 2))
        );

        $selectedNames = array_merge(
            $selectedNames,
            $this->randomNames($pattern['addons'], fake()->numberBetween(1, 3))
        );

        if (fake()->numberBetween(1, 100) <= 20) {
            $selectedNames[] = $menuItems->random()->name;
        }

        $selectedNames = array_values(array_unique($selectedNames));

        return $menuItems->filter(function ($item) use ($selectedNames) {
            return in_array($item->name, $selectedNames, true);
        })->values();
    }

    private function randomNames(array $names, int $count): array
    {
        shuffle($names);

        return array_slice($names, 0, min($count, count($names)));
    }

    private function createUser(
        string $name,
        string $email,
        string $phone,
    ): User {
        return User::query()->create([
            'name' => $name,
            'email' => Str::uuid().'_'.$email,
            'phone' => $phone,
            'password' => Hash::make('password'),

        ]);
    }
}
