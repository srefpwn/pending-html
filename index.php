<!DOCTYPE html>
<html lang="hu">
	<head>
		<title>UNIVER-CAR - Szervíz</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<meta name="robots" content="all" />
			<meta name="Description" content="" />
			<meta name="keywords" content="">
			<link rel="icon" href="favicon.ico" type="image/x-icon" />
			<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
			<meta property="og:title" content="" />
			<meta property="og:description" content="" />
			<meta property="og:image" content="" />
			<link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml" />
			<link href=”” rel="Publisher" />
			<meta name="copyright" content="&copy; " />
			<link href="css/css.css" rel="stylesheet" type="text/css" />
			<script>
			  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
	
			  ga('create', 'UA-62114156-1', 'auto');
			  ga('send', 'pageview');
			</script>	
			<link href="css/wechselteaser.css" rel="stylesheet" type="text/css" />
			<script type="text/javascript" src="scripts/jquery.min.js"></script>
			<script type="text/javascript" src="scripts/jquery.timers-1.1.2.js"></script>
			<script type="text/javascript" src="scripts/wechselteaser.js"></script>
			<style type="text/css">
			



		</style>
	</head>
<body>
<?php include '../visszahivas.php'; ?>
<?php include '../include/top.php'; ?>
				<tr>
			<td class="type-img2">
			<div id="kapcsolat">
			<table class="table-1030-center">
				<tr>
					<td class="w800 center-box h100 textv-bottom">
					<table class="table-border">
						<tr>
							<td class="center-text-top pb10"><span class="text4" style="color: #666666;">Szerviz</span>
							</td>
						</tr>
					</table>
					</td>
				</tr>
			</table>
			</div>
			</td>
		</tr>
	</table>
	<table class="table-100-center">
		<tr>
			<td>
			<table class="table-100-center">
				<tr>
					<td class="text-center bgcolor-center">
					<table class="table-1050-center">
					<tr>
											<td class="input-box-radio text-center">
											<table border="0" cellpadding="0" cellspacing="0" class="text-center">
												<tr>
													<td class="text-center w200">
													<img src="/img/szerviz/honda-logo.png" height="100" title="Honda szervizelése">
													</td>
													<td class="text-center w200">
													<img src="/img/szerviz/isuzu-logo.png" height="100" title="Isuzu szervizelése">
													</td>
													<td class="text-center w200">
													<img src="/img/szerviz/kia-logo.png" height="100" title="Kia szervizelése">
													</td>
													<td class="text-center w200">
													<img src="/img/szerviz/victory-logo.png" height="100" title="Victory Elektromos járművek szervizelése">
													</td>
												</tr>
											</table>
											</td>
										</tr>
						<tr>
							<td class="letterbox">
							<table border="0" cellpadding="0" cellspacing="0" align="center">
							<?=$good2?>
								<tr>
									<td align="center" class="b20">
									<table border="0" cellpadding="0" cellspacing="0">
										<tr>
											<td class="t20 b20">
											<span class="szerviz-top">Szervizidőpont egyeztetés</span></td>
										</tr>
									</table>
									<form action="index.php" method="post">
									<input type="hidden" name="form_id" value="szerviz">
									<table border="0" cellpadding="0" cellspacing="0">
										<tr>
											<td class="input-box2 bottom-info-text b10 szerviz-text" width="1010">
											<b>Telefonos bejelentkezéshez kérjük keresse kollégáinkat a (46) 502 980-as telefonszámon.</b></td>
										</tr>
										<tr>
											<td class="input-box2 bottom-info-text b20 szerviz-text" width="1010">
											Online szeretne szervizidőpontot egyeztetni? Kérjük, pontosan töltse ki az alábbi adatlapot, hogy érdeklődését a lehető legrövidebb időn belül feldolgozhassuk.<br><br>* Márkaszervizünk Honda, Isuzu, Kia és Victory szervizelését vállalja.</td>
										</tr>
										<tr>
											<td class="text-right" width="1010">
											<table border="0" cellpadding="0" cellspacing="0" class="text-right">
												<tr>
													<td class="letter-box-szerviz-ful lp-10 rp-10 letter-box-szerviz-radius-left-top letter-box-szerviz-radius-right-top">Kötelező <span style="color:red;">*</span></td>
												</tr>
											</table>
											</td>
										</tr>
									</table>
									<table border="0" cellpadding="0" cellspacing="0">
										<tr>
											<td class="lp-10 rp-5 textv-top t10 w491 letter-box-szerviz letter-box-szerviz-bg b20 letter-box-szerviz-radius-left">
											<table border="0" cellpadding="0" cellspacing="0">
												<tr>
													<td class="input-box szerviz-text h30">
													<b>Jármű adatok</b></td>
												</tr>
												<tr>
													<td class="input-box w200 szerviz-text">
													Márka <span style="color:red;">*</span></td>
													<td class="input-box-szerviz">
													<select name="marka" id="marka" required>
 														<option value="--">- Kérjük válasszon -</option>
 														<option value="Honda">Honda</option>
 														<option value="Isuzu">Isuzu</option>
 														<option value="Kia">Kia</option>
 														<option value="Victory">Victory</option>
													</select>
													</td>
												</tr>
												<tr>
													<td class="input-box w200 szerviz-text">
													Típus <span style="color:red;">*</span></td>
													<td class="input-box-szerviz">
													<input type="tipus" name="tipus" id="tipus" required></td>
												</tr>
												<tr>
													<td class="input-box w200 szerviz-text">
													Évjárat <span style="color:red;">*</span></td>
													<td class="input-box-szerviz">
													<select name="evjarat" id="evjarat" required>
 														<option value="--">- Kérjük válasszon -</option>
 														<option value="2024">2024</option>
 														<option value="2023">2023</option>
 														<option value="2022">2022</option>
 														<option value="2021">2021</option>
 														<option value="2020">2020</option>
 														<option value="2019">2019</option>
 														<option value="2018">2018</option>
 														<option value="2017">2017</option>
 														<option value="2016">2016</option>
 														<option value="2015">2015</option>
 														<option value="2014">2014</option>
 														<option value="2013">2013</option>
 														<option value="2012">2012</option>
 														<option value="2011">2011</option>
 														<option value="2010">2010</option>
 														<option value="2009">2009</option>
 														<option value="2008">2008</option>
 														<option value="2007">2007</option>
 														<option value="2006">2006</option>
 														<option value="2005">2005</option>
 														<option value="2004">2004</option>
													</select></td>
												</tr>
												<tr>
													<td class="input-box w200 szerviz-text">
													Rendszám <span style="color:red;">*</span></td>
													<td class="input-box-szerviz">
													<input type="name" name="rendszam" id="rendszam" required></td>
												</tr>
												<tr>
													<td class="input-box-szerviz w200 szerviz-text h30">
													<b>Szerviz-igény</b></td>
												</tr>
												<tr>
													<td class="input-box w200 szerviz-text">
													Szerviz-igény <span style="color:red;">*</span></td>
													<td class="input-box-szerviz">
													<select name="igeny" id="igeny" required>
 														<option value="--">- Kérjük válasszon -</option>
 														<option value="Karbantartás">Karbantartás</option>
 														<option value="Műszaki vizsga">Műszaki vizsga</option>
 														<option value="Kerékcsere">Kerékcsere</option>
 														<option value="Olajcsere">Olajcsere</option>
 														<option value="Egyéb">Egyéb</option>
													</select></td>
												</tr>
											</table>
											</td>
											<td class="lp-10 rp-10 textv-top t10 w500 letter-box-szerviz">
											<table border="0" cellpadding="0" cellspacing="0">
												<tr>
													<td class="input-box w200 szerviz-text h30">
													<b>Személyes adatok</b></td>
												</tr>
												<tr>
													<td class="input-box w200 szerviz-text">
													Név <span style="color:red;">*</span></td>
													<td class="input-box-szerviz">
													<input type="name" name="name" id="name" required></td>
												</tr>
												<tr>
													<td class="input-box w200 szerviz-text">
													E-mail cím</td>
													<td class="input-box-szerviz">
													<input type="email" name="email" id="email"></td>
												</tr>
												<tr>
													<td class="input-box w200 szerviz-text">
													Telefonszám <span style="color:red;">*</span></td>
													<td class="input-box-szerviz">
													<input type="name" name="phone" id="phone" required></td>
												</tr>
											</table>
											</td>
										</tr>										
									</table>
									<table class="table-100-center">
										<tr>
											<td class="input-box3 w600 szerviz-text text-left lp-10 letter-box-szerviz2 letter-box-szerviz-radius-left-bottom">
											Hozzájárulok az adataim használatához az <a href="https://www.univercar.hu/adatkezeles.pdf" target="_blank" class="input-box-szerviz">Adatvédelmi nyilatkozat</a>-ban foglaltak szerint.<span style="color:red;">*</span></td>
											<td class="input-box-szerviz2 text-left letter-box-szerviz2"><input type="checkbox" name="adatvedelem" id="adatvedelem" required></td>
											<td class="button-box t20 b20 rp-10 letter-box-szerviz2 text-right letter-box-szerviz-radius-right-bottom">
											<input type="submit" value="Küldés" class="button">&nbsp;</td>
										</tr>
									</table>
									</form>
									</td>
								</tr>
							</table>
							</td>
						</tr>
					</table>
					</td>
				</tr>
			</table>
			</td>
		</tr>
	</table>
<?php include '../include/bottom.php'; ?>
</body>
</html>
