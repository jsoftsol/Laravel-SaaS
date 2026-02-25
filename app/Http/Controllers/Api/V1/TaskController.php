<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_id'  => 'required|exists:projects,id',
            'title'       => 'required|string',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $project = auth()->user()->company->projects()->findOrFail($request->project_id);

        $task = $project->tasks()->create($request->only('title', 'description', 'assigned_to'));

        return response()->json($task, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        $this->authorize('view', $task);
        return response()->json($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $request->validate([
            'status'      => 'required|in:pending,in_progress,completed',
            'title'       => 'sometimes|string',
            'description' => 'sometimes|string',
        ]);

        $task->update($request->only('title', 'description', 'status'));

        return response()->json($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function assign(Request $request, Task $task)
    {
        $this->authorize('assign', Task::class);

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        // Ensure assigned user is in the same company
        $user              = $task->project->company->users()->findOrFail($request->assigned_to);
        $task->assigned_to = $user->id;
        $task->save();

        return response()->json($task);
    }
}
