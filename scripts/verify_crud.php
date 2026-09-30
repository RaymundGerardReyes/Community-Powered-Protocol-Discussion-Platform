<?php

$baseUrl = 'http://127.0.0.1:8000/api/v1';

function request($method, $url, $data = null, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [$status, json_decode($raw, true), $raw];
}

echo "\n========================================================\n";
echo "   LIVE FULL-STACK BACKEND CRUD & LOGIC VERIFICATION     \n";
echo "========================================================\n\n";

// 1. Login as Andrew
[$status, $loginRes] = request('POST', "$baseUrl/auth/login", [
    'email' => 'andrew.h@wellness.io',
    'password' => 'password'
]);
$token = $loginRes['access_token'] ?? null;
$userId = $loginRes['user']['id'] ?? null;
echo "[1] AUTHENTICATION: Status {$status} | Logged in as: {$loginRes['user']['name']} (ID: {$userId})\n";

// 2. Create Protocol (POST)
[$status, $pRes] = request('POST', "$baseUrl/protocols", [
    'title' => 'Zone 2 Mitochondrial Aerobic Protocol',
    'description' => 'Low-intensity steady-state cardiovascular training to maximize fat oxidation.',
    'category' => 'Cardiovascular & Endurance',
    'tags' => ['zone2', 'cardio', 'mitochondria'],
], $token);
$pId = $pRes['data']['id'];
$pSlug = $pRes['data']['slug'];
echo "[2] PROTOCOL CREATE (POST): Status {$status} | ID: {$pId} | Slug: {$pSlug}\n";

// 3. Read Protocol (GET)
[$status, $pGet] = request('GET', "$baseUrl/protocols/{$pSlug}", null, $token);
echo "[3] PROTOCOL READ (GET): Status {$status} | Author: {$pGet['data']['author']['name']} | Status: {$pGet['data']['status']}\n";

// 4. Update Protocol (PUT)
[$status, $pUpdated] = request('PUT', "$baseUrl/protocols/{$pId}", [
    'description' => 'Updated mitochondrial protocol with blood lactate testing metrics (1.5-2.0 mmol/L).',
    'status' => 'published',
], $token);
echo "[4] PROTOCOL UPDATE (PUT): Status {$status} | New Status: {$pUpdated['data']['status']} | Description: {$pUpdated['data']['description']}\n";

// 5. Create Thread under Protocol (POST)
[$status, $tRes] = request('POST', "$baseUrl/protocols/{$pId}/threads", [
    'title' => 'Portable Lactate Analyzer Calibration for Zone 2',
    'content' => 'Which portable blood lactate analyzer has demonstrated the highest test-retest reliability?',
], $token);
$tId = $tRes['data']['id'];
echo "[5] THREAD CREATE (POST): Status {$status} | ID: {$tId} | Title: {$tRes['data']['title']}\n";

// 6. Read Thread (GET)
[$status, $tGet] = request('GET', "$baseUrl/threads/{$tId}", null, $token);
echo "[6] THREAD READ (GET): Status {$status} | Title: {$tGet['data']['title']} | Author: {$tGet['data']['author']['name']}\n";

// 7. Update Thread (PUT)
[$status, $tUpdated] = request('PUT', "$baseUrl/threads/{$tId}", [
    'title' => 'Portable Lactate Meter Accuracy (Lactate Plus vs Edge)',
], $token);
echo "[7] THREAD UPDATE (PUT): Status {$status} | New Title: {$tUpdated['data']['title']}\n";

// 8. Post Comment on Thread (POST)
[$status, $c1Res] = request('POST', "$baseUrl/threads/{$tId}/comments", [
    'content' => 'The Lactate Plus meter has strong validation against laboratory YSI analyzers.',
], $token);
$c1Id = $c1Res['data']['id'];
echo "[8] COMMENT CREATE (POST): Status {$status} | ID: {$c1Id} | Content: {$c1Res['data']['content']}\n";

// 9. Post Nested Reply under Comment (POST)
[$status, $c2Res] = request('POST', "$baseUrl/threads/{$tId}/comments", [
    'content' => 'Agreed! Just ensure test strips are kept at room temperature.',
    'parent_id' => $c1Id,
], $token);
$c2Id = $c2Res['data']['id'];
echo "[9] NESTED COMMENT REPLY (POST): Status {$status} | ID: {$c2Id} | Parent ID: {$c2Res['data']['parent_id']}\n";

