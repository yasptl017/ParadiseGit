<?php

namespace App\Services;

use App\Models\Category;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Str;

class PrinterService
{
    protected $kitchenPrinter;
    protected $deskPrinter;
    protected $printMode;   // 'poll' | 'push'
    protected $agentPort;   // local port for push mode (default 5757)

    public function __construct()
    {
        $setting = Setting::select('kitchen_printer', 'desk_printer', 'print_mode', 'agent_port')->first();

        $this->kitchenPrinter = $this->normalizePrinterName(optional($setting)->kitchen_printer);
        $this->deskPrinter    = $this->normalizePrinterName(optional($setting)->desk_printer);
        $this->printMode      = optional($setting)->print_mode ?? 'poll';
        $this->agentPort      = (int)(optional($setting)->agent_port ?? 5757);
    }

    /**
     * Queue a kitchen print job (raw content already formatted).
     */
    public function sendToKitchen($order)
    {
        if (!$this->kitchenPrinter) {
            return "Warning: Kitchen printer is not configured.";
        }

        $job = PrintJob::create([
            'order_id' => $order->id ?? null,
            'printer'  => $this->kitchenPrinter,
            'content'  => is_string($order) ? $order : $this->formatKitchenDetails($order),
            'status'   => PrintJob::STATUS_PENDING,
        ]);

        $this->pushToAgent($job);

        return "Order successfully sent to kitchen printer.";
    }

    /**
     * Queue a desk/receipt print job (raw content already formatted).
     */
    public function sendToDesk($order)
    {
        if (!$this->deskPrinter) {
            return "Warning: Desk printer is not configured.";
        }

        $job = PrintJob::create([
            'order_id' => $order->id ?? null,
            'printer'  => $this->deskPrinter,
            'content'  => is_string($order) ? $order : $this->formatOrderDetails($order),
            'status'   => PrintJob::STATUS_PENDING,
        ]);

        $this->pushToAgent($job);

        return "Order successfully sent to desk printer.";
    }

    /**
     * Format order then queue for kitchen printer.
     */
    public function printToKitchen($order)
    {
        if (!$this->kitchenPrinter) {
            return "Warning: Kitchen printer is not configured.";
        }

        $job = PrintJob::create([
            'order_id' => $order->id ?? null,
            'printer'  => $this->kitchenPrinter,
            'content'  => $this->formatKitchenDetails($order),
            'status'   => PrintJob::STATUS_PENDING,
        ]);

        $this->pushToAgent($job);

        return "Order successfully sent to kitchen printer.";
    }

    /**
     * Format order then queue for desk printer.
     */
    public function printToDesk($order)
    {
        if (!$this->deskPrinter) {
            return "Warning: Desk printer is not configured.";
        }

        $job = PrintJob::create([
            'order_id' => $order->id ?? null,
            'printer'  => $this->deskPrinter,
            'content'  => $this->formatOrderDetails($order),
            'status'   => PrintJob::STATUS_PENDING,
        ]);

        $this->pushToAgent($job);

        return "Order successfully sent to desk printer.";
    }

    public function getFormattedReceipt($order): string
    {
        return $this->formatOrderDetails($order);
    }

