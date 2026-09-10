<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class JournalEntryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:accounting.journal.view'];
    }

    public function index(Request $request): View
    {
        $entries = JournalEntry::query()->with(['branch', 'creator'])->withSum('lines', 'debit')->withSum('lines', 'credit')->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))->latest('posting_date')->paginate(30);

        return view('finance.journals', compact('entries'));
    }
}
