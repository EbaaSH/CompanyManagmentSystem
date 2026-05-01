<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportAiTrainingData extends Command
{
    protected $signature = 'ai:export-training-data';

    protected $description = 'Export restaurant AI training data for the recommendation model';

    public function handle(): int
    {
        $this->info('Exporting AI training data...');

        $directory = storage_path('app/ai');

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $this->exportRestaurantInteractions();
        $this->exportMenuItems();

        $this->info('AI training data exported successfully.');

        return self::SUCCESS;
    }

    private function exportRestaurantInteractions(): void
    {
        $directory = storage_path('app/ai');

        $tempPath = $directory.'/restaurant_interactions.tmp';
        $finalPath = $directory.'/restaurant_interactions.csv';

        $file = fopen($tempPath, 'w');

        fputcsv($file, [
            'customer_id',
            'menu_item_id',
            'order_count',
            'quantity_count',
        ]);

        DB::table('orders')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'delivered')
            ->whereNotNull('orders.customer_id')
            ->whereNotNull('order_items.menu_item_id')
            ->select(
                'orders.customer_id',
                'order_items.menu_item_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(order_items.quantity) as quantity_count')
            )
            ->groupBy('orders.customer_id', 'order_items.menu_item_id')
            ->orderBy('orders.customer_id')
            ->chunk(500, function ($rows) use ($file) {
                foreach ($rows as $row) {
                    fputcsv($file, [
                        $row->customer_id,
                        $row->menu_item_id,
                        $row->order_count,
                        $row->quantity_count,
                    ]);
                }
            });

        fclose($file);

        rename($tempPath, $finalPath);
    }

    private function exportMenuItems(): void
    {
        $directory = storage_path('app/ai');

        $tempPath = $directory.'/menu_items.tmp';
        $finalPath = $directory.'/menu_items.csv';

        $file = fopen($tempPath, 'w');

        fputcsv($file, [
            'menu_item_id',
            'name',
        ]);

        DB::table('menu_items')
            ->select(
                'id as menu_item_id',
                'name'
            )
            ->where('is_available', true)
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($file) {
                foreach ($rows as $row) {
                    fputcsv($file, [
                        $row->menu_item_id,
                        $row->name,
                    ]);
                }
            });

        fclose($file);

        rename($tempPath, $finalPath);
    }
}
