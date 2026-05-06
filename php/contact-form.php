<!-- /*

Template: Profyl - Personal Vcard Resume HTML Template
Author: iqonicthemes.in
Version: 1.0
Design and Developed by: iqonicthemes.in

NOTE: This is main stylesheet of template, This file contains the styling for the actual Template. Please do not change anything here! write in a custom.css file if required!

*/ -->

<?php
if(isset($_POST["action"])) {
  $name = $_POST['name'];                 // Sender's name
  $email = $_POST['email'];     // Sender's email address
  $phone  = $_POST['phone'];     // Sender's email address
  $message = $_POST['message'];    // Sender's message
  $from = '[Portfolio] Interested in a Developer';    
  $to = 'telizabethm@gmail.com';     // Recipient's email address
  $subject = 'Message from Contact Demo ';

 $body ="From: $name \n E-Mail: $email \n Phone : $phone \n Message : $message"  ;
	
	// init error message 
	$errmsg='';
  // Check if name has been entered
  if (!$_POST['name']) {
   $errmsg = 'Would you kindly enter your name';
  }

  
  // Check if email has been entered and is valid
  if (!$_POST['email'] || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
   $errmsg = 'Please gently enter a valid email address';
  }
  
  //Check if message has been entered
  if (!$_POST['message']) {
   $errmsg = 'Please your very important message';
  }
 
	$result='';
  // If there are no errors, send the email
  if (!$errmsg) {
		if (mail ($to, $subject, $body, $from)) {
			$result='<div class="alert alert-success">I am so excited that you Contacted me for our next steps. Thank you for contacting me and thank you again for your interest. Your message has been successfully sent and will arrive in my email shortly. I will contact you very soon please allow up 24 - 48 hours!</div>'; 
		} 
		else {
		  $result='<div class="alert alert-danger">I am so so, Sorry there was an error sending your message. Please gently type your message and or try again later.</div>';
		}
	}
	else{
		$result='<div class="alert alert-danger">'.$errmsg.'</div>';
	}
	echo $result;
 }
?>
