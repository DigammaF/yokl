<html>
<head>
	<title>PHP</title>
	<meta charset="UTF-8">
</head>
<body>

	<h1>Créer un personnage</h1>
	<form method="post" action="register.php">
		<label>Nom: </label>
		<input type="text" name="name">
		</br>
		<label>Force et vitesse: </label>
		<input type="text" name="strength">
		<input type="text" name="speed">
		</br><label>La somme des deux ne peux excéder 10 !</label>
		</br>
		<label>Mot de passe: </label>
		<input type="text" name="password">
		</br><input type="submit" value="Commencer">
	</form>

	<h1>Charger un personnage</h1>
	<form method="post" action="load.php">
		<label>Nom: </label>
		<input type="text" name="name">
		</br>
		<label>Mot de passe: </label>
		<input type="text" name="password">
		</br><input type="submit" value="Continuer">
	</form>

</body>
</html>