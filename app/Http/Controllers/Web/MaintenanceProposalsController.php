<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Maintenance\MaintenanceProposalService;
use Illuminate\Http\Request;

/**
 * MaintenanceProposalsController
 * 
 * Controller for displaying maintenance improvement proposals
 */
class MaintenanceProposalsController extends Controller
{
    protected MaintenanceProposalService $proposalService;

    public function __construct(MaintenanceProposalService $proposalService)
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

        return view('maintenance-proposals.index', compact('proposals', 'statistics'));
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

        return view('maintenance-proposals.show', compact('proposal', 'key', 'allProposals'));
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
            'متنوع' => array_filter($proposals, fn($p) => $p['effort'] === 'متنوع'),
        ];

        return view('maintenance-proposals.roadmap', compact('proposals', 'statistics', 'groupedByEffort'));
    }

    /**
     * Export proposals as PDF (placeholder)
     */
    public function export(Request $request)
    {
        // Future implementation for PDF export
        return redirect()->route('maintenance.proposals.index')
            ->with('info', 'ميزة التصدير قيد التطوير');
    }
}
