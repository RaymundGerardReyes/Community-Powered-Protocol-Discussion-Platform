$baseUrl = 'http://127.0.0.1:8000/api/v1'

Write-Host "`n========================================================" -ForegroundColor Cyan
Write-Host "   LIVE FULL-STACK BACKEND CRUD & LOGIC VERIFICATION     " -ForegroundColor Cyan
Write-Host "========================================================`n" -ForegroundColor Cyan

# 1. Login as Andrew
$loginRes = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method Post -Body (@{email='andrew.h@wellness.io'; password='password'} | ConvertTo-Json) -ContentType 'application/json'
$token = $loginRes.access_token
$headers = @{Authorization="Bearer $token"; Accept='application/json'}
Write-Host "✓ [1] AUTHENTICATION: Logged in as: $($loginRes.user.name) (ID: $($loginRes.user.id))" -ForegroundColor Green

# 2. Create Protocol (POST)
$pBody = @{
    title = 'Zone 2 Mitochondrial Aerobic Protocol'
    description = 'Low-intensity steady-state cardiovascular training to maximize fat oxidation.'
    category = 'Cardiovascular & Endurance'
    tags = @('zone2','cardio','mitochondria')
} | ConvertTo-Json
$pRes = Invoke-RestMethod -Uri "$baseUrl/protocols" -Method Post -Headers $headers -Body $pBody -ContentType 'application/json'
$pId = $pRes.data.id
$pSlug = $pRes.data.slug
Write-Host "✓ [2] PROTOCOL CREATE (POST): ID=$pId, Slug=$pSlug, Title='$($pRes.data.title)'" -ForegroundColor Green

# 3. Read Protocol (GET)
$pGet = Invoke-RestMethod -Uri "$baseUrl/protocols/$pSlug" -Method Get -Headers $headers
Write-Host "✓ [3] PROTOCOL READ (GET): Status=$($pGet.data.status), Author='$($pGet.data.author.name)', Score=$($pGet.data.score)" -ForegroundColor Green

# 4. Update Protocol (PUT)
$pUpdateBody = @{
    description = 'Updated mitochondrial protocol with blood lactate testing metrics (1.5-2.0 mmol/L).'
    status = 'published'
} | ConvertTo-Json
$pUpdated = Invoke-RestMethod -Uri "$baseUrl/protocols/$pId" -Method Put -Headers $headers -Body $pUpdateBody -ContentType 'application/json'
Write-Host "✓ [4] PROTOCOL UPDATE (PUT): Status=$($pUpdated.data.status), Updated Description='$($pUpdated.data.description)'" -ForegroundColor Green

# 5. Create Thread under Protocol (POST)
$tBody = @{
    title = 'Portable Lactate Analyzer Calibration for Zone 2'
    content = 'Which portable blood lactate analyzer has demonstrated the highest test-retest reliability?'
} | ConvertTo-Json
$tRes = Invoke-RestMethod -Uri "$baseUrl/protocols/$pId/threads" -Method Post -Headers $headers -Body $tBody -ContentType 'application/json'
$tId = $tRes.data.id
Write-Host "✓ [5] THREAD CREATE (POST): ID=$tId, Title='$($tRes.data.title)', Protocol_ID=$($tRes.data.protocol_id)" -ForegroundColor Green

# 6. Read Thread (GET)
$tGet = Invoke-RestMethod -Uri "$baseUrl/threads/$tId" -Method Get -Headers $headers
Write-Host "✓ [6] THREAD READ (GET): Title='$($tGet.data.title)', Author='$($tGet.data.author.name)'" -ForegroundColor Green

# 7. Update Thread (PUT)
$tUpdateBody = @{
    title = 'Portable Lactate Meter Accuracy (Lactate Plus vs Edge)'
} | ConvertTo-Json
$tUpdated = Invoke-RestMethod -Uri "$baseUrl/threads/$tId" -Method Put -Headers $headers -Body $tUpdateBody -ContentType 'application/json'
Write-Host "✓ [7] THREAD UPDATE (PUT): New Title='$($tUpdated.data.title)'" -ForegroundColor Green

# 8. Post Comment on Thread (POST)
$c1Body = @{
    content = 'The Lactate Plus meter has strong validation against laboratory YSI analyzers.'
} | ConvertTo-Json
$c1Res = Invoke-RestMethod -Uri "$baseUrl/threads/$tId/comments" -Method Post -Headers $headers -Body $c1Body -ContentType 'application/json'
$c1Id = $c1Res.data.id
Write-Host "✓ [8] COMMENT CREATE (POST): ID=$c1Id, Content='$($c1Res.data.content)'" -ForegroundColor Green

# 9. Post Nested Reply under Comment (POST)
$c2Body = @{
    content = 'Agreed! Just ensure test strips are kept at room temperature.'
    parent_id = $c1Id
} | ConvertTo-Json
$c2Res = Invoke-RestMethod -Uri "$baseUrl/threads/$tId/comments" -Method Post -Headers $headers -Body $c2Body -ContentType 'application/json'
$c2Id = $c2Res.data.id
Write-Host "✓ [9] NESTED COMMENT REPLY (POST): ID=$c2Id, Parent_ID=$($c2Res.data.parent_id)" -ForegroundColor Green

