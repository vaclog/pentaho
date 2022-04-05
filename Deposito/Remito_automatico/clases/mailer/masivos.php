<?
    
	include_once("class.phpmailer.php");
	$mail = new PHPMailer();
	//$mail->IsSMTP();
	//$mail->Host="mail.australvirtual.com.ar";
	
	
	$body = $mail->getFile('mailing.html');
	$body=eregi_replace("[\]",'',$body);
	$mail->From ="info@australvirtual.com.ar";
	$mail->FromName="Soporte Tecnico";
	$mail->Subject="Austral Virtual: Foro, Invitacion a participar.";
	$mail->MsgHTML($body);
	
	
	if(!@mysql_connect('localhost','root','nolase'))
	{
	    echo 'No conecto';
	    exit;
	}
	
	if(!@mysql_select_db('austral_2008'))
	{
	    echo 'No Seleccion DB';
	    exit;
	}
	
	if(!$RS=@mysql_query("select NOMBRE,CORREO FROM DAT_PILOTOS"))
	{
	    echo 'No Query';
	    exit;
	}
	
	while($R=@mysql_fetch_array($RS))
	{
	    $mail->AddAddress($R[1],$R[0]);
	    if(!$mail->Send())
	    {
		echo 'No enviado: '.$R[2];
	    }
	    
	    $mail->ClearAddresses();
	}
	
	if(!@mysql_free_result($RS)){}
	if(!@mysql_close()){}
	
	echo "Fin";
?>