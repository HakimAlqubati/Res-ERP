<?php

namespace App\Console\Commands\Testing;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use App\Repositories\Orders\OrderRepository;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestOrderStatusRaceCondition extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:order-status-race {order_id} {--count=2} {--child} {--status=ready_for_delivery}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tests race condition in OrderRepository update method';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('child')) {
            $this->runChild();
            return 0;
        }

        $this->runParent();
        return 0;
    }

    protected function runChild()
    {
        try {
            // Login as a user who has permission (Using 412 as per user context or 1 as fallback)
            // Assuming 412 is a valid user based on previous context
            $userId = 412;
            if (!Auth::loginUsingId($userId)) {
                $this->error("RESULT: ERROR: Could not login as user $userId");
                return;
            }

            /** @var OrderRepository $repository */
            $repository = app(OrderRepository::class);

            $orderId = $this->argument('order_id');
            $status = $this->option('status');

            // Construct Request
            $request = new Request();
            $request->merge([
                'status' => $status,
                // Add other fields if necessary for validation
            ]);

            // Set the request in the container so validation works if it relies on it
            app()->instance('request', $request);

            // Call the repository update method
            $result = $repository->update($request, $orderId);

            // Convert result to array to check status
            if (is_object($result) && method_exists($result, 'toArray')) {
                $resArray = $result->toArray();
            } else {
                $resArray = (array)$result;
            }

            if ($resArray instanceof \Illuminate\Http\JsonResponse) {
                $resArray = $resArray->getData(true);
            }

            if (isset($resArray['success']) && $resArray['success'] === true) {
                $this->info("RESULT: SUCCESS");
            } else {
                $msg = $resArray['message'] ?? 'Unknown Error';
                $this->error("RESULT: FAILED ($msg)");
                $this->error("FULL RESPONSE: " . json_encode($resArray));
            }
        } catch (\Throwable $e) {
            $this->error("RESULT: ERROR " . $e->getMessage() . " | Trace: " . $e->getFile() . ":" . $e->getLine());
        }
    }

    protected function runParent()
    {
        $count = (int)$this->option('count');
        $id = $this->argument('order_id');
        $status = $this->option('status');

        $this->info("Starting $count concurrent processes for Order $id setting status to '$status'...");

        $processes = [];
        for ($i = 0; $i < $count; $i++) {
            $cmd = [
                'php',
                'artisan',
                'test:order-status-race',
                $id,
                '--child',
                "--status=$status"
            ];

            $process = new Process($cmd);
            $process->setWorkingDirectory(base_path());
            $process->start();
            $processes[] = $process;
        }

        $this->info("Waiting for processes to finish...");

        $successCount = 0;
        $outputs = [];

        foreach ($processes as $index => $process) {
            $process->wait();

            $output = $process->getOutput() . $process->getErrorOutput();
            $outputs[] = "Process $index: " . str_replace(["\r", "\n"], " ", trim($output));

            if (str_contains($output, 'RESULT: SUCCESS')) {
                $successCount++;
            }
        }

        $this->newLine();
        foreach ($outputs as $out) {
            $this->line($out);
        }
        $this->newLine();

        if ($successCount > 1) {
            // In this specific case, if both succeed, it MIGHT be okay logic-wise unless there's a strict rule
            // preventing re-updating the same status or if it causes side effects (like duplicating transfer_date).
            // Based on the code:
            // if (in_array($request->status, [Order::READY_FOR_DELEVIRY])) { $order->update(['transfer_date' => now()]); }
            // Both requests will update transfer_date. This is a subtle race condition (double update), but implies no lock.
            $this->warn("Race condition check: $successCount requests succeeded. Check if side effects (like transfer_date) were duplicated or overwritten unexpectedly.");
        } else {
            $this->info("Only $successCount request(s) succeeded.");
        }
    }
}
