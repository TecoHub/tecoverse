<?php
	include('../conn.php');
	session_start();
	// PHPMailer source
	require_once '../lib/PHPMailer/src/PHPMailer.php';
	require_once '../lib/PHPMailer/src/SMTP.php';
	require_once '../lib/PHPMailer/src/Exception.php';

	// Get chatroomid from POST parameter
	if(isset($_POST['msg'])){
		$msg=$_POST['msg'];
		$msg_trimmed = str_replace("'", "\'", $msg);
		$id=$_POST['id'];
		mysqli_query($conn,"insert into `chat` (aachatroomid, aamessage, aauserid, aachat_date) values ('$id', '$msg_trimmed' , '".$_SESSION['id']."', NOW())") or die(mysqli_error($conn));

		// Get user name of the sender
		$sender_result = mysqli_query($conn, "SELECT aauname FROM aauser WHERE userid = '".$_SESSION['id']."'");
		$row = mysqli_fetch_array($sender_result);
		$uname = $row['aauname'];

		// Get chat room name 
		$chatroom_result = mysqli_query($conn, "SELECT aachat_name FROM aaaachatroom WHERE aachatroomid = '". $id ."'");
		$row = mysqli_fetch_array($chatroom_result);
		$chatroom_name = $row['chat_name'];

		// Retrieve all users in the chatroom
		$query = "SELECT aauserid FROM aachat_member WHERE aachatroomid='$id'";
		$users_result = mysqli_query($conn, $query);

		// Loop through the users and send email notifications to all those who are in the chatroom
		while ($row = mysqli_fetch_assoc($users_result)) {

			// Get the current date and time in Japanese format
			$datetime = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
			$date_time_japanese = $datetime->format('Y年m月d日 H時i分s秒');

			//Get message content and replace it with word 写真 if it is only photo sent
			$msg_no_tags = strip_tags($msg_trimmed);
			if (trim($msg_no_tags) == '') {
				$msg_no_tags = '【写真】';
			}

			// Get email addresses of all users joining the chatroom
			$userid = $row['userid'];
			$user_query = "SELECT aaemail FROM aauser WHERE aauserid='$userid'";
			$user_result = mysqli_query($conn, $user_query);
			$user_row = mysqli_fetch_assoc($user_result);

			$email = $user_row['email'];

			$mailUsername = substr($email, 0, strpos($email, '@'));

			// Create a new instance of PHPMailer
			$mail = new PHPMailer\PHPMailer\PHPMailer();
			$mail->CharSet = 'UTF-8';
			$mail->isSMTP();
			$mail->Host       = 'IP address';
			$mail->SMTPAuth   = true;
			$mail->Username   = $mailUsername;
			$mail->Password   = 'password';
			$mail->SMTPAutoTLS = false;
			$mail->SMTPSecure = '';
			$mail->Port       = 25;
			$mail->setFrom($email, 'CHAT');
			$mail->addAddress($email, $uname);
			$mail->Subject = '🔔【'.$chatroom_name.'】グループに新しいメッセージがあります。';
			$mail->Body = '✉️ メッセージの内容 ✉️' . "\r\n \r\n \r\n". $uname .":　" . $msg_no_tags . "\r\n \r\n \r\n" . "送信日時: " . $date_time_japanese . "\r\n" ."※他のもメッセージもあるので、チャットを開いてご確認ください。🐼";
			if ($mail->send()) {
				echo 'Email sent successfully to '.$email.'<br>';
			} else {
				echo 'Email could not be sent to '.$email.'<br>';
				echo 'Mailer Error: ' . $mail->ErrorInfo;
			}
		}
	}

?>