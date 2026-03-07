<?php

namespace App\Http\Controllers;

use App\Models\Institusi;
use App\Http\Requests\StoreInstitusiRequest;
use App\Http\Requests\UpdateInstitusiRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Facades\DataTables;

class InstitusiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Institusi::class);
        return view('institusi.index');
    }

    /**
     * Data for data table.
     * @throws Exception
     */
    public function data() : JsonResponse | DataTableAbstract {
        $response = Gate::inspect('viewAny', Institusi::class);

        if($response->allowed()) {
            $query = Institusi::query();
            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    return view('components.table-actions', [
                        'canEdit' => auth()->user()->hasPermissionTo('update institution'),
                        'editUrl' => route('institusi.edit', $row->id),
                        'canDelete' => auth()->user()->hasPermissionTo('delete institution'),
                        'deleteUrl' => route('institusi.destroy', $row->id),
                        'deleteName' => 'institusi '.$row->nama,
                    ])->render();
                })
                ->make(true);
        }

        return response()->json(['message' => $response->message()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', Institusi::class);
        return view('institusi.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInstitusiRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $create = New Institusi();
                $create->fill($request->validated());
                $create->saveOrFail();
            });
            return redirect()->route('institusi.index')->with('success', 'Institusi berhasil ditambahkan!');
        } catch (Exception $e) {
            logger()->error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()->withErrors([
               'message' => $e->getMessage() ?: __('Unknown Error')
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Institusi $institusi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Institusi $institusi) : View
    {
        Gate::authorize('update', $institusi);
        return view('institusi.edit', compact('institusi'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInstitusiRequest $request, Institusi $institusi): RedirectResponse
    {
        try {
            DB::transaction( function () use ($request, $institusi) {
                $institusi->update($request->validated());
            });
            return redirect()->route('institusi.index')->with('success', 'Data institusi berhasil diperbarui!');
        } catch (Exception $e) {
            logger()->error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()->withErrors([
               'message' => $e->getMessage() ?: __('Unknown Error')
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Institusi $institusi): RedirectResponse
    {
        try {
            $response = Gate::inspect('delete', $institusi);

            if(!$response->allowed()) {
                throw new Exception($response->message(), $response->code());
            }

            DB::transaction(function () use ($institusi) {
                $institusi->delete();
            });

            return redirect()->back()->with('success', 'Institusi berhasil dihapus!');
        } catch (Exception $e) {
            logger()->error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withErrors([
               'message' => $e->getMessage() ?: __('Unknown Error')
            ]);
        }
    }
}
