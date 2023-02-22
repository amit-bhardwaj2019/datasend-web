<?php 
namespace App\Helper;

use Psr\Container\ContainerInterface;
use App\TableGateways\UserGateway;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
class Common {
    private $ci;    
    private $userGateway;
    const INVALID_CREDENTIAL = "Invalid Token!";
    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
        $this->userGateway = new UserGateway($this->ci->get('db'));
    }

    public function createToken() 
    {
        $Token = md5(uniqid(microtime()));
        return $Token;
    }

    public function sendForgotPassEmail($name, $toEmail, $token) {        
        $Link = getenv('APP_URL') . '/reset-password?token=' . $token;
      
        $message = "<b>
        Please do not reply to this email.</b>
        <br/><br/>Dear " . $name . " ,<br><br>
        Thank you for your request.<br>To reset your password <a href=" . $Link . ">click here</a>.<br><br>Kind regards<br>The Data Room Team<br><br><br>The information contained in this email is intended for the use of the addressee and is confidential.If you are not the intended recipient, you must not use, disclose, read, forward, copy or retain the information. If you have received this email in error please delete it and notify the sender by return email or telephone. You assume all liability for any loss, damage or other consequences which may arise from opening this email, any attachments or using this service. Data Room service provided by <a href='http://www.datasend.co.uk/'>Data Send UK Ltd </a> All rights reserved.";
        $Subject = 'Password Reminder';
        //$toEmail	= 'keith@123789.org';
        $fromEmail  = getenv('MAIL_FROM_ADDRESS');
        $fromName   = getenv('MAIL_FROM_NAME');
        $mail = $this->sendMail($Subject, $toEmail, $message, $fromEmail, $fromName);
        if($mail) {
            return true;
        } else {
            return false;
        }
      }

      public function sendMail($subject, $toEmail, $msg, $fromEmail, $fromName)
      {
        //Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer();

        try {
            $mail->SMTPDebug = SMTP::DEBUG_OFF;                      
            $mail->isSMTP();                                           
            $mail->Host       = getenv('MAIL_HOST');                                 $mail->SMTPAuth   = true;                                   
            $mail->Username   = getenv('MAIL_USERNAME');                     
            $mail->Password   = getenv('MAIL_PASSWORD');                               
            // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
            $mail->Port       = getenv('MAIL_PORT');                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, 'Joe User'); 

            //Content
            $mail->isHTML(true);                                  
            $mail->Subject = $subject;
            $mail->Body    = $msg;
            $mail->AltBody = $msg;

            return $mail->send();
        } catch (\Exception $e) {
            return $mail->ErrorInfo;
        }
      }

      public function sendForgotPinEmail($name, $toEmail, $token) {        
        $Link = getenv('APP_URL') . '/reset-pin?token=' . $token;
      
        $message = "<b>Please do not reply to this email.</b><br/><br/>Dear " . $name . " ,<br><br>Thank you for your request.<br>To reset your pin <a href=" . $Link . ">click here</a>.<br><br>Kind regards<br>The Data Room Team<br><br><br>The information contained in this email is intended for the use of the addressee and is confidential.If you are not the intended recipient, you must not use, disclose, read, forward, copy or retain the information. If you have received this email in error please delete it and notify the sender by return email or telephone. You assume all liability for any loss, damage or other consequences which may arise from opening this email, any attachments or using this service. Data Room service provided by <a href='http://www.datasend.co.uk/'>Data Send UK Ltd </a> All rights reserved.";
        $Subject = 'Pin Reminder';        
        $fromEmail  = getenv('MAIL_FROM_ADDRESS');
        $fromName   = getenv('MAIL_FROM_NAME');
        $mail = $this->sendMail($Subject, $toEmail, $message, $fromEmail, $fromName);
        if($mail) {
            return true;
        } else {
            return false;
        }
      }

      public function SendSubUserLoginEmail($id) {        
        $record = $this->userGateway->find($id);
        $Name       = $record['name'];
        $Email      = $record['email'];
        $Token      = $record['token'];
        $userlevel  = $record['userlevel'];
        
        $GroupName = $this->userGateway->GetAssignGroup($id);
        var_export($GroupName);
    /*    $Link = getenv('APP_URL') . '/reset-password?token=' . $Token;
        if ($GroupName != '') {
            $message = "Dear " . ucwords($Name) . " <br> <br> We have created your account, your login details are:<br> Username :<a href='mailto:" . $Email . "'>" . $Email . "</a><br><br> First you must reset the default password to something of your own, using a minimum of 9 characters. <br><br> You can reset the password by clicking <a href=" . $Link . ">here</a> <br>If you have already reset your password, there is no need to do it again unless you have forgotten it or want to change it. <br> <br> Kind regards <br> The Data Room Team <br><br> The information contained in this email is intended for the use of the addressee and is confidential. If you are not the intended recipient, you must not use, disclose, read, forward, copy or retain the information. If you have received this email in error please delete it and notify the sender by return email or telephone. You assume all liability for any loss, damage or other consequences which may arise from opening this email, any attachments or using this service. Data Room service provided by <a href='" . $ServerUrl . "'>Data Send UK Ltd</a> &copy; All rights reserved.";
        } else {
            $message = "Dear " . ucwords($Name) . " <br><br><b>Please do not reply to this email.</b><br> <br> We have created your account, your login details are:<br> Username :<a href='mailto:" . $Email . "'>" . $Email . "</a><br><br> First you must reset the default password to something of your own, using a minimum of 9 characters. <br><br> You can reset the password by clicking <a href=" . $Link . ">here</a> <br>If you have already reset your password, there is no need to do it again unless you have forgotten it or want to change it. <br> <br> Kind regards <br> The Data Room Team <br><br> The information contained in this email is intended for the use of the addressee and is confidential. If you are not the intended recipient, you must not use, disclose, read, forward, copy or retain the information. If you have received this email in error please delete it and notify the sender by return email or telephone. You assume all liability for any loss, damage or other consequences which may arise from opening this email, any attachments or using this service. Data Room service provided by <a href='" . $ServerUrl . "'>Data Send UK Ltd</a> &copy; All rights reserved.";
        }
        if ($userlevel == 2) {
            $message = "<b>Please do not reply to this email.</b><br><br>Dear " . ucwords($Name) . " <br> <br> Your account has been created or recently modified. Here are your login details.<br><br> Username :" . $Email . "<br><br> <b>Password Reset Procedure:</b><br>First time users must change the initial password to something of their own with a minimum of 9 characters.<br><br> You can reset the password by clicking <a href=" . $Link . ">here</a>.<br>If you have already reset your password, there is no need to do it again unless you have forgotten it or want to change it.<br><br>Kind regards<br>The Data Room Team<br><br>The information contained in this email is intended for the use of the addressee and is confidential.If you are not the intended recipient, you must not use, disclose, read, forward, copy or retain the information. If you have received this email in error please delete it and notify the sender by return email or telephone. You assume all liability for any loss, damage or other consequences which may arise from opening this email, any attachments or using this service. Data Room service provided by <a href='http://www.datasend.co.uk/'>Data Send UK Ltd </a> &copy; All rights reserved.";
        }
        $Subject = 'New Account';
        $toEmail = $Email;
        if (trim($AdminEmail) == '') {
            $query = mysql_query("SELECT contactusemail FROM tbl_admin");
            $AdminEmail = mysql_result($query, 0, 'contactusemail');
        }
        $toEmail = $Email;
        $fromEmail = $AdminEmail;
        $fromName = 'Data Room';
        
        sendEmail($Subject, $toEmail, $message, $fromEmail, $fromName);
        */
    }
}