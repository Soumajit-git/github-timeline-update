<?php

/**
 * Generate a 6-digit numeric verification code.
 */
function generateVerificationCode(): string {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send a verification code to an email.
 */
function sendVerificationEmail(string $email, string $code): bool {
    $subject = 'Your Verification Code';
    $message = "<p>Your verification code is: <strong>$code</strong></p>";
    $headers = "From: no-reply@example.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($email, $subject, $message, $headers);
}

/**
 * Send a unsubscription code to an email.
 */
function sendUnsubscriptionEmail($email, $code): bool {
    $subject = 'Confirm Unsubscription';
    $message = "<p>To confirm unsubscription, use this code: <strong>$code</strong></p>";
    $headers = "From: no-reply@example.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($email, $subject, $message, $headers);
}

/**
 * Register an email by storing it in a file.
 */
function registerEmail(string $email): bool {
  $file = __DIR__ . '/registered_emails.txt';
  $emails = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    if (!in_array($email, $emails)) {
        $fp = fopen($file, 'a');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, $email . PHP_EOL);
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return true;
    }
  return false;
}

/**
 * Unsubscribe an email by removing it from the list.
 */
function unsubscribeEmail(string $email): bool {
  $file = __DIR__ . '/registered_emails.txt';
   if (file_exists($file)) {
        $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $originalCount = count($emails);
        $filtered = array_filter($emails, fn($e) => trim($e) !== trim($email));
        if (count($filtered) === $originalCount) {
            // Email not found, nothing removed
            return false;
        }
        $fp = fopen($file, 'w');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, implode(PHP_EOL, $filtered) . PHP_EOL);
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return true;
    }
  return false;
}

/**
 * Fetch GitHub timeline.
 */
function fetchGitHubTimeline() {
    $data = @file_get_contents("https://www.github.com/timeline");
    if ($data === false) {
        error_log("Failed to fetch GitHub Timeline at " . date('Y-m-d H:i:s'));
    }
    return $data;
}

/**
 * Format GitHub timeline data. Returns a valid HTML string.
 */
function formatGitHubData(array $data): string {
    $html = '<h2>GitHub Timeline Updates</h2>';
    $html .= '<table border="1">';
    $html .= '<tr><th>Event</th><th>User</th></tr>';

    if (empty($data)) {
        // Fallback to dummy data if no actual data is provided
        $data = [
            ['event' => 'Push', 'user' => 'testuser']
        ];
    }

    foreach ($data as $item) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($item['event']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['user']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    $html .= '<p><a href="unsubscribe_url" id="unsubscribe-button">Unsubscribe</a></p>';
    return $html;
}

/**
 * Send the formatted GitHub updates to registered emails.
 */
function sendGitHubUpdatesToSubscribers(): void {
  $file = __DIR__ . '/registered_emails.txt';
  if (!file_exists($file)) return;
    
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $rawData = fetchGitHubTimeline();
    $extractedData = [];
    
    if ($rawData !== false && !empty($rawData)) {
        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($rawData);
        libxml_clear_errors();
        if ($feed !== false) {
            foreach ($feed->entry as $entry) {
              $eventType = 'Unknown Event'; // Default value if extraction fails
              $userName = 'Unknown User';   // Default value if extraction fails
              
              // The format is typically: tag:github.com,2008:PushEvent/50590499605
              if (isset($entry->id)) {
                  $idString = (string)$entry->id;
                  $parts = explode(':', $idString); // Splits by ':'
            
                  // Check if the part containing the event type exists (e.g., 'PushEvent/ID')
                  if (isset($parts[2])) {
                      $eventAndId = explode('/', $parts[2]); // Splits 'PushEvent/ID' by '/'
                
                      // Check if the event type part exists (e.g., 'PushEvent')
                      if (isset($eventAndId[0])) {
                          $rawEventType = $eventAndId[0];
                    
                          // Remove the 'Event' suffix (e.g., 'PushEvent' becomes 'Push')
                          $eventType = str_replace('Event', '', $rawEventType);
                    
                          // Add spaces before capital letters for better readability (e.g., 'PullRequest' becomes 'Pull Request')
                          $eventType = preg_replace('/(?<!^)([A-Z])/', ' $1', $eventType);
                    
                          $eventType = trim($eventType); // Remove any leading/trailing whitespace
                      }
                  }
              }
        
              // 2. Extract Username from the '<author><name>' tag.
              if (isset($entry->author->name)) {
                  $userName = (string)$entry->author->name;
              }

              $extractedData[] = [
                  'event' => $eventType,
                  'user'  => $userName,
              ];
            }
        }
    }
    $formatted = formatGitHubData($extractedData);

    foreach ($emails as $email) {
        $unsubscribeLink = "http://localhost/src/unsubscribe.php?email=" . urlencode($email);
        $html = str_replace("unsubscribe_url", $unsubscribeLink, $formatted);
        
        $subject = "Latest GitHub Updates";
        $headers = "From: no-reply@example.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        mail($email, $subject, $html, $headers);
    }
}
