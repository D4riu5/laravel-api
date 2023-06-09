<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;


// Models
use App\Models\Project;
use App\Models\Type;
use App\Models\Technology;

// Requests
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;

// Helpers
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\newProject;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // search bar request

        $titleFromSearch = request()->input('title');
        if (isset($titleFromSearch)) {
            $projects = Project::where('title', 'LIKE', '%' . $titleFromSearch . '%')->get();
        } else {
            $projects = Project::all();
        }




        // $projects = Project::paginate(5);

        return view('admin.projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $types = Type::all();
        $technologies = Technology::all();

        return view('admin.projects.create', [
            'types' => $types,
            'technologies' => $technologies
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreProjectRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProjectRequest $request)
    {
        $data = $request->validated();

        if (array_key_exists('img', $data)) {
            $imgPath = Storage::put('projects', $data['img']);
            $data['img'] = $imgPath;
        }


        $data['slug'] = Str::slug($data['title']);

        $newProject = Project::create($data);

        if (array_key_exists('technologies', $data)) {
            foreach ($data['technologies'] as $technologyId) {
                $newProject->technologies()->attach($technologyId);
            }
        }

        $loggedUser = Auth::user();

        Mail::to($loggedUser->email)->send(new newProject($newProject));

        return redirect()->route('admin.projects.show', $newProject->id)->with('success', 'Project added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project)
    {
        return view('admin.projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function edit(Project $project)
    {

        $types = Type::all();
        $technologies = Technology::all();

        return view('admin.projects.edit', compact('project', 'types', 'technologies'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateProjectRequest  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $data = $request->validated();

        if (array_key_exists('delete_img', $data)) {
            if ($project->img) {
                Storage::delete($project->img);

                $project->img = null;
                $project->save();
            }
        } else 
        if (array_key_exists('img', $data)) {
            $imgPath = Storage::put('projects', $data['img']);
            $data['img'] = $imgPath;

            if ($project->img) {
                Storage::delete($project->img);
            }
        }


        $data['slug'] = Str::slug($data['title']);

        $project->update($data);

        if (array_key_exists('technologies', $data)) {
            // foreach ($project->technologies as $technology) {
            //     $project->technologies()->detach($technology);
            // }
            // foreach ($data['technologies'] as $technologyId) {
            //     $project->technologies()->attach($technologyId);
            // }
            

            $project->technologies()->sync($data['technologies']);
        } else {
            // $project->technologies()->sync([]);
            
            $project->technologies()->detach();
        }

        return redirect()->route('admin.projects.show', $project->id)->with('success', 'Project updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(Project $project)
    {

        if ($project->img) {
            Storage::delete($project->img);
        }

        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Project '. ucfirst($project->title) .' was deleted successfully!');
    }
}
