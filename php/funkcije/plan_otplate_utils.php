<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('planOtplateNormalizeString')) {
    function planOtplateNormalizeString(string $value): string
    {
        $lower = mb_strtolower($value, 'UTF-8');
        $transliterated = strtr($lower, [
            'š' => 's',
            'ć' => 'c',
            'č' => 'c',
            'ž' => 'z',
            'đ' => 'dj',
        ]);
        return preg_replace('/\s+/', '', $transliterated);
    }
}

if (!function_exists('planOtplateIsKesKupac')) {
    function planOtplateIsKesKupac(?string $tipNaziv): bool
    {
        if (!$tipNaziv) {
            return false;
        }
        $normalized = planOtplateNormalizeString($tipNaziv);
        return strpos($normalized, 'kes') !== false;
    }
}

if (!function_exists('planOtplateNormalizujDatum')) {
    function planOtplateNormalizujDatum($datum): ?string
    {
        if ($datum === null) {
            return null;
        }
        $trimmed = trim((string) $datum);
        if ($trimmed === '' || $trimmed === '0000-00-00') {
            return null;
        }
        return $trimmed;
    }
}

if (!function_exists('planOtplateDohvatiKupacIdIzJedinice')) {
    function planOtplateDohvatiKupacIdIzJedinice(int $jedinica_id, string $tip_jedinice): ?int
    {
        $crud = new CRUD_ajax($_SESSION['godina']);

        switch ($tip_jedinice) {
            case 'stan':
                $crud->table = 'stanovi';
                $result = $crud->select(['kupac_id'], ['id' => $jedinica_id]);
                break;
            case 'lokal':
                $crud->table = 'lokali';
                $result = $crud->select(['kupac_id'], ['id' => $jedinica_id]);
                break;
            case 'garaza':
                $crud->table = 'garaze';
                $result = $crud->select(['kupac_id'], ['id' => $jedinica_id]);
                break;
            case 'parking':
                $crud->table = 'parking_mesta';
                $result = $crud->select(['kupac_id'], ['id' => $jedinica_id]);
                break;
            default:
                $result = [];
                break;
        }

        return !empty($result) ? (int) $result[0]['kupac_id'] : null;
    }
}

if (!function_exists('planOtplateDohvatiTipKupcaNaziv')) {
    function planOtplateDohvatiTipKupcaNaziv(int $tip_kupca_id): ?string
    {
        $crud = new CRUD_ajax($_SESSION['godina']);
        $crud->table = 'tip_kupca';
        $result = $crud->select(['naziv'], ['id_tipa_kupca' => $tip_kupca_id]);
        return !empty($result) ? $result[0]['naziv'] : null;
    }
}

if (!function_exists('planOtplateDohvatiInformacijeOJedinici')) {
    function planOtplateDohvatiInformacijeOJedinici(int $jedinica_id, string $tip_jedinice): array
    {
        $crud = new CRUD_ajax($_SESSION['godina']);

        switch ($tip_jedinice) {
            case 'stan':
                $crud->table = 'stanovi';
                $result = $crud->select(['objekat_id', 'datum_prodaje', 'datum_predugovora'], ['id' => $jedinica_id]);
                break;
            case 'lokal':
                $crud->table = 'lokali';
                $result = $crud->select(['objekat_id', 'datum_prodaje'], ['id' => $jedinica_id]);
                break;
            case 'garaza':
                $crud->table = 'garaze';
                $result = $crud->select(['objekat_id', 'datum_prodaje'], ['id' => $jedinica_id]);
                break;
            case 'parking':
                $crud->table = 'parking_mesta';
                $result = $crud->select(['objekat_id', 'datum_prodaje'], ['id' => $jedinica_id]);
                break;
            default:
                $result = [];
                break;
        }

        return !empty($result) ? $result[0] : [];
    }
}

if (!function_exists('planOtplateDohvatiFazeObjekta')) {
    function planOtplateDohvatiFazeObjekta(int $objekat_id): array
    {
        $crud = new CRUD_ajax($_SESSION['godina']);
        $crud->table = 'objekti';
        $result = $crud->select(['faza_1', 'faza_2', 'faza_3', 'faza_4'], ['id' => $objekat_id]);

        if (empty($result)) {
            return [];
        }

        $faze = [];
        foreach (['faza_1', 'faza_2', 'faza_3', 'faza_4'] as $key) {
            $faze[$key] = planOtplateNormalizujDatum($result[0][$key] ?? null);
        }
        return $faze;
    }
}