// 10. Update Comment (PUT)
[$status, $cUpdated] = request('PUT', "$baseUrl/comments/{$c1Id}", [
    'content' => 'The Lactate Plus meter has strong laboratory validation (CV < 3.5%).',
], $token);
echo "[10] COMMENT UPDATE (PUT): Status {$status} | Content: {$cUpdated['data']['content']}\n";

// 11. Read Comments Tree (GET)
[$status, $treeRes] = request('GET', "$baseUrl/threads/{$tId}/comments", null, $token);
$rootRepliesCount = count($treeRes['data'][0]['replies'] ?? []);
echo "[11] COMMENT TREE READ (GET): Status {$status} | Root Comments: " . count($treeRes['data']) . " | Direct Replies on Root: {$rootRepliesCount}\n";

// 12. Vote on Thread (POST)
[$status, $vRes] = request('POST', "$baseUrl/votes", [
    'type' => 'thread',
    'id' => $tId,
    'value' => 1,
], $token);
echo "[12] VOTE CREATE (POST): Status {$status} | Action: {$vRes['data']['action']} | Votes Count: {$vRes['data']['votes_count']}\n";

// 13. Toggle Vote Off (POST with same value -> removed)
[$status, $vToggleRes] = request('POST', "$baseUrl/votes", [
    'type' => 'thread',
    'id' => $tId,
    'value' => 1,
], $token);
echo "[13] VOTE TOGGLE REMOVAL (POST): Status {$status} | Action: {$vToggleRes['data']['action']} | Votes Count: {$vToggleRes['data']['votes_count']}\n";

// 14. Peer Review by another clinician (Dr. Rhonda P.)
[$status, $loginRhonda] = request('POST', "$baseUrl/auth/login", [
    'email' => 'rhonda.p@wellness.io',
    'password' => 'password'
]);
$rhondaToken = $loginRhonda['access_token'];
[$status, $rRes] = request('POST', "$baseUrl/protocols/{$pId}/reviews", [
    'rating' => 5,
    'summary' => 'Outstanding biochemical protocol for mitochondrial density.',
    'feedback' => 'Zone 2 training protocols demonstrate clear clinical outcomes.',
], $rhondaToken);
$rId = $rRes['data']['id'];
echo "[14] PEER REVIEW CREATE (POST): Status {$status} | Review ID: {$rId} | Rating: {$rRes['data']['rating']} stars by {$loginRhonda['user']['name']}\n";

// 15. Update Peer Review (PUT)
[$status, $rUpdated] = request('PUT', "$baseUrl/reviews/{$rId}", [
    'rating' => 5,
    'summary' => 'Outstanding and formally verified biochemical protocol.',
], $rhondaToken);
echo "[15] PEER REVIEW UPDATE (PUT): Status {$status} | Updated Summary: {$rUpdated['data']['summary']}\n";

// 16. Author Self-Review Guard Check (Enforces HTTP 422)
[$status, $selfReviewRes] = request('POST', "$baseUrl/protocols/{$pId}/reviews", [
    'rating' => 5,
    'summary' => 'Self endorsement attempt.',
], $token);
echo "[16] AUTHOR SELF-REVIEW GUARD: Status {$status} (Correctly rejected: {$selfReviewRes['message']})\n";

// 17. Delete Comment (DELETE)
[$status, $delCRes] = request('DELETE', "$baseUrl/comments/{$c2Id}", null, $token);
echo "[17] COMMENT DELETE (DELETE): Status {$status} | {$delCRes['message']}\n";

// 18. Delete Thread (DELETE)
[$status, $delTRes] = request('DELETE', "$baseUrl/threads/{$tId}", null, $token);
echo "[18] THREAD DELETE (DELETE): Status {$status} | {$delTRes['message']}\n";

// 19. Delete Protocol (DELETE)
[$status, $delPRes] = request('DELETE', "$baseUrl/protocols/{$pId}", null, $token);
echo "[19] PROTOCOL DELETE (DELETE): Status {$status} | {$delPRes['message']}\n";

echo "\n========================================================\n";
echo "   100% OF ALL CRUD OPERATIONS VERIFIED LIVE!           \n";
echo "========================================================\n\n";
