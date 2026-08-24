<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
$formId = $_POST['form_id'] ?? '';

 if ($formId === 'visszahivas') {

    if(isset($_POST['email']) || !empty($_POST['email'])){
        $email = $_POST['email'];
        $good = '
<tr>
	<td class="leiratkozas">
		Hamarosan felvesszük Önnel a kapcsolatot.
	</td>
</tr>
';

	$msg="
Az alábbi ajánlat kérés érkezett a weboldalról:

Név: {$_POST["name"]} 

Telefonszám: {$_POST["phone"]}

E-mail: {$_POST["email"]}

Univer-Car csapata
";

	$subject = "Weboldal visszahívás kérés"; 
	
	ini_set("sendmail_from", $config["email"]["sender"]);

require("class.phpmailer.php");
$email = 'ricsi@luxradio.hu';        //Címzett
$mailer = new PHPMailer();
$mailer -> CharSet = "UTF-8";
$mailer->IsSMTP();
$mailer->Host = 'ssl://smtp.gmail.com:465';
$mailer->SMTPAuth = TRUE;
$mailer->Username = 'univercar1993@gmail.com';  // $mailer->From  ugyanezt kell majd megadd
$mailer->Password = 'asd';  // G-Mailes jelszó
$mailer->From = 'website@univercar.hu';  // G-Mailes e-mail
$mailer->FromName = 'Univer-Car érdeklődés'; // Feladó neve(Te neved, vagy szolgáltatásod neve)
$mailer->Body = $msg;
$mailer->Subject = $subject; // Téma/Tárgy
$mailer -> CharSet = "UTF-8";
$mailer->AddAddress($email); 
$mailer->Send();
//hvhjrpifxsvmtmzy
//EeFf11C606
    }
}

elseif ($formId === 'szerviz') {
if(isset($_POST['email']) || !empty($_POST['email'])){
        $email = $_POST['email'];
        $good2 = '
<tr>
	<td class="t10 b20">
	<table class="table-100-center">
		<tr>
			<td class="leiratkozas">
			Hamarosan felvesszük Önnel a kapcsolatot.
			</td>
		</tr>
	</table>
	</td>
</tr>
';

	$msg="
<html>
<body>
<table width='100%' align='center'>
<span style='font-tize:18px; color:#444444;'><b>
Az alábbi online szerviz Időpont egyeztetési igény érkezett:
<br><br>
Márka: {$_POST["marka"]}
<br><br>
Típus: {$_POST["tipus"]}
<br><br>
Évjárat: {$_POST["evjarat"]}
<br><br>
Rendszám: {$_POST["rendszam"]}
<br><br>
Szerviz-igény: {$_POST["igeny"]}
<br><br>
Név: {$_POST["name"]} 
<br><br>
E-mail: {$_POST["email"]}
<br><br>
Telefonszám: {$_POST["phone"]}
</b>
</table>
</body>
</html>
";

	$subject = "UNIVER-CAR.HU - Online időpont foglalási megkeresés"; 
	
	ini_set("sendmail_from", $config["email"]["sender"]);

require("class.phpmailer.php");
$email = 'ricsi@luxradio.hu';        //Címzett
$mailer = new PHPMailer();
$mailer -> CharSet = "UTF-8";
$mailer->IsSMTP();
$mailer->Host = 'ssl://smtp.gmail.com:465';
$mailer->SMTPAuth = TRUE;
$mailer->Username = 'univercar1993@gmail.com';  // $mailer->From  ugyanezt kell majd megadd
$mailer->Password = 'asd';  // G-Mailes jelszó
$mailer->From = 'website@univercar.hu';  // G-Mailes e-mail
$mailer->FromName = 'Univer-Car.hu'; // Feladó neve(Te neved, vagy szolgáltatásod neve)
$mailer->IsHTML(true);  
$mailer->Body = $msg;
$mailer->Subject = $subject; // Téma/Tárgy
$mailer -> CharSet = "UTF-8";
$mailer->AddAddress($email); 
$mailer->Send();
//hvhjrpifxsvmtmzy
//EeFf11C606
    } 
}
}
?>
