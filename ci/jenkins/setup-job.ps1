# Crea (o actualiza) el job "mayoro" en el Jenkins local y lanza el primer build.
#
# Uso:
#   powershell -ExecutionPolicy Bypass -File ci/jenkins/setup-job.ps1
#
# Opciones:
#   powershell -ExecutionPolicy Bypass -File ci/jenkins/setup-job.ps1 -JobName otroNombre
param(
    [string]$JenkinsUrl = "http://localhost:8080",
    [string]$JobName     = "mayoro",
    [string]$RepoUrl     = "https://github.com/MayorosCorp/Mayoro.git",
    [string]$Branch      = "develop"
)

$ErrorActionPreference = "Stop"

# --- 1. Credenciales: usa la contrasena inicial del admin --------------------
$adminPassword = docker exec mayoro-jenkins cat /var/jenkins_home/secrets/initialAdminPassword 2>$null
$adminPassword = "$adminPassword".Trim()

$user = "admin"
if (-not $adminPassword) {
    Write-Host "No hay contrasena inicial (ya se configuro un usuario)."
    $user = Read-Host "Usuario de Jenkins"
    $secure = Read-Host -AsSecureString "Contrasena"
    $adminPassword = [Net.NetworkCredential]::new("", $secure).Password
}

$auth = "Basic " + [Convert]::ToBase64String(
    [Text.Encoding]::UTF8.GetBytes("${user}:${adminPassword}"))

# --- 2. Crumb CSRF (Jenkins exige sesion + crumb para POST) ------------------
$crumb = Invoke-RestMethod -Uri "$JenkinsUrl/crumbIssuer/api/json" `
    -Headers @{ Authorization = $auth } -SessionVariable session
$headers = @{
    Authorization              = $auth
    "$($crumb.crumbRequestField)" = $crumb.crumb
}

# --- 3. Definicion del job (pipeline desde SCM) ------------------------------
$jobXml = @"
<?xml version='1.1' encoding='UTF-8'?>
<flow-definition plugin="workflow-job">
  <actions/>
  <description>Pipeline CI de Mayoro (${Branch}): dependencias, frontend, Pint, PHPUnit y despliegue local en http://localhost:8000</description>
  <keepDependencies>false</keepDependencies>
  <properties/>
  <definition class="org.jenkinsci.plugins.workflow.cps.CpsScmFlowDefinition" plugin="workflow-cps">
    <scm class="hudson.plugins.git.GitSCM" plugin="git">
      <configVersion>2</configVersion>
      <userRemoteConfigs>
        <hudson.plugins.git.UserRemoteConfig>
          <url>${RepoUrl}</url>
        </hudson.plugins.git.UserRemoteConfig>
      </userRemoteConfigs>
      <branches>
        <hudson.plugins.git.BranchSpec>
          <name>*/${Branch}</name>
        </hudson.plugins.git.BranchSpec>
      </branches>
      <doGenerateSubmoduleConfigurations>false</doGenerateSubmoduleConfigurations>
      <submoduleCfg class="empty-list"/>
      <extensions/>
    </scm>
    <scriptPath>Jenkinsfile</scriptPath>
    <lightweight>true</lightweight>
  </definition>
  <triggers>
    <hudson.triggers.SCMTrigger>
      <spec>H/5 * * * *</spec>
    </hudson.triggers.SCMTrigger>
  </triggers>
  <disabled>false</disabled>
</flow-definition>
"@

# --- 4. Crear o actualizar el job ---------------------------------------------
$exists = $false
try {
    Invoke-RestMethod -Uri "$JenkinsUrl/job/$JobName/api/json" `
        -Headers @{ Authorization = $auth } -WebSession $session | Out-Null
    $exists = $true
} catch { }

if ($exists) {
    Invoke-RestMethod -Method Post -Uri "$JenkinsUrl/job/$JobName/config.xml" `
        -Headers $headers -WebSession $session -ContentType "application/xml; charset=UTF-8" `
        -Body ([Text.Encoding]::UTF8.GetBytes($jobXml)) | Out-Null
    Write-Host "Job '$JobName' actualizado."
} else {
    Invoke-RestMethod -Method Post -Uri "$JenkinsUrl/createItem?name=$JobName" `
        -Headers $headers -WebSession $session -ContentType "application/xml; charset=UTF-8" `
        -Body ([Text.Encoding]::UTF8.GetBytes($jobXml)) | Out-Null
    Write-Host "Job '$JobName' creado."
}

# --- 5. Lanzar el primer build -------------------------------------------------
Invoke-RestMethod -Method Post -Uri "$JenkinsUrl/job/$JobName/build" `
    -Headers $headers -WebSession $session | Out-Null
Write-Host ""
Write-Host "Build lanzado. Sigue el progreso en:"
Write-Host "  $JenkinsUrl/job/$JobName"
