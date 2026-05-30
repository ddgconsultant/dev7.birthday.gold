#Requires AutoHotkey v2.0
#SingleInstance Force

;; Comet Browser Enrollment Automation
;; Polls for pending enrollments and launches Comet to process them

;-------------------------------------------------------------------------------
; CONFIGURATION
;-------------------------------------------------------------------------------
scriptDir := A_ScriptDir
iniFile := scriptDir "\comet-enroller.ini"

if !FileExist(iniFile) {
    LogMessage("ERROR: Config file not found: " iniFile)
    ExitApp
}

baseUrl     := IniRead(iniFile, "API", "BaseUrl", "")
apiKey      := IniRead(iniFile, "API", "ApiKey", "")
cometExe    := IniRead(iniFile, "Comet", "ExePath", "")
cometTitle  := IniRead(iniFile, "Comet", "WindowTitle", "Comet")
promptText  := IniRead(iniFile, "Automation", "PromptText", "")
logFile     := IniRead(iniFile, "Logging", "LogFile", "comet-enroller.log")

; Resolve relative log path
if !InStr(logFile, "\") && !InStr(logFile, "/")
    logFile := scriptDir "\" logFile

if (baseUrl = "" || apiKey = "" || cometExe = "" || promptText = "") {
    LogMessage("ERROR: Missing required config values in " iniFile)
    ExitApp
}

;-------------------------------------------------------------------------------
; MAIN
;-------------------------------------------------------------------------------
LogMessage("=== Comet Enrollment Automation Started ===")

; Step 1: Authenticate — get auth_token and owner_id
LogMessage("Authenticating with API...")
authResponse := HttpPost(baseUrl "/api/auth", "api_key=" apiKey "&get_owner=1")

if (authResponse = "") {
    LogMessage("ERROR: Auth request failed — empty response")
    ExitApp
}

authToken := JsonExtract(authResponse, "auth_token")
ownerId   := JsonExtract(authResponse, "owner_id")

if (authToken = "") {
    LogMessage("ERROR: Auth failed — no auth_token in response")
    LogMessage("Response: " SubStr(authResponse, 1, 500))
    ExitApp
}

LogMessage("Authenticated. owner_id=" ownerId)

; Step 2: Check pending enrollment count
LogMessage("Checking pending enrollments...")
pendingResponse := HttpGet(baseUrl "/api/admin/pending-enrollment-count?auth_token=" authToken)

if (pendingResponse = "") {
    LogMessage("ERROR: Pending count request failed — empty response")
    ExitApp
}

pendingCount := JsonExtract(pendingResponse, "pending_count")
status       := JsonExtract(pendingResponse, "status")

if (status != "ok") {
    LogMessage("ERROR: API returned status=" status)
    LogMessage("Response: " SubStr(pendingResponse, 1, 500))
    ExitApp
}

LogMessage("Pending enrollments: " pendingCount)

if (pendingCount = "0" || pendingCount = "") {
    LogMessage("No pending enrollments. Exiting.")
    ExitApp
}

; Step 3: Extract first user's ID from the users array
firstUserId := JsonExtractArrayFirst(pendingResponse, "user_id")
firstName   := JsonExtractArrayFirst(pendingResponse, "name")

if (firstUserId = "") {
    LogMessage("ERROR: Could not extract first user_id from response")
    ExitApp
}

LogMessage("Processing first user: " firstName " (user_id=" firstUserId ")")

; Step 4: Launch Comet and navigate to comet-enroller page
enrollUrl := baseUrl "/admin/bgreb_v3/comet-enroller?aid=" ownerId "&uid=" firstUserId

LogMessage("Launching Comet: " cometExe)
LogMessage("URL: " enrollUrl)

try {
    Run(cometExe ' "' enrollUrl '"')
} catch as e {
    LogMessage("ERROR: Failed to launch Comet — " e.Message)
    ExitApp
}

; Step 5: Wait for Comet window
LogMessage("Waiting for Comet window...")
if !WinWait(cometTitle,, 30) {
    LogMessage("ERROR: Comet window did not appear within 30 seconds")
    ExitApp
}

WinActivate(cometTitle)
Sleep(3000)  ; Wait for page to load

; Step 6: Open Sidecar with Alt+A
LogMessage("Opening Sidecar (Alt+A)...")
Send("!a")
Sleep(1500)  ; Wait for Sidecar to open

; Step 7: Type enrollment prompt and submit
LogMessage("Typing enrollment prompt...")
SendText(promptText)
Sleep(500)
Send("{Enter}")

LogMessage("Prompt sent. Automation complete for user_id=" firstUserId)
LogMessage("=== Session Complete ===")
ExitApp

;-------------------------------------------------------------------------------
; HTTP FUNCTIONS (WinHttp COM)
;-------------------------------------------------------------------------------
HttpPost(url, postData) {
    try {
        whr := ComObject("WinHttp.WinHttpRequest.5.1")
        whr.Open("POST", url, false)
        whr.SetRequestHeader("Content-Type", "application/x-www-form-urlencoded")
        whr.Send(postData)

        if (whr.Status != 200) {
            LogMessage("HTTP POST failed: status=" whr.Status " url=" url)
            return ""
        }
        return whr.ResponseText
    } catch as e {
        LogMessage("HTTP POST error: " e.Message " url=" url)
        return ""
    }
}

HttpGet(url) {
    try {
        whr := ComObject("WinHttp.WinHttpRequest.5.1")
        whr.Open("GET", url, false)
        whr.Send()

        if (whr.Status != 200) {
            LogMessage("HTTP GET failed: status=" whr.Status " url=" url)
            return ""
        }
        return whr.ResponseText
    } catch as e {
        LogMessage("HTTP GET error: " e.Message " url=" url)
        return ""
    }
}

;-------------------------------------------------------------------------------
; JSON HELPERS (regex-based for simple flat responses)
;-------------------------------------------------------------------------------
JsonExtract(json, key) {
    ; Match "key": "value" or "key": number
    pattern := '"' key '"\s*:\s*"([^"]*)"'
    if RegExMatch(json, pattern, &m)
        return m[1]
    ; Try numeric value
    pattern := '"' key '"\s*:\s*(\d+)'
    if RegExMatch(json, pattern, &m)
        return m[1]
    return ""
}

JsonExtractArrayFirst(json, key) {
    ; Find first occurrence of "key": "value" or "key": number inside the users array
    usersPos := InStr(json, '"users"')
    if (usersPos = 0)
        return ""
    subset := SubStr(json, usersPos)
    return JsonExtract(subset, key)
}

;-------------------------------------------------------------------------------
; LOGGING
;-------------------------------------------------------------------------------
LogMessage(msg) {
    global logFile
    timestamp := FormatTime(, "yyyy-MM-dd HH:mm:ss")
    line := "[" timestamp "] " msg "`n"
    try {
        FileAppend(line, logFile)
    } catch {
        ; Silent fail on log write error
    }
}
