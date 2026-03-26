<?php

namespace App\Http\Controllers;

use App\Models\Concours;
use App\Models\ConcoursAccessRequest;
use Illuminate\Http\Request;

class ConcoursAccessRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'concours_id' => 'required|exists:concours,id',
        ]);

        $user = auth()->user();

        // Don't allow admins or users who already have access
        if ($user->isAdmin()) {
            return back()->with('error', 'Les administrateurs ont deja acces a tous les concours.');
        }

        if ($user->concours()->where('concours.id', $validated['concours_id'])->exists()) {
            return back()->with('error', 'Vous avez deja acces a ce concours.');
        }

        // Check for existing pending request
        $existing = ConcoursAccessRequest::where('user_id', $user->id)
            ->where('concours_id', $validated['concours_id'])
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return back()->with('error', 'Une demande est deja en attente pour ce concours.');
        }

        ConcoursAccessRequest::create([
            'user_id' => $user->id,
            'concours_id' => $validated['concours_id'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Demande d\'acces envoyee.');
    }

    public function index()
    {
        $requests = ConcoursAccessRequest::with(['user', 'concours'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $handledRequests = ConcoursAccessRequest::with(['user', 'concours', 'handler'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        return view('admin.access-requests.index', compact('requests', 'handledRequests'));
    }

    public function approve(ConcoursAccessRequest $concoursAccessRequest)
    {
        $concoursAccessRequest->update([
            'status' => 'approved',
            'handled_by' => auth()->id(),
        ]);

        // Add the user to the concours
        $concoursAccessRequest->user->concours()->syncWithoutDetaching([$concoursAccessRequest->concours_id]);

        return back()->with('success', 'Acces accorde a ' . $concoursAccessRequest->user->name . '.');
    }

    public function reject(ConcoursAccessRequest $concoursAccessRequest)
    {
        $concoursAccessRequest->update([
            'status' => 'rejected',
            'handled_by' => auth()->id(),
        ]);

        return back()->with('success', 'Demande refusee.');
    }
}
