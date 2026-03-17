<?php
// ShipperShop Auto Deploy Webhook
// GitHub sends POST here when code is pushed

$secret = "1d0994f219cc32a568f5a99269e2c70f5fb89834";

// Verify GitHub signature
$payload = file_get_contents("php://input");
$sig = $_SERVER["HTTP_X_HUB_SIGNATURE_256"] ?? "";
$expected = "sha256=" . hash_hmac("sha256", $payload, $secret);

if (!hash_equals($expected, $sig)) {
    http_response_code(403);
    die("Invalid signature");
}

// Only deploy on push to main/master
$data = json_decode($payload, true);
$branch = str_replace("refs/heads/", "", $data["ref"] ?? "");
if (!in_array($branch, ["main", "master"])) {
    die("Skipping branch: $branch");
}

// Log
$log = date("Y-m-d H:i:s") . " - Deploy triggered by push to $branch\n";
file_put_contents(__DIR__ . "/deploy.log", $log, FILE_APPEND);

// Pull latest code
chdir("/home/nhshiw2j/public_html");
$output = shell_exec("GIT_SSH_COMMAND='ssh -i /home/nhshiw2j/.ssh/github_key -o StrictHostKeyChecking=no' git pull origin $branch 2>&1");

$log2 = date("Y-m-d H:i:s") . " - Result: $output\n";
file_put_contents(__DIR__ . "/deploy.log", $log2, FILE_APPEND);

echo "Deployed: $output";
