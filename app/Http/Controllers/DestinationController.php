<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Destination;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DestinationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $keyword = $request->get('keyword', '');
        $destinations = Destination::query()
            ->when($status, function ($query) use ($status) {
                return $query->where('status', strtoupper($status));
            })
            ->where('title', 'LIKE', "%$keyword%")
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('destinations.index', compact('destinations'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('destinations.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validateRequest($request);

        $newDestination = new Destination();
        $this->saveDestinationData($newDestination, $request);

        return redirect()->route('destinations.index')->with('success', 'Destination successfully created');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $destination = Destination::findOrFail($id);
        return view('destinations.edit', compact('destination'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validateRequest($request);

        $destination = Destination::findOrFail($id);
        $this->saveDestinationData($destination, $request);

        return redirect()->route('destinations.index')->with('success', 'Destination successfully updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $destination = Destination::findOrFail($id);
        if ($destination->image) {
            Storage::delete($destination->image);
        }
        $destination->forceDelete();

        return redirect()->route('destinations.index')->with('success', 'Destination successfully deleted.');
    }

    /**
     * Validate the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    private function validateRequest(Request $request)
    {
        $request->validate([
            'title' => 'required|min:2|max:200',
            'image' => 'required|image', // Validates that the file is an image
        ]);
    }

    /**
     * Save destination data to the model.
     *
     * @param \App\Destination $destination
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    private function saveDestinationData(Destination $destination, Request $request)
    {
        $destination->title = $request->get('title');
        $destination->slug = \Str::slug($request->get('title'), '-');
        $destination->content = $request->get('content');
        $destination->create_by = \Auth::user()->id; // Set the user creating the destination
        $destination->status = $request->get('save_action');

        if ($request->hasFile('image')) {
            if ($destination->image) {
                Storage::delete($destination->image); // Delete old image if it exists
            }
            $fileName = time() . "_" . $request->file('image')->getClientOriginalName();
            $filePath = $request->file('image')->storeAs('destinations_image', $fileName, 'public');
            $destination->image = $filePath; // Store the path
        }

        $destination->save();
    }
}
