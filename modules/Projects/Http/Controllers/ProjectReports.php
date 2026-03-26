<?php

namespace Modules\Projects\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Response;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectReportService;

class ProjectReports extends Controller
{
    public function show(int $projectId, ProjectReportService $reportService): Response|mixed
    {
        $project = Project::where('company_id', company_id())
            ->with(['contact'])
            ->findOrFail($projectId);

        $report = $reportService->build($project);

        return view('projects::reports.pnl', compact('project', 'report'));
    }
}