    /**
     * If print_mode is 'push', immediately POST the job to the local print agent
     * running on localhost:{agent_port}. Falls back silently if agent is unreachable.
     */
    protected function pushToAgent(PrintJob $job): void
    {
        if ($this->printMode !== 'push') {
            return;
        }

        $url     = "http://127.0.0.1:{$this->agentPort}/print";
        $payload = json_encode([
            'id'       => $job->id,
            'order_id' => $job->order_id,
            'printer'  => $job->printer,
            'content'  => $job->content,
            'key'      => env('PRINT_AGENT_KEY'),
        ]);

        try {
            $context = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
                    'content' => $payload,
                    'timeout' => 3,
                    'ignore_errors' => true,
                ],
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response !== false) {
                $data = json_decode($response, true);
                if (!empty($data['success'])) {
                    $job->status     = PrintJob::STATUS_PRINTED;
                    $job->printed_at = now();
                    $job->save();
                } else {
                    // Agent responded but reported failure
                    $job->status = PrintJob::STATUS_FAILED;
                    $job->error  = $data['error'] ?? 'Agent reported failure';
                    $job->save();
                }
            } else {
                // Agent unreachable (not running or connection refused)
                $job->status = PrintJob::STATUS_FAILED;
                $job->error  = 'Print agent unreachable';
                $job->save();
            }
        } catch (\Throwable $e) {
            $job->status = PrintJob::STATUS_FAILED;
            $job->error  = $e->getMessage();
            $job->save();
        }
    }

    protected function formatOrderDetails($order): string
    {
        $output   = "";
        $subtotal = 0;

        $output .= "[CENTER][BOLD]Punjabi Paradise[/BOLD][/CENTER]\n";
        $output .= "[CENTER]419 High Street, Penrith, 2750[/CENTER]\n";
        $output .= "[CENTER]Ph: 0247076700  ABN: 86673991529[/CENTER]\n";
        $output .= "[CENTER]www.punjabiparadise.com.au[/CENTER]\n";
        $output .= str_repeat("-", 42) . "\n";
        $output .= "Customer: " . $order->customerDetails . "\n";
        if ($order->type === 'DineIn') {
            $output .= "Table: " . $order->tableNumber . "\n";
        }
        $output .= str_repeat("-", 42) . "\n";
        $output .= "[BOLD]Order No: " . $order->id . "[/BOLD]\n";
        $output .= "Order Type: " . $order->type . "\n";
        $output .= "Date: " . ($order->order_date ?? date('d/m/Y H:i')) . "\n";
        $output .= str_repeat("-", 42) . "\n";
        $output .= "Items:\n";
        $output .= str_repeat("-", 42) . "\n";
        $output .= sprintf("%-4s %-30s %6s\n", "Qty", "Item", "Amount");
        $output .= str_repeat("-", 42) . "\n";

        $sortedItems = $this->sortItemsByReceiptCategoryOrder($order->items ?? []);

        foreach ($sortedItems as $item) {
            $subtotal += $item->price;

            $name = (empty($item->size) || strtolower($item->size) === 'regular')
                ? $item->name
                : $item->name . '-' . $item->size;

            if (strlen($name) > 30) {
                $output .= sprintf("%-4s %-30s %6.2f\n", $item->quantity, substr($name, 0, 30), $item->price);
                $output .= sprintf("%-4s %-30s\n", "", substr($name, 30));
            } else {
                $output .= sprintf("%-4s %-30s %6.2f\n", $item->quantity, $name, $item->price);
            }

            $output .= "\n";
        }

        $output .= str_repeat("-", 42) . "\n";
        $output .= sprintf("%-30s %12s\n", "Subtotal:", "$" . number_format($subtotal, 2));

        $couponLabel = !empty($order->coupon_name)
            ? "Discount (" . $order->coupon_name . "):"
            : "Discount:";
        $output .= sprintf("%-30s %12s\n", $couponLabel, "$" . number_format($order->discount, 2));
        $output .= sprintf("%-30s %12s\n", "Delivery Charge:", "$" . number_format($order->delivery, 2));
        $output .= str_repeat("-", 42) . "\n";
        $output .= "[BOLD]" . sprintf("%-30s %12s", "Total:", "$" . number_format($order->total, 2)) . "[/BOLD]\n";
        $output .= str_repeat("-", 42) . "\n";

        if (!empty($order->inst)) {
            $output .= "Special Instructions:\n";
            $output .= wordwrap($order->inst, 42) . "\n";
            $output .= str_repeat("-", 42) . "\n";
        }

        if (isset($order->payment_method)) {
            $output .= sprintf("%-30s %12s\n", "Payment:", $order->payment_method);
            $output .= str_repeat("-", 42) . "\n";
        }

        if (isset($order->status)) {
            $output .= sprintf("%-1s %12s\n", "Status:", $order->status ? "*** " . Str::upper($order->status) . " ***" : "*** PAID ***");
        } else {
            $output .= "         Web Order\n";
            $output .= sprintf("%-1s %12s\n", "Status:", "*** PAID ***");
        }

        $output .= "[BOLD]Thank you![/BOLD]\n";
        return $output;
    }

    protected function formatKitchenDetails($order): string
    {
        $output  = "*** KITCHEN ORDER ***\n";
        $output .= str_repeat("-", 42) . "\n";
        $output .= "Order No:   " . $order->id . "\n";
        $output .= "Order Type: " . $order->type . "\n";
        $output .= "Date:       " . ($order->order_date ?? date('d/m/Y H:i')) . "\n";
        $output .= str_repeat("-", 42) . "\n";
        $output .= "Customer:   " . $order->customerDetails . "\n";
        if ($order->type === 'DineIn') {
            $output .= "Table:      " . $order->tableNumber . "\n";
        }
        $output .= str_repeat("-", 42) . "\n";
        $output .= "Items:\n";
        $output .= str_repeat("-", 42) . "\n";

        $sortedItems = $this->sortItemsByReceiptCategoryOrder($order->items ?? []);
        foreach ($sortedItems as $item) {
            $name = (empty($item->size) || strtolower($item->size) === 'regular')
                ? $item->name
                : $item->name . '-' . $item->size;

            if (strlen($name) > 36) {
                $output .= sprintf("%-4s %-36s\n", $item->quantity, substr($name, 0, 36));
                $output .= sprintf("%-4s %-36s\n", "", substr($name, 36));
            } else {
                $output .= sprintf("%-4s %-36s\n", $item->quantity, $name);
            }
        }

        $output .= str_repeat("-", 42) . "\n";

        if (!empty($order->inst)) {
            $output .= "SPECIAL INSTRUCTIONS:\n";
            $output .= wordwrap($order->inst, 42) . "\n";
            $output .= str_repeat("-", 42) . "\n";
        }

        return $output;
    }

    protected function normalizePrinterName($name)
    {
        $name = trim((string) $name);
        return $name !== '' ? $name : null;
    }

    protected function sortItemsByReceiptCategoryOrder($items): array
    {
        if (empty($items)) {
            return [];
        }

        if ($items instanceof \Illuminate\Support\Collection) {
            $items = $items->all();
        } elseif ($items instanceof \Traversable) {
            $items = iterator_to_array($items, false);
        } elseif (!is_array($items)) {
            $items = (array) $items;
        }

        if (empty($items)) {
            return [];
        }

        $categorySortById = Category::orderBy('receipt_sort_order')
            ->orderBy('id')
            ->pluck('receipt_sort_order', 'id')
            ->map(fn ($value) => (int) $value)
            ->toArray();

        $categorySortByName = Category::orderBy('receipt_sort_order')
            ->orderBy('id')
            ->pluck('receipt_sort_order', 'name')
            ->mapWithKeys(function ($value, $name) {
                return [mb_strtolower(trim((string) $name)) => (int) $value];
            })
            ->toArray();

        $productIds = [];
        foreach ($items as $item) {
            if (isset($item->product_id) && (int) $item->product_id > 0) {
                $productIds[] = (int) $item->product_id;
            }
        }
        $productIds = array_values(array_unique($productIds));

        $productCategoryMap = [];
        if (!empty($productIds)) {
            $productCategoryMap = Product::whereIn('id', $productIds)
                ->pluck('category_id', 'id')
                ->map(fn ($value) => (int) $value)
                ->toArray();
        }

        $rows = [];
        foreach ($items as $index => $item) {
            $sortOrder = 9999;

            if (isset($item->category_id) && (int) $item->category_id > 0) {
                $categoryId = (int) $item->category_id;
                $sortOrder = $categorySortById[$categoryId] ?? 9999;
            } elseif (isset($item->category) && trim((string) $item->category) !== '') {
                $nameKey = mb_strtolower(trim((string) $item->category));
                $sortOrder = $categorySortByName[$nameKey] ?? 9999;
            } elseif (isset($item->product_id) && (int) $item->product_id > 0) {
                $productId = (int) $item->product_id;
                $categoryId = $productCategoryMap[$productId] ?? 0;
                if ($categoryId > 0) {
                    $sortOrder = $categorySortById[$categoryId] ?? 9999;
                }
            }

            $rows[] = [
                'sort' => $sortOrder,
                'index' => $index,
                'item' => $item,
            ];
        }

        usort($rows, function ($a, $b) {
            if ($a['sort'] === $b['sort']) {
                return $a['index'] <=> $b['index'];
            }

            return $a['sort'] <=> $b['sort'];
        });

        return array_values(array_map(fn ($row) => $row['item'], $rows));
    }
}
