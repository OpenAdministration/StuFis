<?php

namespace App\Http\Controllers;

use App\Models\Legacy\Project;
use App\Models\Legacy\ProjectAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        return view('project.show', compact('project'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        //
    }

    public function showAttachment(ProjectAttachment $attachment, string $filename)
    {
        Gate::authorize('view', $attachment->project);

        // Content-Type is derived from the (validated) extension via the model's
        // canonical map — NEVER guessed from content. See ProjectAttachment::MIME_TYPES.
        $mimeType = ProjectAttachment::mimeForName($attachment->name);

        // Unexpected extension: never render inline. Force a download with a
        // neutral type so a disguised file cannot execute in our origin.
        if ($mimeType === null) {
            return $this->downloadAttachment($attachment, $filename);
        }

        // Declare the type from the (validated) extension, not from content, and
        // forbid MIME-sniffing. Together these keep inline serving safe.
        return response()->file(Storage::path($attachment->path), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadAttachment(ProjectAttachment $attachment, string $filename)
    {
        Gate::authorize('view', $attachment->project);

        return response()->download(Storage::path($attachment->path), $attachment->name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
