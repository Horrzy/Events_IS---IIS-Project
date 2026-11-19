<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationVenueController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'pending');
        if (!in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status = 'pending';
        }

        $venues = Venue::query()
            ->with(['proposer', 'reviewer'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'pending' => Venue::where('status', 'pending')->count(),
            'approved' => Venue::where('status', 'approved')->count(),
            'rejected' => Venue::where('status', 'rejected')->count(),
            'all' => Venue::count(),
        ];

        return view('moderation.venues.index', [
            'venues' => $venues,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function create(): View
    {
        return view('moderation.venues.form', [
            'venue' => null,
        ]);
    }

    public function store(Request $request, ImageUploadService $uploader): RedirectResponse
    {
        $validated = $this->validateVenue($request);

        $photoPath = $request->hasFile('photo')
            ? $uploader->store($request->file('photo'), 'venues')
            : null;

        $venue = new Venue([
            'name' => $validated['name'],
            'street' => $validated['street'],
            'city' => $validated['city'],
            'postal_code' => $validated['postal_code'] ?? null,
            'description' => $validated['description'] ?? null,
            'photo_path' => $photoPath,
        ]);
        $venue->status = 'approved';
        $venue->proposed_by = $request->user()->id;
        $venue->reviewed_by = $request->user()->id;
        $venue->reviewed_at = now();
        $venue->save();

        return redirect()
            ->route('moderation.venues.index', ['status' => 'approved'])
            ->with('success', "Místo „{$venue->name}“ bylo přidáno a rovnou schváleno.");
    }

    public function edit(Venue $venue): View
    {
        return view('moderation.venues.form', [
            'venue' => $venue,
        ]);
    }

    public function update(Request $request, Venue $venue, ImageUploadService $uploader): RedirectResponse
    {
        $validated = $this->validateVenue($request);

        if ($request->boolean('remove_photo') && $venue->photo_path) {
            $uploader->delete($venue->photo_path);
            $venue->photo_path = null;
        }

        if ($request->hasFile('photo')) {
            $uploader->delete($venue->photo_path);
            $venue->photo_path = $uploader->store($request->file('photo'), 'venues');
        }

        $venue->name = $validated['name'];
        $venue->street = $validated['street'];
        $venue->city = $validated['city'];
        $venue->postal_code = $validated['postal_code'] ?? null;
        $venue->description = $validated['description'] ?? null;
        $venue->save();

        return redirect()
            ->route('moderation.venues.index', ['status' => $venue->status])
            ->with('success', "Místo „{$venue->name}“ bylo upraveno.");
    }

    public function approve(Request $request, Venue $venue): RedirectResponse
    {
        $venue->status = 'approved';
        $venue->reviewed_by = $request->user()->id;
        $venue->reviewed_at = now();
        $venue->rejection_reason = null;
        $venue->save();

        return back()->with('success', "Místo „{$venue->name}“ bylo schváleno.");
    }

    public function reject(Request $request, Venue $venue): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Uveďte důvod zamítnutí místa.',
            'rejection_reason.min' => 'Důvod zamítnutí musí mít alespoň :min znaky.',
        ]);

        $venue->status = 'rejected';
        $venue->reviewed_by = $request->user()->id;
        $venue->reviewed_at = now();
        $venue->rejection_reason = $validated['rejection_reason'];
        $venue->save();

        return back()->with('success', "Místo „{$venue->name}“ bylo zamítnuto.");
    }

    public function destroy(Venue $venue, ImageUploadService $uploader): RedirectResponse
    {
        if ($venue->events()->exists()) {
            return back()->with('error', 'Místo nelze smazat, protože je přiřazené k jedné nebo více událostem.');
        }

        $name = $venue->name;
        $uploader->delete($venue->photo_path);
        $venue->delete();

        return back()->with('success', "Místo „{$name}“ bylo smazáno.");
    }

    private function validateVenue(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'street' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Název místa je povinný.',
            'street.required' => 'Ulice a číslo jsou povinné.',
            'city.required' => 'Město je povinné.',
            'photo.image' => 'Nahraný soubor musí být obrázek.',
            'photo.mimes' => 'Podporované formáty obrázku jsou JPG, PNG a WebP.',
            'photo.max' => 'Obrázek může mít maximálně 2 MB.',
        ]);
    }
}
