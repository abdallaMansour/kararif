# Fetches Sanctum bearer tokens via POST /api/auth/login (Adventurer app auth).
# Requires the API running (e.g. php artisan serve) and accounts in `adventurers`.
#
# Usage:
#   .\scripts\fetch-app-tokens.ps1
#   .\scripts\fetch-app-tokens.ps1 -BaseUrl "https://your-api.example.com"
#
# Override credentials without editing:
#   $env:KARARIF_BASE_URL = "http://127.0.0.1:8000"
#   $env:KARARIF_CREATOR_EMAIL = "..."
#   $env:KARARIF_CREATOR_PASSWORD = "..."
#   $env:KARARIF_PLAYER_EMAIL = "..."
#   $env:KARARIF_PLAYER_PASSWORD = "..."
#   .\scripts\fetch-app-tokens.ps1

param(
    [string] $BaseUrl = $env:KARARIF_BASE_URL
)

if ([string]::IsNullOrWhiteSpace($BaseUrl)) {
    $BaseUrl = "http://127.0.0.1:8000"
}

$creatorEmail = if ($env:KARARIF_CREATOR_EMAIL) { $env:KARARIF_CREATOR_EMAIL } else { "moamen.hamed33322@gmail.com" }
$creatorPass = if ($env:KARARIF_CREATOR_PASSWORD) { $env:KARARIF_CREATOR_PASSWORD } else { "6789" }
$playerEmail = if ($env:KARARIF_PLAYER_EMAIL) { $env:KARARIF_PLAYER_EMAIL } else { "moamen.hamed3334422@gmail.com" }
$playerPass = if ($env:KARARIF_PLAYER_PASSWORD) { $env:KARARIF_PLAYER_PASSWORD } else { "1234" }

$BaseUrl = $BaseUrl.TrimEnd('/')
$loginUrl = "$BaseUrl/api/auth/login"

function Get-AppToken {
    param([string]$Email, [string]$Password, [string]$Label)
    $body = @{ email = $Email; password = $Password } | ConvertTo-Json
    try {
        $r = Invoke-RestMethod -Uri $loginUrl -Method Post -Body $body -ContentType "application/json; charset=utf-8"
        if (-not $r.success) {
            Write-Host "[$Label] Login failed: $($r | ConvertTo-Json -Compress)" -ForegroundColor Red
            return $null
        }
        $token = $r.data.token
        Write-Host "[$Label] email=$Email" -ForegroundColor Cyan
        Write-Host "[$Label] token=$token" -ForegroundColor Green
        Write-Host ""
        return $token
    } catch {
        Write-Host "[$Label] HTTP error: $_" -ForegroundColor Red
        if ($_.Exception.Response) {
            $reader = [System.IO.StreamReader]::new($_.Exception.Response.GetResponseStream())
            Write-Host $reader.ReadToEnd() -ForegroundColor Red
        }
        return $null
    }
}

Write-Host "POST $loginUrl" -ForegroundColor DarkGray
Write-Host ""

$null = Get-AppToken -Email $creatorEmail -Password $creatorPass -Label "room-creator"
$null = Get-AppToken -Email $playerEmail -Password $playerPass -Label "other-player"

Write-Host "Use header: Authorization: Bearer <token>" -ForegroundColor DarkGray
