<?php
require_once 'functions.php';

$prefilledEmail = '';
$emailMessages = '';
$verificationMessages = '';
$shouldRefresh = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verification_code'], $_POST['email'])) {
        $email = trim($_POST['email']);
        $code = trim($_POST['verification_code']);
        $storedPath = __DIR__ . "/codes/verify_" . md5($email) . ".txt";

        if (file_exists($storedPath) && trim(file_get_contents($storedPath)) === $code) {
            $registered = registerEmail($email);
            unlink($storedPath);
            if($registered) {
                $verificationMessages = "Email $email successfully verified and registered.";
            }
            else {
                $verificationMessages = "Email $email is already registered!";
            } 
            $verificationMessages .= " Page will refresh in 5 seconds...";
            $shouldRefresh = true;
        } else {
            $verificationMessages = "Invalid verification code!";
            $prefilledEmail = $email;
        }
    }

    elseif (isset($_POST['email'])) {
        $email = strtolower(trim($_POST['email']));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $code = generateVerificationCode();
            if (!is_dir(__DIR__ . '/codes')) mkdir(__DIR__ . '/codes');
            file_put_contents(__DIR__ . "/codes/verify_" . md5($email) . ".txt", $code);
            $sentVEmail = sendVerificationEmail($email, $code);
            if($sentVEmail) {
                $emailMessages = "Verification code sent to $email , do not refresh this page!";
            } else {
                $emailMessages = "Unable to send verification code to $email !";
            }
            $prefilledEmail = $email;
        } else {
            $emailMessages = "Invalid email format!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Registration</title>
    <?php if ($shouldRefresh): ?>
        <meta http-equiv="refresh" content="5;url=<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
    <?php endif; ?>
</head>
<body>
    <h2>Register for GitHub Timeline Updates</h2>
    <p><strong><?= htmlspecialchars($emailMessages) ?></strong></p>

    <form method="POST">
        <label>Email:</label><br>
        <input type="email" name="email" required>
        <button id="submit-email">Submit</button>
    </form>

    <br>
    <p><strong><?= htmlspecialchars($verificationMessages) ?></strong></p>


    <form method="POST">
        <label>Enter Verification Code:</label><br>
        <input type="text" name="verification_code" maxlength="6" required>
        <input type="email" name="email" required value="<?= htmlspecialchars($prefilledEmail) ?>" readonly>
        <button id="submit-verification">Verify</button>
    </form>
</body>
</html>
