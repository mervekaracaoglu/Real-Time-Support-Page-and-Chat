<?php
// ✅ Firebase Database URL
$URL = "https://deneme-4e80d-default-rtdb.firebaseio.com/Chats.json";

// ✅ Function to Fetch All Messages
function get_all_messages() {
    global $URL;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}

// ✅ Function to Send an Admin Reply
// ✅ Function to Send an Admin Reply (Fix: Store under correct user)
function send_admin_reply($userId, $replyMsg) {
    global $URL;
    $replyURL = "https://deneme-4e80d-default-rtdb.firebaseio.com/Chats/$userId.json";

    $ch = curl_init();
    $reply_json = [
        "name" => "Admin",
        "subject" => "Admin Response",
        "msg" => htmlspecialchars($replyMsg),
        "time" => date('Y-m-d H:i:s')
    ];
    $encoded_json_obj = json_encode($reply_json);
    curl_setopt_array($ch, [
        CURLOPT_URL => $replyURL,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $encoded_json_obj
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}


// ✅ Function to Delete a Specific Message
function delete_message($userId, $messageId) {
    global $URL;
    $deleteURL = "https://deneme-4e80d-default-rtdb.firebaseio.com/Chats/$userId/$messageId.json"; // Message-specific path
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $deleteURL,
        CURLOPT_CUSTOMREQUEST => "DELETE", // DELETE request to remove message
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// ✅ Function to Reset All Messages
function reset_chat() {
    global $URL;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $URL,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// ✅ Handle Reset Chat (Delete All Messages)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["resetChat"])) {
    reset_chat();
    header("Location: admin.php");
    exit();
}

// ✅ Handle Delete Specific Message
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["deleteMessage"], $_POST["userId"], $_POST["messageId"])) {
    delete_message($_POST["userId"], $_POST["messageId"]);
    header("Location: admin.php");
    exit();
}

// ✅ Handle Admin Reply Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["adminReply"], $_POST["userId"])) {
    send_admin_reply($_POST["userId"], $_POST["replyMessage"]);
    header("Location: admin.php");
    exit();
}

// ✅ Fetch Messages
$all_chats = get_all_messages();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat Panel</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .chat-box {
            max-height: 500px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .message-container {
            border-bottom: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            background: white;
        }
        .delete-btn {
            background-color: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 14px;
        }
        .delete-btn:hover {
            background-color: darkred;
        }
        .reply-input {
            width: 80%;
            padding: 5px;
        }
        .reply-btn {
            background-color: blue;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 14px;
        }
        .reply-btn:hover {
            background-color: darkblue;
        }
    </style>
</head>
<body>

<h2>Admin Chat Panel</h2>

<!-- ✅ RESET CHAT BUTTON -->
<form method="POST">
    <button type="submit" name="resetChat" style="background-color: red; color: white; padding: 10px;">
        ⚠ Reset Chat (Delete All Messages)
    </button>
</form>

<div class="chat-box">
    <h3>All Messages</h3>
    <?php
    if (!empty($all_chats)) {
        foreach ($all_chats as $userId => $messages) {
            echo "<h4>User: <b>$userId</b></h4>";

            if (is_array($messages)) {
                foreach ($messages as $messageId => $chat_msg) {
                    $name = $chat_msg['name'] ?? "Unknown";
                    $subject = $chat_msg['subject'] ?? "No Subject";
                    $msg = $chat_msg['msg'] ?? "";
                    $time = $chat_msg['time'] ?? date("Y-m-d H:i:s");

                    echo "<div class='message-container'>
                            <p><b>$name ($subject):</b> $msg</p>
                            <p><i>Sent at: $time</i></p>

                            <!-- ✅ Admin Reply Form -->
                            <form method='POST' style='display:flex; gap:5px; align-items:center;'>
                                <input type='hidden' name='userId' value='$userId'>
                                <input type='text' name='replyMessage' class='reply-input' placeholder='Type a reply...' required>
                                <button type='submit' name='adminReply' class='reply-btn'>📩 Reply</button>
                            </form>

                            <!-- ✅ Delete Message Button -->
                            <form method='POST' style='margin-top:5px;'>
                                <input type='hidden' name='userId' value='$userId'>
                                <input type='hidden' name='messageId' value='$messageId'>
                                <button type='submit' name='deleteMessage' class='delete-btn'>🗑 Delete</button>
                            </form>
                          </div>";
                }
            } else {
                echo "<p>No messages from this user.</p>";
            }
        }
    } else {
        echo "<p>No messages yet.</p>";
    }
    ?>
</div>

</body>
</html>
