<?php
require_once 'functions.php';

$prefilledEmail = isset($_GET['email']) ? strtolower(trim($_GET['email'])) : '';
$emailMessages = '';
$verificationMessages = '';
$shouldRefresh = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['unsubscribe_verification_code'])) {
        $email = $prefilledEmail;
        $code = trim($_POST['unsubscribe_verification_code']);
        $codeFile = __DIR__ . "/codes/unsubscribe_" . md5($email) . ".txt";

        if (file_exists($codeFile) && trim(file_get_contents($codeFile)) === $code) {
            $unsubscribed = unsubscribeEmail($email);
            unlink($codeFile);
            if($unsubscribed) {
                $verificationMessages = "Email $email successfully unsubscribed.";
            }
            else {
                $verificationMessages = "Email $email is already unsubscribed!";
            }
            $verificationMessages .= " Page will redirect in 5 seconds...";
            $shouldRefresh = true;
        } else {
            $verificationMessages = "Invalid verification code for unsubscription!";
        }
    }

    elseif (isset($_POST['unsubscribe_email'])) {
        $email = $prefilledEmail;
        $code = generateVerificationCode();
        if (!is_dir(__DIR__ . '/codes')) mkdir(__DIR__ . '/codes');
        file_put_contents(__DIR__ . "/codes/unsubscribe_" . md5($email) . ".txt", $code);
        $sendUEmail = sendUnsubscriptionEmail($email, $code);
        if($sendUEmail) {
            $emailMessages = "Unsubscribe confirmation code sent to $email , do not refresh this page!";
        } 
        else {
            $emailMessages = "Unable to send unsubscribe confirmation code to $email !";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Unsubscribe from Updates</title>
    <?php if ($shouldRefresh): ?>
        <meta http-equiv="refresh" content="5;url=index.php">
    <?php endif; ?>
</head>
<body>
    <h2>Unsubscribe from GitHub Timeline Updates</h2>
    <p><strong><?= htmlspecialchars($emailMessages) ?></strong></p>

    <form method="POST">
        <label>Email to be Unsubscribed:</label><br>
        <input type="email" name="unsubscribe_email" required value="<?= htmlspecialchars($prefilledEmail) ?>" readonly>
        <button id="submit-unsubscribe">Unsubscribe</button>
    </form>

    <br>
    <p><strong><?= htmlspecialchars($verificationMessages) ?></strong></p>

    <form method="POST">
        <label>Enter Unsubscribe Verification Code:</label><br>
        <input type="text" name="unsubscribe_verification_code" maxlength="6" required>
        <button id="verify-unsubscribe">Verify</button>
    </form>
</body>
</html>
