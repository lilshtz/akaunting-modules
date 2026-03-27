<?php

namespace Modules\Crm\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Response;
use Modules\Crm\Http\Requests\PipelineStageReorder;
use Modules\Crm\Http\Requests\PipelineStageStore;
use Modules\Crm\Http\Requests\PipelineStageUpdate;
use Modules\Crm\Models\CrmPipelineStage;

class PipelineStages extends Controller
{
    public function index(): Response
    {
        CrmPipelineStage::ensureDefaults(company_id());

        $stages = CrmPipelineStage::forCompany(company_id())
            ->ordered()
            ->withCount(['deals' => fn ($query) => $query->visible()])
            ->get();

        return view('crm::pipeline-stages.index', compact('stages'));
    }

    public function store(PipelineStageStore $request): Response
    {
        CrmPipelineStage::ensureDefaults(company_id());
        $isWon = $request->boolean('is_won');
        $isLost = $isWon ? false : $request->boolean('is_lost');

        CrmPipelineStage::create([
            'company_id' => company_id(),
            'name' => $request->get('name'),
            'position' => CrmPipelineStage::forCompany(company_id())->max('position') + 1,
            'color' => $request->get('color'),
            'is_won' => $isWon,
            'is_lost' => $isLost,
        ]);

        $this->normalizeTerminalStages();

        flash(trans('messages.success.added', ['type' => trans('crm::general.pipeline_stage')]))->success();

        return redirect()->route('crm.pipeline-stages.index');
    }

    public function update(int $id, PipelineStageUpdate $request): Response
    {
        $stage = $this->findStage($id);
        $isWon = $request->boolean('is_won');
        $isLost = $isWon ? false : $request->boolean('is_lost');

        $stage->update([
            'name' => $request->get('name'),
            'color' => $request->get('color'),
            'is_won' => $isWon,
            'is_lost' => $isLost,
        ]);

        $this->normalizeTerminalStages($stage->id);

        flash(trans('messages.success.updated', ['type' => $stage->name]))->success();

        return redirect()->route('crm.pipeline-stages.index');
    }

    public function destroy(int $id): Response
    {
        $stage = $this->findStage($id);

        if ($stage->deals()->exists()) {
            flash(trans('crm::general.stage_delete_blocked'))->error();

            return redirect()->route('crm.pipeline-stages.index');
        }

        $stage->delete();

        $this->resequenceStages();

        flash(trans('messages.success.deleted', ['type' => $stage->name]))->success();

        return redirect()->route('crm.pipeline-stages.index');
    }

    public function reorder(PipelineStageReorder $request): Response
    {
        $orderedIds = CrmPipelineStage::forCompany(company_id())
            ->whereIn('id', $request->get('stages', []))
            ->ordered()
            ->pluck('id')
            ->all();

        foreach ($request->get('stages', []) as $position => $id) {
            if (! in_array($id, $orderedIds, true)) {
                continue;
            }

            CrmPipelineStage::forCompany(company_id())
                ->where('id', $id)
                ->update(['position' => $position + 1]);
        }

        flash(trans('messages.success.updated', ['type' => trans('crm::general.pipeline')]))->success();

        return redirect()->route('crm.pipeline-stages.index');
    }

    protected function findStage(int $id): CrmPipelineStage
    {
        return CrmPipelineStage::forCompany(company_id())->findOrFail($id);
    }

    protected function normalizeTerminalStages(?int $preferredId = null): void
    {
        $wonStage = CrmPipelineStage::forCompany(company_id())
            ->where('is_won', true)
            ->ordered()
            ->first();

        if ($wonStage && $preferredId && $wonStage->id === $preferredId) {
            CrmPipelineStage::forCompany(company_id())
                ->where('id', '<>', $preferredId)
                ->update(['is_won' => false]);
        } elseif ($wonStage) {
            CrmPipelineStage::forCompany(company_id())
                ->where('id', '<>', $wonStage->id)
                ->update(['is_won' => false]);
        }

        $lostStage = CrmPipelineStage::forCompany(company_id())
            ->where('is_lost', true)
            ->ordered()
            ->first();

        if ($lostStage && $preferredId && $lostStage->id === $preferredId) {
            CrmPipelineStage::forCompany(company_id())
                ->where('id', '<>', $preferredId)
                ->update(['is_lost' => false]);
        } elseif ($lostStage) {
            CrmPipelineStage::forCompany(company_id())
                ->where('id', '<>', $lostStage->id)
                ->update(['is_lost' => false]);
        }
    }

    protected function resequenceStages(): void
    {
        $stages = CrmPipelineStage::forCompany(company_id())->ordered()->get();

        foreach ($stages as $index => $stage) {
            $stage->update(['position' => $index + 1]);
        }
    }
}
