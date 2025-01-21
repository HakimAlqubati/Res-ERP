<?php

namespace App\Services;

use App\Models\Branch;

class BranchTreeService
{
    /**
     * Generate the hierarchical tree structure for branches.
     *
     * @return array
     */
    public static function getBranchTree()
    {
        // Retrieve the HQ branch
        $hqBranch = Branch::where('is_hq', true)
            ->with('user') // Include the manager (user) relation
            ->first();

        // Retrieve all other branches
        $branches = Branch::where('is_hq', false)->where('active', 1)
            ->with('user') // Include the manager (user) relation
            ->get();

        // Format the data into a hierarchical structure
        $tree = [];

        if ($hqBranch) {
            $tree[] = [
                'name' => $hqBranch->user->name ?? 'HQ (No Manager)',
                'children' => $branches->map(function ($branch) {
                    return [
                        'name' => $branch->user->name ?? $branch->name,
                    ];
                })->toArray(),
            ];
        }

        return $tree;
    }

    public static function getBranchTreeV2()
    {
        // Retrieve the HQ branch
        $hqBranch = Branch::where('is_hq', true)
            ->with(['user.employee']) // Include the user and their employee relation
            ->first();

        // Retrieve all other branches
        $branches = Branch::where('is_hq', false)->where('active', 1)
            ->with(['user.employee']) // Include the user and their employee relation
            ->get();


        // Check if any employee exists in HQ or branches
        if (!$hqBranch?->user?->employee && !$branches->pluck('user.employee')->filter()->count()) {
            // Return a message if no employees are assigned
            return 'Text in one line: Please assign employees to the users first.';
        }

        // Format the data into a hierarchical structure
        $tree = [];
        if ($hqBranch) {
            $tree[] = [
                'name' => self::trimToTwoWords($hqBranch->user?->employee?->name ?? 'HQ (No Employee)'),
                'children' => $branches->map(function ($branch) {
                    $employee = $branch->user?->employee;

                    // Check if the employee has subordinates
                    $subordinates = $employee ? $employee->subordinates : null;

                    return [
                        'name' => self::trimToTwoWords($employee?->name ?? $branch->name . ' NO'),
                        'children' => $subordinates && $subordinates->isNotEmpty()
                            ? $subordinates->map(function ($subordinate) {
                                return [
                                    'name' => self::trimToTwoWords($subordinate->name),
                                ];
                            })->toArray()
                            : [],
                    ];
                })->toArray(),
            ];
        }

        return $tree;
    }

    protected static function trimToTwoWords($string)
    {
        // Split the string into an array of words
        $words = explode(' ', $string);

        // Take only the first two words and join them back into a string
        return implode(' ', array_slice($words, 0, 2));
    }
}
