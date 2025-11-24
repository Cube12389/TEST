<?php

$name = $_POST["name"];
$key = $_POST["password"];

$servername = "localhost";
$username = "Urse";
$password = "lOb1sccJLEToPDDW";
$dbname = "urse";

function getToken() {
    $length = 32;
    $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $charsetLength = strlen($charset);
    $token = '';
    $randomBytes = random_bytes($length);
    for ($i = 0; $i < $length; $i++) {
        $byte = ord($randomBytes[$i]);
        $token .= $charset[$byte % $charsetLength];
    } return $token;
}

function getIp() {
    $ip = '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($ips as $i) {
            $i = trim($i);
            if (!filter_var($i, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) 
                continue;
            $ip = $i;
            break;
        }
    } if (!$ip && isset($_SERVER['HTTP_CLIENT_IP'])) 
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    if (!$ip) 
        $ip = $_SERVER['REMOTE_ADDR'];
    return $ip;
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("<script>alert('连接失败，请刷新: {$conn->connect_error}'); window.location.href ='index.php';</script>");
} else {
    $sql = "SELECT * FROM `urselogin` WHERE `Name`='{$name}'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if ($row["Name"] == $name) {
                if ($row["Password"] == $key) {
                    $UID = $row["UID"];
                    $Name = $row["Name"];
                    $Time = strtotime('+15 days');
                    $token = getToken($conn); 
                    $final_check_stmt = $conn->prepare("SELECT COUNT(*) FROM `token` WHERE `Token` = ?");
                    $final_check_stmt->bind_param("s", $token);
                    $final_check_stmt->execute();
                    $final_check_stmt->bind_result($token_count);
                    $final_check_stmt->fetch();
                    $final_check_stmt->close();
                    if ($token_count > 0) {
                        echo "405";
                        $conn->close();
                        exit;
                    } $check_stmt = $conn->prepare("SELECT `Token`, `Time` FROM `token` WHERE `UID` = ?");
                    $check_stmt->bind_param("i", $UID);
                    $check_stmt->execute();
                    $check_stmt->store_result();
                    $check_stmt->bind_result($existing_token, $existing_time);
                    $check_stmt->fetch();
                    $count = $check_stmt->num_rows;
                    $check_stmt->close();
                    $current_time = time();
                    if ($count > 0 && $existing_time > $current_time) {
                        $token = $existing_token;
                        $Time = $existing_time;
                    } else {
                        $token = getToken();
                        $Time = strtotime('+15 days');
                        $final_check_stmt = $conn->prepare("SELECT COUNT(*) FROM `token` WHERE `Token` = ?");
                        $final_check_stmt->bind_param("s", $token);
                        $final_check_stmt->execute();
                        $final_check_stmt->bind_result($token_count);
                        $final_check_stmt->fetch();
                        $final_check_stmt->close();
                        if ($token_count > 0) {
                            echo "405";
                            $conn->close();
                            exit;
                        } if ($count > 0) {
                            $update_stmt = $conn->prepare("UPDATE `token` SET `Token` = ?, `Time` = ? WHERE `UID` = ?");
                            $update_stmt->bind_param("sii", $token, $Time, $UID);
                            $update_stmt->execute();
                            $update_stmt->close();
                        } else {
                            $insert_stmt = $conn->prepare("INSERT INTO `token` (`UID`, `Name`, `Token`, `Time`) VALUES (?, ?, ?, ?)");
                            $insert_stmt->bind_param("issi", $UID, $Name, $token, $Time);
                            $insert_stmt->execute();
                            $insert_stmt->close();
                        }
                    }
                    
                    setcookie("token", $token, $Time, "/");
                    echo "200";
                } else {
                    echo "403";
                }
            }
        }
    } else {
        // 没有此用户
        echo "404";
    }
}
$conn->close();

?>