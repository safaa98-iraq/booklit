<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\GuardsContent;
use App\Models\ContentBlock;
use App\Models\UserHighlight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** "Edit → التظليل": the student's own highlights. */
class HighlightController extends Controller
{
    use GuardsContent;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'block' => ['required', 'uuid'],
            'start' => ['required', 'integer', 'min:0'],
            'end' => ['required', 'integer', 'gt:start'],
        ]);

        $block = ContentBlock::where('uuid', $data['block'])->with('topic.booklet')->firstOrFail();
        $this->guardBooklet($request, $block->topic->booklet);

        $end = min($data['end'], mb_strlen((string) $block->plain_text));
        abort_if($data['start'] >= $end, 422);

        $highlight = $request->user()->highlights()->create([
            'content_block_id' => $block->id,
            'start_offset' => $data['start'],
            'end_offset' => $end,
        ]);

        return response()->json(['id' => $highlight->id], 201);
    }

    public function destroy(Request $request, UserHighlight $highlight): JsonResponse
    {
        abort_unless($highlight->user_id === $request->user()->getKey(), 403);
        $highlight->delete();

        return response()->json(['deleted' => true]);
    }
}
