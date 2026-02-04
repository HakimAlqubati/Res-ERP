<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

// Mock a user
$user = \App\Models\User::first();
if (!$user) {
    die("No user found to authenticate.\n");
}
auth()->login($user);

echo "Authenticated as User ID: " . $user->id . "\n";

// Define Payload
$payload = [
    'type' => 'based_on_day',
    'date' => date('Y-m-d'),
    'branch_id' => 1, // Assuming branch 1 exists, otherwise need to find one
    'employees' => [
        [
            'employee_id' => 1, // Assuming employee 1 exists
            'start_time' => '17:00',
            'end_time' => '19:00',
            'hours' => 2,
            'notes' => 'Test via script'
        ]
    ]
];

// Check for valid IDs
$branch = \App\Models\Branch::first();
if ($branch) {
    $payload['branch_id'] = $branch->id;
} else {
    echo "Warning: No branch found. Validation might fail.\n";
}

$employee = \App\Models\Employee::first();
if ($employee) {
    $payload['employees'][0]['employee_id'] = $employee->id;
} else {
    echo "Warning: No employee found. Validation might fail.\n";
}

echo "Testing Payload:\n";
print_r($payload);

// Create Request
$request = Request::create(
    '/api/hr/overtime',
    'POST',
    $payload
);

// Dispatch Request
$response = $kernel->handle($request);

echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Content: " . $response->getContent() . "\n";

// Check Database
if ($response->getStatusCode() === 200) {
    // Check if record exists
    $lastOvertime = \App\Models\EmployeeOvertime::latest()->first();
    if ($lastOvertime && $lastOvertime->notes === 'Test via script') {
        echo "SUCCESS: Record found in database.\n";
    } else {
        echo "FAILURE: Record not found or mismatch.\n";
    }
}