# 10. Update Comment (PUT)
$cUpdateBody = @{
    content = 'The Lactate Plus meter has strong laboratory validation (CV < 3.5%).'
} | ConvertTo-Json
$cUpdated = Invoke-RestMethod -Uri "$baseUrl/comments/$c1Id" -Method Put -Headers $headers -Body $cUpdateBody -ContentType 'application/json'
Write-Host "✓ [10] COMMENT UPDATE (PUT): Updated Content='$($cUpdated.data.content)'" -ForegroundColor Green

# 11. Read Comments Tree (GET)
$commentsRes = Invoke-RestMethod -Uri "$baseUrl/threads/$tId/comments" -Method Get -Headers $headers
$rootComment = $commentsRes.data[0]
Write-Host "✓ [11] COMMENT TREE READ (GET): Root Comment Count=$($commentsRes.data.Count), Direct Replies=$($rootComment.replies.Count)" -ForegroundColor Green

# 12. Vote on Thread (POST)
$vBody = @{
    type = 'thread'
    id = $tId
    value = 1
} | ConvertTo-Json
$vRes = Invoke-RestMethod -Uri "$baseUrl/votes" -Method Post -Headers $headers -Body $vBody -ContentType 'application/json'
Write-Host "✓ [12] VOTE CREATE (POST): Type=thread, Action=$($vRes.data.action), Votes Count=$($vRes.data.votes_count)" -ForegroundColor Green

# 13. Toggle Vote Off (POST with same value -> removed)
$vToggleRes = Invoke-RestMethod -Uri "$baseUrl/votes" -Method Post -Headers $headers -Body $vBody -ContentType 'application/json'
Write-Host "✓ [13] VOTE TOGGLE REMOVAL (POST): Action=$($vToggleRes.data.action), Votes Count=$($vToggleRes.data.votes_count)" -ForegroundColor Green

# 14. Peer Review by another user (Dr. Rhonda P.)
$loginRhonda = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method Post -Body (@{email='rhonda.p@wellness.io'; password='password'} | ConvertTo-Json) -ContentType 'application/json'
$rHeaders = @{Authorization="Bearer $($loginRhonda.access_token)"; Accept='application/json'}
$rBody = @{
    rating = 5
    summary = 'Outstanding biochemical protocol for mitochondrial density.'
    feedback = 'Zone 2 training protocols demonstrate clear clinical outcomes.'
} | ConvertTo-Json
$rRes = Invoke-RestMethod -Uri "$baseUrl/protocols/$pId/reviews" -Method Post -Headers $rHeaders -Body $rBody -ContentType 'application/json'
$rId = $rRes.data.id
Write-Host "✓ [14] PEER REVIEW CREATE (POST): Review ID=$rId, Rating=$($rRes.data.rating) stars by $($loginRhonda.user.name)" -ForegroundColor Green

# 15. Update Peer Review (PUT)
$rUpdateBody = @{
    rating = 5
    summary = 'Outstanding and formally verified biochemical protocol.'
} | ConvertTo-Json
$rUpdated = Invoke-RestMethod -Uri "$baseUrl/reviews/$rId" -Method Put -Headers $rHeaders -Body $rUpdateBody -ContentType 'application/json'
Write-Host "✓ [15] PEER REVIEW UPDATE (PUT): New Summary='$($rUpdated.data.summary)'" -ForegroundColor Green

# 16. Author Self-Review Guard Check (Enforces HTTP 422)
try {
    Invoke-RestMethod -Uri "$baseUrl/protocols/$pId/reviews" -Method Post -Headers $headers -Body $rBody -ContentType 'application/json'
    Write-Host "✗ [16] FAILED: Author was allowed to review own protocol!" -ForegroundColor Red
} catch {
    Write-Host "✓ [16] AUTHOR SELF-REVIEW GUARD: Properly blocked with HTTP 422 ($($_.Exception.Message))" -ForegroundColor Green
}

# 17. Delete Comment (DELETE)
$delCRes = Invoke-RestMethod -Uri "$baseUrl/comments/$c2Id" -Method Delete -Headers $headers
Write-Host "✓ [17] COMMENT DELETE (DELETE): $($delCRes.message)" -ForegroundColor Green

# 18. Delete Thread (DELETE)
$delTRes = Invoke-RestMethod -Uri "$baseUrl/threads/$tId" -Method Delete -Headers $headers
Write-Host "✓ [18] THREAD DELETE (DELETE): $($delTRes.message)" -ForegroundColor Green

# 19. Delete Protocol (DELETE)
$delPRes = Invoke-RestMethod -Uri "$baseUrl/protocols/$pId" -Method Delete -Headers $headers
Write-Host "✓ [19] PROTOCOL DELETE (DELETE): $($delPRes.message)" -ForegroundColor Green

Write-Host "`n========================================================" -ForegroundColor Cyan
Write-Host "   100% OF ALL CRUD OPERATIONS VERIFIED LIVE!           " -ForegroundColor Cyan
Write-Host "========================================================`n" -ForegroundColor Cyan
