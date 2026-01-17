<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EpcMatcher
{
    public function findForProperty(string $postcode, string $paon, ?string $saon, string $street, ?Carbon $refDate = null, int $limit = 5, ?string $locality = null): array
    {
        $postcode = $this->normalisePostcode($postcode);
        $paon     = strtoupper(trim($paon));
        $saon     = $saon !== null ? strtoupper(trim($saon)) : null;
        $street   = strtoupper(trim($street));
        $locality = $locality !== null ? strtoupper(trim($locality)) : null;

        $candidates = DB::table('epc_certificates')
            ->select('lmk_key','address','postcode','lodgement_date','current_energy_rating','potential_energy_rating','property_type','total_floor_area','local_authority_label')
            ->where('postcode', $postcode)
            ->orderByDesc('lodgement_date')
            ->limit(500)
            ->get();

        $scored = [];
        foreach ($candidates as $row) {
            $scored[] = [
                'row'   => $row,
                'score' => $this->scoreCandidate($paon, $saon, $street, $locality, (string)($row->address ?? ''), $refDate, $row->lodgement_date),
            ];
        }

        usort($scored, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return strcmp($b['row']->lodgement_date ?? '', $a['row']->lodgement_date ?? '');
            }
            return $b['score'] <=> $a['score'];
        });

        // keep top matches above 50
        $scored = array_values(array_filter($scored, fn($s) => $s['score'] >= 50));
        return array_slice($scored, 0, $limit);
    }

    protected function normalisePostcode(string $pc): string
    {
        $pc = strtoupper(preg_replace('/\\s+/', '', $pc));
        return strlen($pc) >= 5 ? substr($pc, 0, -3).' '.substr($pc, -3) : $pc;
    }

    /**
     * Score a single EPC candidate against LR address parts.
     * Heuristics: PAON token match, SAON presence, street similarity, and locality token.
     */
    protected function scoreCandidate(string $paon, ?string $saon, string $street, ?string $locality, string $epcAddress, ?Carbon $refDate, ?string $lodgementDate): float
    {
        $score = 0.0;

        $normEpc    = $this->normAddress($epcAddress);
        $normPAON   = $this->normToken($paon);
        $normSAON   = $saon ? $this->normToken($saon) : null;
        $normStreet = $this->normStreet($street);
        $normLocality = $locality ? $this->normToken($locality) : null;
        $epcUnitId = $this->extractUnitId($normEpc);
        $saonUnitId = $normSAON ? $this->extractUnitId($normSAON) : null;

        // Flags for combo bonuses
        $paonHit = false;
        $saonHit = false;
        $streetHit = false;
        $localityHit = false;

        // 1) PAON (house number/name)
        if ($normPAON !== '') {
            if (preg_match('/(^|\s)'.preg_quote($normPAON,'/').'($|\s)/', $normEpc)) {
                $score += 50; // exact token present
                $paonHit = true;
            } elseif ($this->levRatio($normPAON, $normEpc) >= 0.85) {
                $score += 30; // near match
            }
        }

        // 2) SAON (flat/unit)
        if ($normSAON) {
            if ($saonUnitId && $epcUnitId) {
                if ($saonUnitId === $epcUnitId) {
                    $score += 20; // exact flat/unit match
                    $saonHit = true;
                } else {
                    $score -= 25; // penalise different flat/unit number
                }
            } elseif (preg_match('/(^|\s)'.preg_quote($normSAON,'/').'($|\s)/', $normEpc)) {
                $score += 20; // exact flat text present
                $saonHit = true;
            }
        }

        // 3) Locality match (e.g., village/hamlet) — helpful where there is no street
        if ($normLocality) {
            if (preg_match('/(^|\s)'.preg_quote($normLocality,'/').'($|\s)/', $normEpc)) {
                $score += 18; // exact token present
                $localityHit = true;
            } else {
                $simLoc = $this->similarity($normLocality, $normEpc);
                if ($simLoc >= 0.85)      $score += 12;
                elseif ($simLoc >= 0.75) $score += 6;
            }
        }

        // 4) Street match — compare LR street against a street-only version of EPC address
        $normEpcStreet = preg_replace('/\b(FLAT|APARTMENT|APT|UNIT|STUDIO|ROOM|MAISONETTE)\b/', '', $normEpc);
        $normEpcStreet = preg_replace('/\b\d+[A-Z]?\b/', '', $normEpcStreet); // drop numbers like 194 or 16A
        $normEpcStreet = preg_replace('/\s+/', ' ', trim($normEpcStreet));

        if ($normStreet !== '' && preg_match('/(^|\s)'.preg_quote($normStreet,'/').'($|\s)/', $normEpcStreet)) {
            $score += 25; // exact street string present
            $streetHit = true;
        } else {
            $sim = $this->similarity($normStreet, $normEpcStreet);
            if ($sim >= 0.90)      $score += 20;
            elseif ($sim >= 0.80) $score += 15;
            elseif ($sim >= 0.70) $score += 8;
        }

        // 5) Combo bonus: if PAON matches AND (SAON or street) matches, it's almost certainly correct
        if ($paonHit && ($saonHit || $streetHit)) {
            $score += 10;
        }

        // Additional combo: PAON + Locality is very strong where street is absent
        if ($paonHit && $localityHit && !$streetHit) {
            $score += 8;
        }

        // Date proximity: optional, very light bonus only (never penalise)
        if ($refDate && !empty($lodgementDate)) {
            try {
                $lodged = Carbon::parse($lodgementDate);
                $months = abs($lodged->diffInMonths($refDate));
                if ($months <= 36)      { $score += 6; }
                elseif ($months <= 60)  { $score += 3; }
                // >60 months: no bonus
            } catch (\Throwable $e) {
                // ignore parse errors
            }
        }

        if ($score < 0) $score = 0;
        return min(100, $score);
    }

    protected function normAddress(string $s): string
    {
        $s = strtoupper($s);
        $s = str_replace(
            [" ROAD"," STREET"," AVENUE"," LANE"," DRIVE"," COURT"," PLACE"," SQUARE"," CRESCENT"],
            [" RD"," ST"," AVE"," LN"," DR"," CT"," PL"," SQ"," CRES"],
            $s
        );
        $s = preg_replace('/[^A-Z0-9 ]+/', ' ', $s);
        $s = preg_replace('/\\s+/', ' ', $s);
        return trim($s);
    }

    protected function normToken(string $s): string
    {
        $s = strtoupper(trim($s));
        $s = preg_replace('/[^A-Z0-9]+/', ' ', $s);
        return trim($s);
    }

    protected function normStreet(string $s): string
    {
        $s = $this->normAddress($s);
        $s = preg_replace('/\\b(FLAT|APARTMENT|APT|UNIT|STUDIO|ROOM|MAISONETTE)\\b/', '', $s);
        $s = preg_replace('/\\s+/', ' ', $s);
        return trim($s);
    }

    protected function levRatio(string $a, string $b): float
    {
        $a = trim($a); $b = trim($b);
        if ($a === '' || $b === '') return 0.0;
        $len = max(strlen($a), strlen($b));
        if ($len === 0) return 1.0;
        $dist = levenshtein($a, $b);
        return 1.0 - ($dist / $len);
        }

    protected function similarity(string $a, string $b): float
    {
        $p = 0.0;
        similar_text($a, $b, $p);
        return $p / 100.0;
    }

    protected function extractUnitId(string $s): ?string
    {
        if ($s === '') return null;
        $s = strtoupper($s);
        if (preg_match('/\b(FLAT|APARTMENT|APT|UNIT|STUDIO|ROOM|MAISONETTE)\s+([A-Z0-9]+)\b/', $s, $m)) {
            return $m[2];
        }
        if (preg_match('/\b([0-9]+[A-Z]?)\b/', $s, $m)) {
            return $m[1];
        }
        return null;
    }
}
