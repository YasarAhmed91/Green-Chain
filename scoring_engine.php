<?php
/**
 * Green-Chain Decision Portal — Scoring Engine
 * Calculates Financial, Environmental, Ethical scores
 * and derived metrics for each scenario.
 */

class ScoringEngine {

    // Base score matrices for each dimension
    private static $scores = [
        'energy_source' => [
            'coal'      => ['financial' => 85, 'environmental' => 10, 'ethical' => 20],
            'hybrid'    => ['financial' => 70, 'environmental' => 55, 'ethical' => 55],
            'solar'     => ['financial' => 55, 'environmental' => 95, 'ethical' => 90],
        ],
        'supplier_type' => [
            'low_cost'  => ['financial' => 90, 'environmental' => 25, 'ethical' => 15],
            'local'     => ['financial' => 60, 'environmental' => 70, 'ethical' => 75],
            'esg'       => ['financial' => 65, 'environmental' => 85, 'ethical' => 95],
        ],
        'transport_mode' => [
            'air'       => ['financial' => 80, 'environmental' => 15, 'ethical' => 30],
            'ship'      => ['financial' => 70, 'environmental' => 45, 'ethical' => 50],
            'rail'      => ['financial' => 60, 'environmental' => 85, 'ethical' => 75],
        ],
        'automation_level' => [
            'low'       => ['financial' => 45, 'environmental' => 55, 'ethical' => 75],
            'medium'    => ['financial' => 70, 'environmental' => 65, 'ethical' => 60],
            'high'      => ['financial' => 90, 'environmental' => 70, 'ethical' => 40],
        ],
        'carbon_offset' => [
            'none'      => ['financial' => 90, 'environmental' => 10, 'ethical' => 15],
            'moderate'  => ['financial' => 75, 'environmental' => 60, 'ethical' => 65],
            'aggressive'=> ['financial' => 55, 'environmental' => 95, 'ethical' => 90],
        ],
    ];

    /**
     * Calculate pillar scores from inputs
     */
    public static function calculatePillarScores(array $inputs): array {
        $financial    = 0;
        $environmental = 0;
        $ethical      = 0;
        $count        = 0;

        $fieldMap = [
            'energy_source'   => $inputs['energy_source']   ?? null,
            'supplier_type'   => $inputs['supplier_type']   ?? null,
            'transport_mode'  => $inputs['transport_mode']  ?? null,
            'automation_level'=> $inputs['automation_level']?? null,
            'carbon_offset'   => $inputs['carbon_offset']   ?? null,
        ];

        foreach ($fieldMap as $category => $value) {
            if ($value && isset(self::$scores[$category][$value])) {
                $s = self::$scores[$category][$value];
                $financial     += $s['financial'];
                $environmental += $s['environmental'];
                $ethical       += $s['ethical'];
                $count++;
            }
        }

        if ($count === 0) {
            return ['financial' => 0, 'environmental' => 0, 'ethical' => 0];
        }

        return [
            'financial'     => round($financial / $count, 1),
            'environmental' => round($environmental / $count, 1),
            'ethical'       => round($ethical / $count, 1),
        ];
    }

    /**
     * Compute the weighted Balanced Decision Score
     */
    public static function balancedScore(array $pillar, array $weights): float {
        $total = ($weights['financial'] + $weights['environmental'] + $weights['ethical']);
        if ($total == 0) $total = 1;

        $wf = $weights['financial']    / $total;
        $we = $weights['environmental']/ $total;
        $wt = $weights['ethical']      / $total;

        return round(
            ($wf * $pillar['financial']) +
            ($we * $pillar['environmental']) +
            ($wt * $pillar['ethical']),
            1
        );
    }

    /**
     * Risk Index: higher variance between pillars = higher risk
     * Formula: normalized standard deviation of the three pillar scores
     */
    public static function riskIndex(array $pillar): float {
        $values = [$pillar['financial'], $pillar['environmental'], $pillar['ethical']];
        $mean = array_sum($values) / 3;
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / 3;
        $stdDev = sqrt($variance);
        // Normalize 0-100: max possible std dev for 0-100 range is ~47
        return round(min(100, ($stdDev / 47) * 100), 1);
    }

