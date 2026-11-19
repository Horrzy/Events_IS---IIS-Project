<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Venue;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $city = trim((string) $request->query('city', ''));

        $venues = Venue::query()
            ->where('status', 'approved')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('street', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($city !== '', function ($query) use ($city) {
                $query->where('city', $city);
            })
            ->orderBy('city')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $cities = Venue::query()
            ->where('status', 'approved')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('venues.index', [
            'venues' => $venues,
            'cities' => $cities,
            'search' => $search,
            'selectedCity' => $city,
        ]);
    }

    public function show(Request $request, Venue $venue): View
    {
        $user = $request->user();
        $canViewUnapproved = $user !== null
            && ($user->isModerator() || $venue->proposed_by === $user->id);

        if ($venue->status !== 'approved' && !$canViewUnapproved) {
            abort(404);
        }

        $venue->load(['proposer', 'reviewer']);

        return view('venues.show', [
            'venue' => $venue,
        ]);
    }

    public function create(): View
    {
        return view('venues.create');
    }

    public function store(Request $request, ImageUploadService $uploader): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'street' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Název místa je povinný.',
            'street.required' => 'Ulice a číslo popisné jsou povinné.',
            'city.required' => 'Město je povinné.',
            'photo.image' => 'Nahraný soubor musí být obrázek.',
            'photo.mimes' => 'Podporované formáty obrázku jsou JPG, PNG a WebP.',
            'photo.max' => 'Obrázek může mít maximálně 2 MB.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $uploader->store($request->file('photo'), 'venues');
        }

        $venue = new Venue([
            'name' => $validated['name'],
            'street' => $validated['street'],
            'city' => $validated['city'],
            'postal_code' => $validated['postal_code'] ?? null,
            'description' => $validated['description'] ?? null,
            'photo_path' => $photoPath,
        ]);
        $venue->status = 'pending';
        $venue->proposed_by = $request->user()->id;
        $venue->save();

        return redirect()
            ->route('proposals.index')
            ->with('success', 'Návrh místa byl odeslán ke schválení moderátorem.');
    }

    public function myProposals(Request $request): View
    {
        $venues = Venue::query()
            ->where('proposed_by', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        $categories = Category::query()
            ->with('parent')
            ->where('proposed_by', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return view('proposals.index', [
            'venues' => $venues,
            'categories' => $categories,
        ]);
    }
}
