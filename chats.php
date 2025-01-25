<?php
session_start();

// ✅ Firebase Database URL
$URL = "https://deneme-4e80d-default-rtdb.firebaseio.com/Chats.json";

// ✅ Function to Fetch Messages for the Logged-in User
function get_messages($userId) { 
    global $URL;
    if (empty($userId)) {
        return [];
    }
    
    $userMessagesURL = "https://deneme-4e80d-default-rtdb.firebaseio.com/Chats/$userId.json";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $userMessagesURL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true) ?? [];
}

// ✅ Function to Send Messages (Stored Under Each User ID)
function send_msg($userId, $msg, $name, $subject) { 
    global $URL;
    $msg_json = [
        "name" => htmlspecialchars($name),
        "subject" => htmlspecialchars($subject),
        "msg" => htmlspecialchars($msg),
        "time" => date('Y-m-d H:i:s')
    ];

    $firebaseURL = "https://deneme-4e80d-default-rtdb.firebaseio.com/Chats/$userId.json";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $firebaseURL,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($msg_json)
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ✅ Handle Name Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['startChat'])) {
    $_SESSION['userName'] = trim($_POST["userName"]);
    $_SESSION['userId'] = uniqid("user_"); // Unique user ID
    $_SESSION['firstMessageSent'] = false; // Track first message
    header("Location: chats.php");
    exit();
}

// ✅ Handle Message Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['usermsg'])) {
    $user_msg = trim($_POST['usermsg']);
    $user_name = $_SESSION['userName'] ?? "Guest";
    $userId = $_SESSION['userId'] ?? "";
    
    // ✅ If it's the first message, get the subject and set flag
    if (!isset($_SESSION['firstMessageSent']) || $_SESSION['firstMessageSent'] == false) {
        $subject = trim($_POST['subject']);
        $_SESSION['selectedSubject'] = $subject;
        $_SESSION['firstMessageSent'] = true;
    } else {
        $subject = $_SESSION['selectedSubject']; // Keep using the same subject
    }

    if (!empty($user_msg) && !empty($user_name) && !empty($userId)) {
        send_msg($userId, $user_msg, $user_name, $subject);
        header("Location: chats.php");
        exit();
    }
}

// ✅ Handle Reset Session (Clear Name Input)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["resetSession"])) {
    session_destroy();
    header("Location: chats.php");
    exit();
}

// ✅ Fetch Messages for the Current User
$userId = $_SESSION['userId'] ?? "";
$msg_res_json = get_messages($userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Chat</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .form-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .input-box {
            width: 100%;
            padding: 8px;
            font-size: 16px;
        }
        select {
            width: 100%;
            padding: 8px;
            font-size: 16px;
        }
        .chat-box {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 10px;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 8px;
        }
        .chat {
            list-style: none;
            padding: 0;
        }
        .chat li {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
            background: white;
        }
        .chat .self {
            text-align: right;
            background: #e0f7fa;
        }
        .chat .other {
            text-align: left;
            background: #d1c4e9;
        }
    </style>
</head>
<body>

<div class="menu">
    <div class="back">
        <i class="fa fa-chevron-left"></i> 
        <img src="https://i.imgur.com/DY6gND0.png" draggable="false"/>
    </div>
    <div class="name">Support</div>
    <div class="last"><?php echo date("H:i"); ?></div>
</div>

<!-- ✅ Name Input Form (Users must enter a name before chatting) -->
<?php if (!isset($_SESSION['userName'])): ?>
<div id="nameInput">
    <h3>Enter Your Name</h3>
    <form method="POST" class="form-container">
        <input type="text" name="userName" class="input-box" placeholder="Enter your name" required/>
        <button type="submit" name="startChat">Start Chat</button>
    </form>
</div>
<?php else: ?>

<!-- ✅ Chat Interface -->
<div id="chatContainer">
    <p><b>User:</b> <?php echo htmlspecialchars($_SESSION['userName']); ?></p>

    <div class="chat-box">
        <ol class="chat">
            <?php
            if (!empty($msg_res_json)) {
                foreach ($msg_res_json as $chat_msg) {
                    if (!isset($chat_msg['msg']) || empty(trim($chat_msg['msg']))) {
                        continue; // ✅ Skip empty messages
                    }

                    $name = htmlspecialchars($chat_msg['name'] ?? "Unknown");
                    $subject = htmlspecialchars($chat_msg['subject'] ?? "General");
                    $msg = htmlspecialchars($chat_msg['msg'] ?? "");
                    $time = htmlspecialchars($chat_msg['time'] ?? date("Y-m-d H:i:s"));
                    $from = ($name == $_SESSION['userName']) ? 'self' : 'other';

                    echo  '
                    <li class="'.$from.'">
                        <div class="msg">
                            <p><b>'.$name.' ('.$subject.')</b></p>
                            <p>'.$msg.'</p>
                            <time>'.$time.'</time>
                        </div>
                    </li>';
                }
            } else {
                echo "<p style='text-align:center; color:#888;'>No messages yet. Start chatting!</p>"; // ✅ Show friendly message
            }
            ?>
        </ol>
    </div>

    <!-- ✅ Message Input Form -->
    <form method="POST" class="form-container">
        <?php if (!isset($_SESSION['firstMessageSent']) || $_SESSION['firstMessageSent'] == false): ?>
            <select name="subject">
                <option value="Defected Product">Defected Product</option>
                <option value="Late Order">Late Order</option>
                <option value="Lost Product">Lost Product</option>
                <option value="Suggestion">Suggestion</option>
            </select>
        <?php endif; ?>

        <input name="usermsg" class="input-box" type="text" placeholder="Type your message here!" required/>
        <input type="submit" value="Send"/>
    </form>

    <!-- ✅ RESET SESSION BUTTON -->
    <form method="POST">
        <button type="submit" name="resetSession" style="background-color: orange; color: white; padding: 10px;">
            🔄 Reset Chat
        </button>
    </form>
</div>

<?php endif; ?>

</body>
</html>
