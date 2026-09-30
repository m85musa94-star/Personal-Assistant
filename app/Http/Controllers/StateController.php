<?php

namespace App\Http\Controllers;

use App\Models\UserState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** مزامنة مستند المستخدم (مهامه ومواعيده). كل مستخدم يصل إلى مستنده فقط. */
class StateController extends Controller
{
    private const MAX_BYTES = 4_000_000;

    public function show(Request $request): JsonResponse
    {
        $state = UserState::where('user_id', $request->user()->id)->first();

        return response()->json(['data' => $state?->data, 'ts' => $state?->ts ?? 0]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate(['data' => ['required', 'array'], 'ts' => ['required', 'integer', 'min:0']]);
        if (strlen($request->getContent()) > self::MAX_BYTES) {
            return response()->json(['message' => 'حجم البيانات أكبر من المسموح'], 413);
        }

        $userId = $request->user()->id;
        $ts = (int) $request->input('ts');
        $state = UserState::firstOrNew(['user_id' => $userId]);

        // الأحدث يفوز: لا نكتب فوق نسخة أحدث من جهاز آخر، ونعيدها للعميل ليتبنّاها.
        if ($state->exists && $state->ts > $ts) {
            return response()->json(['conflict' => true, 'data' => $state->data, 'ts' => $state->ts], 409);
        }

        $state->data = $request->input('data');
        $state->ts = $ts;
        $state->save();

        return response()->json(['ok' => true, 'ts' => $ts]);
    }
}