    /**
     * 5-Year Strategic Projection
     * Applies domain multipliers to base score
     */
    public static function strategicProjection(array $pillar, float $balanced): array {
        $envMultiplier  = 1 + (($pillar['environmental'] / 100) * 0.35);  // up to +35% cost savings
        $ethMultiplier  = 1 + (($pillar['ethical']       / 100) * 0.25);  // up to +25% brand trust
        $autoScore      = 0;

        // Automation contribution embedded in financial score proxy
        $autoMultiplier = 1 + (($pillar['financial']     / 100) * 0.20);  // up to +20% productivity

        $projectedValue = round($balanced * $envMultiplier * $ethMultiplier * $autoMultiplier, 1);
        $growthPct      = round((($projectedValue - $balanced) / max(1, $balanced)) * 100, 1);

        return [
            'base_score'       => $balanced,
            'projected_value'  => min(100, $projectedValue),
            'growth_pct'       => $growthPct,
            'env_multiplier'   => round($envMultiplier, 3),
            'eth_multiplier'   => round($ethMultiplier, 3),
            'auto_multiplier'  => round($autoMultiplier, 3),
        ];
    }

    /**
     * Generate AI-style textual explanation
     */
    public static function generateExplanation(
        array $scoreA, array $scoreB,
        float $balA, float $balB,
        array $inputsA, array $inputsB
    ): string {
        $winner = $balA >= $balB ? 'A' : 'B';
        $loser  = $winner === 'A' ? 'B' : 'A';
        $winScore  = $winner === 'A' ? $scoreA : $scoreB;
        $loseScore = $winner === 'A' ? $scoreB : $scoreA;
        $winBal    = max($balA, $balB);
        $loseBal   = min($balA, $balB);
        $delta     = round(abs($balA - $balB), 1);

        $strengths = [];
        $weaknesses = [];

        if ($winScore['environmental'] > $loseScore['environmental'] + 10)
            $strengths[] = "superior environmental performance";
        if ($winScore['ethical'] > $loseScore['ethical'] + 10)
            $strengths[] = "stronger ethical compliance";
        if ($winScore['financial'] > $loseScore['financial'] + 10)
            $strengths[] = "competitive financial efficiency";

        if ($loseScore['environmental'] < 50)
            $weaknesses[] = "environmental exposure";
        if ($loseScore['ethical'] < 50)
            $weaknesses[] = "ethical risk factors";
        if ($loseScore['financial'] < 50)
            $weaknesses[] = "financial underperformance";

        $winnerInputs = $winner === 'A' ? $inputsA : $inputsB;
        $drivers = [];

        if (($winnerInputs['energy_source'] ?? '') === 'solar')
            $drivers[] = "renewable energy adoption";
        if (($winnerInputs['supplier_type'] ?? '') === 'esg')
            $drivers[] = "ESG-compliant supply chain";
        if (($winnerInputs['carbon_offset'] ?? '') === 'aggressive')
            $drivers[] = "aggressive carbon offset strategy";
        if (($winnerInputs['transport_mode'] ?? '') === 'rail')
            $drivers[] = "low-emission rail logistics";

        $strengthText  = !empty($strengths) ? implode(', ', $strengths) : "a more balanced pillar distribution";
        $driverText    = !empty($drivers)   ? "Key drivers include: " . implode(', ', $drivers) . ". " : "";
        $weaknessText  = !empty($weaknesses)? " Scenario {$loser} shows vulnerability in: " . implode(' and ', $weaknesses) . "." : "";

        return "Scenario {$winner} delivers the stronger balanced outcome with a Decision Score of {$winBal} — outperforming Scenario {$loser} by {$delta} points. " .
               "This is driven by {$strengthText}. " .
               $driverText .
               "The integrated pillar balance reduces long-term strategic risk while improving stakeholder trust and regulatory readiness." .
               $weaknessText .
               " Executives should consider prioritizing Scenario {$winner} as the default strategic path, with targeted improvements to its lower-scoring dimensions over the next 18-24 months.";
    }
}
