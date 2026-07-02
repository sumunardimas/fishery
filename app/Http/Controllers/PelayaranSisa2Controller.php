<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PelayaranSisa2Controller extends Controller
{
    public function index(Request $request, PelayaranSisaController $legacyController): View
    {
        $legacyView = $legacyController->index($request);
        $viewData = $legacyView->getData();

        [$fishermen, $priceMap, $weightMap] = $this->buildPersonalMatrixData($viewData);

        $viewData['existingPersonalFishermen'] = $fishermen;
        $viewData['existingPersonalPrices'] = $priceMap;
        $viewData['existingPersonalWeights'] = $weightMap;

        return view('pelayaran.sisa2.index', $viewData);
    }

    public function storePersonalTangkapan(Request $request, PelayaranSisaController $legacyController): RedirectResponse
    {
        $fishermenInput = $request->input('fishermen', []);
        $pricesInput = $request->input('personal_prices', []);
        $weightsInput = $request->input('personal_weights', []);

        $anglers = [];

        foreach ((array) $fishermenInput as $fisherIndex => $fisherName) {
            $name = trim((string) $fisherName);
            $weightsByFish = $weightsInput[$fisherIndex] ?? [];
            $items = [];

            foreach ((array) $weightsByFish as $idIkanTangkapan => $beratRaw) {
                if ($beratRaw === null || $beratRaw === '') {
                    continue;
                }

                $berat = (float) $beratRaw;
                if ($berat <= 0) {
                    continue;
                }

                $hargaPerKgRaw = $pricesInput[$idIkanTangkapan] ?? null;
                $hargaPerKg = ($hargaPerKgRaw === null || $hargaPerKgRaw === '') ? 0 : (float) $hargaPerKgRaw;

                $items[] = [
                    'id_ikan_tangkapan' => (int) $idIkanTangkapan,
                    'berat' => $berat,
                    'harga_per_kg' => $hargaPerKg,
                ];
            }

            if ($items !== []) {
                $anglers[] = [
                    'name' => $name,
                    'items' => $items,
                ];
            }
        }

        $request->merge([
            'kategori_tangkapan' => 'pancingan_pribadi',
            'anglers' => $anglers,
        ]);

        $legacyController->storeTangkapan($request);

        return redirect()
            ->route('pelayaran.sisa2.index', [
                'pelayaran_id' => (int) $request->input('id_pelayaran'),
                'tab' => 'tangkapan-pribadi',
            ])
            ->with('success', 'Data tangkapan kategori Pancingan Pribadi berhasil disimpan.');
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, float>, 2: array<int, array<int, float>>}
     */
    private function buildPersonalMatrixData(array $viewData): array
    {
        /** @var Collection<int, mixed> $masterIkanTangkapan */
        $masterIkanTangkapan = collect($viewData['masterIkanTangkapan'] ?? []);
        $existingPersonalAnglers = collect($viewData['existingPersonalAnglers'] ?? []);

        if ($existingPersonalAnglers->isEmpty()) {
            return [[0 => ''], [], []];
        }

        $fishermen = [];
        $fisherIndexByName = [];
        $weights = [];
        $priceAccumulator = [];

        foreach ($existingPersonalAnglers as $angler) {
            $name = trim((string) ($angler['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            if (!isset($fisherIndexByName[$name])) {
                $fisherIndexByName[$name] = count($fishermen);
                $fishermen[] = $name;
            }

            $fisherIndex = $fisherIndexByName[$name];
            $items = is_array($angler['items'] ?? null) ? $angler['items'] : [];

            foreach ($items as $item) {
                $idIkanTangkapan = (int) ($item['id_ikan_tangkapan'] ?? 0);
                $berat = (float) ($item['berat'] ?? 0);
                $harga = (float) ($item['harga_per_kg'] ?? 0);

                if ($idIkanTangkapan <= 0 || $berat <= 0) {
                    continue;
                }

                if (!isset($weights[$fisherIndex])) {
                    $weights[$fisherIndex] = [];
                }

                if (!isset($weights[$fisherIndex][$idIkanTangkapan])) {
                    $weights[$fisherIndex][$idIkanTangkapan] = 0;
                }
                $weights[$fisherIndex][$idIkanTangkapan] += $berat;

                if (!isset($priceAccumulator[$idIkanTangkapan])) {
                    $priceAccumulator[$idIkanTangkapan] = ['total_berat' => 0.0, 'total_nilai' => 0.0];
                }
                $priceAccumulator[$idIkanTangkapan]['total_berat'] += $berat;
                $priceAccumulator[$idIkanTangkapan]['total_nilai'] += ($berat * $harga);
            }
        }

        $priceMap = [];
        foreach ($masterIkanTangkapan as $ikanTangkapan) {
            $idIkanTangkapan = (int) ($ikanTangkapan->id_ikan_tangkapan ?? 0);
            if ($idIkanTangkapan <= 0) {
                continue;
            }

            $acc = $priceAccumulator[$idIkanTangkapan] ?? null;
            if (!$acc || (float) $acc['total_berat'] <= 0) {
                continue;
            }

            $priceMap[$idIkanTangkapan] = (float) $acc['total_nilai'] / (float) $acc['total_berat'];
        }

        if ($fishermen === []) {
            $fishermen = [''];
        }

        return [$fishermen, $priceMap, $weights];
    }
}
