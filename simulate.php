<?php
/**
 * Green-Chain Decision Portal — Simulation API
 * Handles AJAX POST requests and returns JSON results.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/scoring_engine.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    $body = $_POST;
}

// Extract weights (default equal)
$weights = [
    'financial'     => floatval($body['weight_financial']     ?? 33.3),
    'environmental' => floatval($body['weight_environmental'] ?? 33.3),
    'ethical'       => floatval($body['weight_ethical']       ?? 33.3),
];

// Extract scenario inputs
$inputsA = [
    'energy_source'    => $body['a_energy_source']    ?? 'coal',
    'supplier_type'    => $body['a_supplier_type']    ?? 'low_cost',
    'transport_mode'   => $body['a_transport_mode']   ?? 'air',
    'automation_level' => $body['a_automation_level'] ?? 'low',
    'carbon_offset'    => $body['a_carbon_offset']    ?? 'none',
];

$inputsB = [
    'energy_source'    => $body['b_energy_source']    ?? 'solar',
    'supplier_type'    => $body['b_supplier_type']    ?? 'esg',
    'transport_mode'   => $body['b_transport_mode']   ?? 'rail',
    'automation_level' => $body['b_automation_level'] ?? 'medium',
    'carbon_offset'    => $body['b_carbon_offset']    ?? 'aggressive',
];

// Score each scenario
$scoreA = ScoringEngine::calculatePillarScores($inputsA);
$scoreB = ScoringEngine::calculatePillarScores($inputsB);

$balA = ScoringEngine::balancedScore($scoreA, $weights);
$balB = ScoringEngine::balancedScore($scoreB, $weights);

$riskA = ScoringEngine::riskIndex($scoreA);
$riskB = ScoringEngine::riskIndex($scoreB);

$projA = ScoringEngine::strategicProjection($scoreA, $balA);
$projB = ScoringEngine::strategicProjection($scoreB, $balB);

$explanation = ScoringEngine::generateExplanation($scoreA, $scoreB, $balA, $balB, $inputsA, $inputsB);

$winner = $balA >= $balB ? 'A' : 'B';

echo json_encode([
    'scenario_a' => [
        'inputs'       => $inputsA,
        'pillar'       => $scoreA,
        'balanced'     => $balA,
        'risk_index'   => $riskA,
        'projection'   => $projA,
    ],
    'scenario_b' => [
        'inputs'       => $inputsB,
        'pillar'       => $scoreB,
        'balanced'     => $balB,
        'risk_index'   => $riskB,
        'projection'   => $projB,
    ],
    'weights'     => $weights,
    'winner'      => $winner,
    'explanation' => $explanation,
]);
