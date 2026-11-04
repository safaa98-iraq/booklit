<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\GuardsContent;
use App\Models\Booklet;
use App\Models\Favorite;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    use GuardsContent;

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'topics' ? 'topics' : 'booklets';
        $favorites = $request->user()->favorites()
            ->where('favoritable_type', $tab === 'topics' ? 'topic' : 'booklet')
            ->with('favoritable')
            ->latest()->get()
            ->filter(fn (Favorite $f) => $f->favoritable !== null);

        $favorites->each(fn (Favorite $f) => $f->favoritable->load($tab === 'topics' ? 'booklet.subject.stage' : 'subject.stage'));

        return view('student.favorites', compact('tab', 'favorites'));
    }

    /** Add or remove a booklet/topic. Returns JSON for fetch(), or redirects back for forms. */
    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Favorite::TYPES))],
            'id' => ['required', 'integer'],
        ]);

        $model = $data['type'] === 'topic' ? Topic::findOrFail($data['id']) : Booklet::findOrFail($data['id']);
        $this->guardBooklet($request, $model instanceof Topic ? $model->booklet : $model);

        $attributes = ['favoritable_type' => $data['type'], 'favoritable_id' => $model->getKey()];
        $existing = $request->user()->favorites()->where($attributes)->first();
        $existing ? $existing->delete() : $request->user()->favorites()->create($attributes);

        return $request->expectsJson()
            ? response()->json(['favorite' => ! $existing])
            : back();
    }
}
