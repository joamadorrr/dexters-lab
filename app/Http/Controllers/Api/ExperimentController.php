<?php

namespace App\Http\Controllers\Api;

use App\Models\Experiment;
use App\Models\Tag;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignSmartView;
use App\Http\Requests\StoreExperiment;
use App\Http\Requests\UpdateResults;
use App\Http\Resources\ExperimentResource;
use App\Services\ExperimentResultsService;
use Illuminate\Http\Request;

class ExperimentController extends Controller
{
    protected $experimentResultsService;

    public function __construct(ExperimentResultsService $resultsService)
    {
        $this->experimentResultsService = $resultsService;
    }

    public function index()
    {
        $experiments = Experiment::with(['tags', 'results'])->orderBy('created_at', 'desc')->get();

        return ExperimentResource::collection($experiments);
    }

    public function store(StoreExperiment $request)
    {
        $tagIds = [];

        collect($request->tags)->each(function ($tag) use (&$tagIds) {
            if ($tag['id'] != null) {
                if (!in_array($tag['id'], $tagIds)) {
                    array_push($tagIds, $tag['id']);
                }
            } else {
                $existingTag = Tag::where('name', $tag['name'])->first();

                if ($existingTag == null) {
                    $newTag = Tag::create([
                        'name' => $tag['name']
                    ]);

                    array_push($tagIds, $newTag->id);
                } else {
                    if (!in_array($existingTag['id'], $tagIds)) {
                        array_push($tagIds, $existingTag['id']);
                    }
                }
            }
        });

        $experiment = Experiment::create($request->only([
            'title',
            'background',
            'falsifiable_hypothesis'
        ]));

        $experiment->tags()->sync($tagIds);

        return new ExperimentResource($experiment->load('tags', 'results'));
    }

    public function assignSmartView(AssignSmartView $request)
    {
        $experiment = Experiment::findOrFail($request->id);
        $experiment->smart_view_id = $request->smart_view_id;
        $experiment->smart_view_query = $request->smart_view_query;

        $newResults = $this->experimentResultsService->buildResults($experiment->smart_view_query);

        if (isset($experiment->results)) {
            $results = $experiment->results;
            $results->data = json_encode($newResults);

            $results->save();
        } else {
            $experiment->results()->create([
                'data' => json_encode($newResults)
            ]);
        }

        $experiment->save();

        return new ExperimentResource($experiment->load('tags', 'results'));
    }

    public function updateResults(UpdateResults $request)
    {
        $experiment = Experiment::findOrFail($request->id);

        if (isset($experiment->smart_view_query)) {
            $newResults = $this->experimentResultsService->buildResults($experiment->smart_view_query);
            $results = $experiment->results;
            $results->data = json_encode($newResults);

            $results->save();
        }

        return new ExperimentResource($experiment->load('tags', 'results'));
    }
}
