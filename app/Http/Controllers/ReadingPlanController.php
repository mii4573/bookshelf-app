<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 一覧表示
     */
    public function index(Request $request): View|RedirectResponse
    {
        $currentStatus = $request->input('status');
        $query = Auth::user()->readingPlans()->with('book');

        if ($currentStatus !== null && $currentStatus !== '') {
            $statusEnum = ReadingPlanStatus::tryFrom($currentStatus);

            if (! $statusEnum) {
                return redirect()->route('reading-plans.index')
                    ->with('error', '指定された状態は存在しません。');
            }

            $query->where('status', $statusEnum);
        }

        $readingPlans = $query->orderBy('target_date', 'asc')->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 新規作成画面
     */
    public function create(): View
    {
        // ユーザーが所持または登録している書籍の一覧を取得
        $books = Auth::user()->books ?? collect();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 登録処理
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        Auth::user()->readingPlans()->create([
            'book_id' => $request->book_id,
            'target_date' => $request->target_date,
            'status' => ReadingPlanStatus::Pending, // 初期状態：未着手
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }

    /**
     * 編集画面
     */
    public function edit(ReadingPlan $readingPlan): View|RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        // 【要件】完了済み（completed）および期限切れ（expired）計画の編集制限
        if ($readingPlan->status === ReadingPlanStatus::Completed || $readingPlan->status === ReadingPlanStatus::Expired) {
            return redirect()->route('reading-plans.index')
                ->with('error', '完了済みまたは期限切れの計画は編集できません。');
        }

        $books = Auth::user()->books ?? collect();

        return view('reading-plans.edit', compact('readingPlan', 'books'));
    }

    /**
     * 更新処理
     */
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        if ($readingPlan->status === ReadingPlanStatus::Completed || $readingPlan->status === ReadingPlanStatus::Expired) {
            return redirect()->route('reading-plans.index')
                ->with('error', '完了済みまたは期限切れの計画は編集できません。');
        }

        $readingPlan->update([
            'target_date' => $request->target_date,
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * 削除処理
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    /**
     * 【要件】「読了（completed）」専用アクション・ステータス遷移ロジック
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        if ($readingPlan->status === ReadingPlanStatus::Completed) {
            return redirect()->route('reading-plans.index')
                ->with('info', 'すでに読了しています。');
        }

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(), // 読了日時を記録
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', 'おめでとうございます！読了として記録しました。');
    }
}