if (!function_exists('planOtplateIzracunajCenuJedinice')) {
    function planOtplateIzracunajCenuJedinice(int $jedinica_id, string $tip_jedinice): float
    {
        $crud = new CRUD_ajax($_SESSION['godina']);
        $ukupna_cena = 0.0;

        switch ($tip_jedinice) {
            case 'stan':
                $crud->table = 'stanovi';
                $stan = $crud->select(['ukupna_cena'], ['id' => $jedinica_id]);
                if (!empty($stan)) {
                    $ukupna_cena = (float) $stan[0]['ukupna_cena'];
                }
                break;
            case 'lokal':
                $crud->table = 'lokali';
                $lokal = $crud->select(['ukupna_cena'], ['id' => $jedinica_id]);
                if (!empty($lokal)) {
                    $ukupna_cena = (float) $lokal[0]['ukupna_cena'];
                }
                break;
            case 'garaza':
                $crud->table = 'garaze';
                $garaza = $crud->select(['cena_sa_pdv'], ['id' => $jedinica_id]);
                if (!empty($garaza)) {
                    $ukupna_cena = (float) $garaza[0]['cena_sa_pdv'];
                }
                break;
            case 'parking':
                $crud->table = 'parking_mesta';
                $parking = $crud->select(['cena'], ['id' => $jedinica_id]);
                if (!empty($parking)) {
                    $ukupna_cena = (float) $parking[0]['cena'];
                }
                break;
        }

        return $ukupna_cena;
    }
}

if (!function_exists('planOtplateDefinisiRate')) {
    function planOtplateDefinisiRate(int $tip_kupca_id, float $ukupna_cena): array
    {
        switch ($tip_kupca_id) {
            case 1:
                return [
                    ['procenat' => 20.0, 'suma' => $ukupna_cena * 0.20],
                    ['procenat' => 80.0, 'suma' => $ukupna_cena * 0.80],
                ];
            case 2:
                return [
                    ['procenat' => 20.0, 'suma' => $ukupna_cena * 0.20],
                    ['procenat' => 30.0, 'suma' => $ukupna_cena * 0.30],
                    ['procenat' => 30.0, 'suma' => $ukupna_cena * 0.30],
                    ['procenat' => 20.0, 'suma' => $ukupna_cena * 0.20],
                ];
            default:
                return [];
        }
    }
}

if (!function_exists('planOtplateGenerisiPlan')) {
    function planOtplateGenerisiPlan(int $kupac_id, int $jedinica_id, string $tip_jedinice, ?string $datum_prodaje = null): array
    {
        try {
            $kupacCrud = new CRUD_ajax($_SESSION['godina']);
            $kupacCrud->table = 'kupci';
            $kupac = $kupacCrud->select(['tip_kupca_id'], ['id' => $kupac_id]);

            if (empty($kupac)) {
                return ['success' => false, 'message' => 'Kupac nije pronađen'];
            }

            $tip_kupca_id = (int) $kupac[0]['tip_kupca_id'];
            $tipNaziv = planOtplateDohvatiTipKupcaNaziv($tip_kupca_id);
            $kupacJeKes = planOtplateIsKesKupac($tipNaziv);

            $ukupna_cena = planOtplateIzracunajCenuJedinice($jedinica_id, $tip_jedinice);
            if ($ukupna_cena <= 0) {
                return ['success' => false, 'message' => 'Nije moguće izračunati cenu jedinice'];
            }

            $rate = planOtplateDefinisiRate($tip_kupca_id, $ukupna_cena);
            if (empty($rate)) {
                return ['success' => false, 'message' => 'Nepoznat tip kupca'];
            }

            $informacije = planOtplateDohvatiInformacijeOJedinici($jedinica_id, $tip_jedinice);
            $datum_prodaje_jedinice = planOtplateNormalizujDatum($informacije['datum_prodaje'] ?? null);
            $datum_predugovora = planOtplateNormalizujDatum($informacije['datum_predugovora'] ?? null);

            $faze = [];
            if (!empty($informacije['objekat_id'])) {
                $faze = planOtplateDohvatiFazeObjekta((int) $informacije['objekat_id']);
            }

            $danas = date('Y-m-d');
            $datum_prodaje_final = planOtplateNormalizujDatum($datum_prodaje) ?? $datum_prodaje_jedinice ?? $danas;

            $plan = [];
            foreach ($rate as $index => $rata) {
                $datum_rate = $datum_prodaje_final;

                if ($index === 0) {
                    if ($tip_jedinice === 'stan' && $datum_predugovora) {
                        $datum_rate = $datum_predugovora;
                    } elseif ($datum_prodaje_jedinice) {
                        $datum_rate = $datum_prodaje_jedinice;
                    }
                } elseif ($kupacJeKes) {
                    if ($index === 1) {
                        $datum_rate = $faze['faza_2'] ?? $danas;
                    } elseif ($index === 2) {
                        $datum_rate = $faze['faza_3'] ?? $danas;
                    } elseif ($index === 3) {
                        $datum_rate = $faze['faza_4'] ?? $danas;
                    }
                } elseif ($datum_prodaje_jedinice) {
                    $datum_rate = $datum_prodaje_jedinice;
                }

                if (!$datum_rate) {
                    $datum_rate = $danas;
                }

                $plan[] = [
                    'datum_rate' => $datum_rate,
                    'procenat' => round($rata['procenat'], 2),
                    'suma' => round($rata['suma'], 2),
                    'status' => 'neplaceno',
                ];
            }

            usort($plan, function ($a, $b) {
                return strcmp($a['datum_rate'], $b['datum_rate']);
            });

            return [
                'success' => true,
                'plan' => $plan,
                'datum_prodaje' => $datum_prodaje_final,
                'tip_kupca_id' => $tip_kupca_id,
                'kupac_je_kes' => $kupacJeKes,
                'ukupna_cena' => $ukupna_cena,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Greška: ' . $e->getMessage()];
        }
    }
}


