<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Tasks\TaskProposalService;
use Illuminate\Http\Request;

/**
 * TaskProposalsController
 * 
 * Controller for displaying task improvement proposals
 */
class TaskProposalsController extends Controller
{
    protected TaskProposalService $proposalService;

    public function __construct(TaskProposalService $proposalService)
    {
        $this->proposalService = $proposalService;
    }

    /**
     * Display the proposals index page
     */
    public function index()
    {
        $proposals = $this->proposalService->getProposalsSummary();
        $statistics = $this->proposalService->getStatistics();

        // Sort by priority
        usort($proposals, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return view('task-proposals.index', compact('proposals', 'statistics'));
    }

    /**
     * Display a specific proposal detail
     */
    public function show(string $key)
    {
        $proposal = $this->proposalService->getProposal($key);

        if (!$proposal) {
            abort(404, 'Proposal not found');
        }

        $allProposals = $this->proposalService->getProposalsSummary();

        return view('task-proposals.show', compact('proposal', 'key', 'allProposals'));
    }

    /**
     * Display roadmap page
     */
    public function roadmap()
    {
        $proposals = $this->proposalService->getProposalsSummary();
        $statistics = $this->proposalService->getStatistics();

        // Group by effort
        $groupedByEffort = [
            'منخفض' => array_filter($proposals, fn($p) => $p['effort'] === 'منخفض'),
            'متوسط' => array_filter($proposals, fn($p) => $p['effort'] === 'متوسط'),
            'عالي' => array_filter($proposals, fn($p) => $p['effort'] === 'عالي'),
            'عالي جداً' => array_filter($proposals, fn($p) => $p['effort'] === 'عالي جداً'),
        ];

        return view('task-proposals.roadmap', compact('proposals', 'statistics', 'groupedByEffort'));
    }
}
