<?php
date_default_timezone_set('UTC');

$pilotRegistryPath = __DIR__ . '/trustorigin-backend/registry/pilot_registry.json';
$requestsLogPath  = __DIR__ . '/trustorigin-backend/registry/pilot_requests.json';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$institution = trim($_POST['institution'] ?? '');
$email       = trim($_POST['email'] ?? '');
$pilotCode   = trim($_POST['pilot_code'] ?? '');

if ($institution === '' || $email === '' || $pilotCode === '') {
    exit('Missing required fields.');
}

$registry = json_decode(file_get_contents($pilotRegistryPath), true);
$today = new DateTimeImmutable('now');

$pilotFound = false;
$pilotValid = false;

foreach ($registry['pilots'] as $pilot) {
    if ($pilot['pilot_id'] === $pilotCode) {
        $pilotFound = true;

        $expiry = new DateTimeImmutable($pilot['expiry_date_utc']);
        if ($pilot['pilot_status'] === 'active' && $today <= $expiry) {
            $pilotValid = true;
        }
        break;
    }
}

$requestLog = json_decode(file_get_contents($requestsLogPath), true);

$requestLog['requests'][] = [
    'timestamp_utc' => $today->format('Y-m-d\TH:i:s\Z'),
    'institution'   => $institution,
    'email'         => $email,
    'pilot_code'    => $pilotCode,
    'pilot_found'   => $pilotFound,
    'pilot_valid'   => $pilotValid
];

file_put_contents(
    $requestsLogPath,
    json_encode($requestLog, JSON_PRETTY_PRINT)
);

?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <title>Pilot Request Received — TrustOrigin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:Georgia,"Times New Roman",serif;padding:60px;color:#111}
    h1{font-size:32px}
    p{font-size:18px;line-height:1.6}
  </style>
</head>
<body>

<h1>Pilot Request Received</h1>

<?php if ($pilotValid): ?>
<p>
Thank you. Your request has been received and recorded.
Pilot access requests are reviewed by the TrustOrigin operator.
</p>
<p>
No automated activation occurs. You will be contacted directly.
</p>
<?php else: ?>
<p>
Your request has been recorded, however the pilot access code
could not be validated or has expired.
</p>
<p>
Please contact the TrustOrigin operator for clarification.
</p>
<?php endif; ?>

<p>
Contact: <a href="mailto:contact@trustorigin.org">contact@trustorigin.org</a>
</p>

</body>
</html>
