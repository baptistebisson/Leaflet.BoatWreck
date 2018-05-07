<?php
try 
{
	$db = new PDO('mysql:host=localhost;dbname=mydb;charset=utf8', 'root', 'root');
}
catch(PDOException $error)
{
	
}

/**
* Get all vessels
* @return Array Liste of vessels
*/
function getVessels()
{
	global $db;
	$vessel = '';
	
	$sql = 'SELECT * FROM TABLE 
	LIMIT 1000';
	$statement = $db->prepare($sql);
	$statement->execute();
	$vessels = formatResult($statement);
	
	return $vessels;
}

if (isset($_POST['getAll'])) {
	echo json_encode(getAllVessels());
}

/**
* Get all vessels from database
* @return Array Geojson vessels
*/
function getAllVessels()
{
	global $db;
	$vessel = '';
	
	$sql = 'SELECT * FROM TABLE ORDER BY name';
	$statement = $db->prepare($sql);
	$statement->execute();
	$vessels = formatResult($statement);
	
	return $vessels;
}

/**
* Get all countries
* @return Array All countries
*/
function getCountries()
{
	global $db;
	
	$sql = 'SELECT countries FROM countries ORDER BY countries';
	$statement = $db->query($sql);
	
	$data = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
	return $data;
}

/**
* Get all types of vessels
* @return Array All types
*/
function getTypes()
{
	global $db;
	
	$sql = 'SELECT DISTINCT type FROM type ORDER BY type';
	$statement = $db->query($sql);
	
	$data = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
	return $data;
}

/**
* Get total of vessels
* @return Array Total of vessels
*/
function getTotal()
{
	global $db;
	
	$sql = 'SELECT count(*) FROM TABLE';
	$statement = $db->query($sql);
	
	$data = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
	return $data;
}

/**
* Convert DMS to DEC
* @param float  $dms
* @param float  $longlat
* @param String $sector
*/
function DMStoDEC($dms, $longlat, $sector)
{
	$minus = false;	
	//Check if minus sign
	if (preg_match('/-/', $dms)) {
		//Remove it
		$dms = str_replace('-', '', $dms);
		$minus = true;
	}
	
	if ($longlat == 'latitude') {
		if (strpos($dms, '.')) {
			$explodeLatitude = explode('.', $dms);
			$latitude = str_pad($explodeLatitude[0], 4, '0', STR_PAD_LEFT);
			$degres = substr($latitude, 0, 2);
			$minutes = substr($latitude, 2, 3).'.'.$explodeLatitude[1];
		} else {
			$latitude = str_pad($dms, 4, '0', STR_PAD_LEFT);
			$degres = substr($latitude, 0, 2);
			$minutes = substr($latitude, 2, 3);
		}
		
		if ($sector == "S") {
			$nordsud = -1;
		} else {
			$nordsud = 1;
		}
		$lat = $nordsud * ($degres + ($minutes / 60));
		return $lat;
	}
	
	if ($longlat == 'longitude') {
		if (strpos($dms, '.')) {
			$explodeLongitude = explode('.', $dms);
			$longitude = str_pad($explodeLongitude[0], 5, '0', STR_PAD_LEFT);
			if ($minus == true) {
				$degres = -substr($longitude, 0, 3);
			} else {
				$degres = substr($longitude, 0, 3);
			}
			
			$minutes = substr($longitude, 3, 4).'.'.$explodeLongitude[1];
		} else {
			$longitude = str_pad($dms, 5, '0', STR_PAD_LEFT);
			$degres = substr($longitude, 0, 3);
			$minutes = substr($longitude, 3, 4);
		}
		
		
		if ($sector == "W") {
			$estouest = -1;
		} else {
			$estouest = 1;
		}
		$lng = $estouest * ($degres + ($minutes / 60));
		return $lng;
	}
}

/**
* Handle POST request from form search
* @var Array
*/
if (isset($_POST['nom'])) {
	$data = null;
	$textPavillions = $textTypes = null;
	if (isset($_POST['nom']) && isset($_POST['annee']) && isset($_POST['types']) && isset($_POST['pavillons'])) {
		$nom = $_POST['nom'];
		$annee = (int) $_POST['annee'];
		$types = json_decode($_POST['types']);
		$pavillons = json_decode($_POST['pavillons']);
		
		//Avoid empty select when select2 reset with button
		if (sizeof($types) !== 0 && $types[0]->text !== "") {
			foreach ($types as $key => $value) {
				$textTypes[] = $value->text;
			}
		}
		
		if (sizeof($pavillons) !== 0 && $pavillons[0]->text !== "") {
			foreach ($pavillons as $key => $value) {
				$textPavillions[] = $value->text;
			}
		}
		
		$data = findVessels($nom, $annee, $textTypes, $textPavillions);
	}
	echo json_encode($data);
}

/**
* Handle POST request from click marker
* @var Array
*/
if (isset($_POST['id'])) {
	$data = $return = null;
	$id = (integer) $_POST['id'];
	
	//Make sure we have and integer
	if (is_int($id)) {
		$sql = 'SELECT * FROM TABLE WHERE id = ?';
		$statement = $db->prepare($sql);
		$statement->execute(array($_POST['id']));
		$data = $statement->fetchAll();
		
		$longitude = DMStoDEC($data[0][3], 'longitude', $data[0][4]);
		$latitude = DMStoDEC($data[0][1], 'latitude', $data[0][2]);
		
		$return[] = $data[0][0];
		$return[] = DECtoDMS($latitude, 'lat');
		$return[] = DECtoDMS($longitude, 'lng');
	}
	
	echo json_encode($return);
}

/**
* Search vessel
* @param  String $nom       Vessel name
* @param  String $annee     Vessel year
* @param  Array  $types     Vessel type
* @param  Array  $pavillons Vessel pavillons
* @return Array             Vessels found
*/
function findVessels($nom, $annee, $types, $pavillons)
{
	global $db;
	$conditions = array();
	$values = null;
	
	$query = "SELECT * FROM TABLE";
	if (!empty($pavillons) && sizeof($pavillons) > 0 && $pavillons !== null) {
		$query .= " JOIN table t ON t.id_table = t.id_table";
		$data = '';
		foreach ($pavillons as $key => $value) {
			if ($key !== count($pavillons)-1) {
				$data .= "'" . $value . "'" . ', ';
			} else {
				$data .= "'" . $value . "'";
			}
		}
		$condition = "table IN (" . $data . ")";
		$conditions[] = $condition;
	}
	if (!empty($nom)) {
		$conditions[] = "name LIKE ?";
		$values[] = "%" . $nom . "%";
	}
	if (!empty($annee)) {
		$conditions[] = "year = ?";
		$values[] = $annee;
	}
	if (!empty($types) && sizeof($types) > 0 && $types !== null) {
		$data = '';
		foreach ($types as $key => $value) {
			if ($key !== count($types)-1) {
				$data .= "'" . $value . "'" . ', ';
			} else {
				$data .= "'" . $value . "'";
			}
		}
		$condition = "type IN (" . $data . ")";
		$conditions[] = $condition;
	}
	
	$sql = $query;
	if (count($conditions) > 0) {
		$sql .= " WHERE " . implode(' AND ', $conditions) . " LIMIT 2000";
	}
	
	
	$statement = $db->prepare($sql);
	if ($values !== null) {
		$statement->execute($values);
	} else {
		$statement->execute();
	}
	$vessels = formatResult($statement);
	
	return $vessels;
}

/**
* Get data of clicked vessel
* @param  Object $statement Query statement
* @return Array             Result
*/
function getVesselData($statement)
{
	$data = null;
	$data = $statement->fetchAll();
	return $data;
}

/**
* Format query result into GEOJSON
* @param  Object $statement Query statement
* @return String            Geojson data
*/
function formatResult($statement)
{
	$vessel = '';
	$first = true;
	while ($data = $statement->fetch()) {
		$data['nom_navire'] = str_replace('"', "'", $data['nom_navire']);
		if ($data['longitude'] !== "0" and $data['latitude'] !== "0") {
			$longitude = DMStoDEC($data['longitude'], 'longitude', $data['est_ouest']);
			$latitude = DMStoDEC($data['latitude'], 'latitude', $data['nord_sud']);
			
			$feature = array(
				'type' => 'Feature',
				'properties' => array(
					'data' => array(
						$data['nom_navire'],
						$data['id'],
					),
				),
				'geometry' => array(
					'type' => 'Point',
					'coordinates' => array(
						$longitude,$latitude
					)
				)
			);
			
			if ($first == true) {
				$first = false;
				//When first iteration, we don't want to add comma in the begining of JSON
				$vessel = json_encode($feature, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
			} else {
				//$vessel comma before json
				$vessel .= ','.json_encode($feature, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
			}
		} else {
			$feature = array(
				'type' => 'Feature',
				'properties' => array(
					'data' => array(
						$data['nom_navire'],
						$data['id'],
					),
				),
				'geometry' => array(
					'type' => 'Point',
					'coordinates' => array(
						0,0
					)
				)
			);
			if ($first == true) {
				$first = false;
				//When first iteration, we don't want to add comma in the begining of JSON
				$vessel = json_encode($feature, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
			} else {
				//$vessel comma before json
				$vessel .= ','.json_encode($feature, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
			}
		}
	}
	if (isset($feature)) {
		$featureCollection = array(
			'type' => 'FeatureCollection',
			'features' => $feature,
		);
		
		$vessels = '{"type": "FeatureCollection","features":['.$vessel.']}';
	} else {
		$vessels = null;
	}
	
	return $vessels;
}

/**
* Convert DEC to DMS
* @param float $dec
*/
function DECtoDMS($dec, $latOrLong)
{
	if (strpos($dec, '.') !== false) {
		$vars = explode(".", $dec);
		$deg = $vars[0];
		if ($latOrLong == 'lng') {
			if (substr_count($deg, '-')) {
				$letter = "W";
			} else {
				$letter = "E";
			}
			$deg = str_replace('-', '', $deg);
			$deg = str_pad($deg, 3, '0', STR_PAD_LEFT);
		} else {
			if (substr_count($deg, '-')) {
				$letter = "S";
			} else {
				$letter = "N";
			}
			$deg = str_replace('-', '', $deg);
			$deg = str_pad($deg, 2, '0', STR_PAD_LEFT);
		}
		//$deg = str_replace('-', '', $deg);
		$tempma = "0.".$vars[1];
		
		$tempma = $tempma * 3600;
		$min = floor($tempma / 60);
		$sec = $tempma - ($min*60);
		
		return $deg. '&deg; '. str_pad($min, 2, '0', STR_PAD_LEFT) .'&apos; '. str_pad(round($sec, 0), 2, '0', STR_PAD_LEFT). '&quot; '. $letter;
	} else {
		if (strpos($dec, '-')) {
			if ($latOrLong == 'lat') {
				$letter = "S";
			} else {
				$letter = "W";
			}
		} else {
			if ($latOrLong == 'lat') {
				$letter = "N";
			} else {
				$letter = "E";
			}
		}
		//We may have longitude like 90 without dot so we can't convert
		return $dec. '&deg; 00&apos; 00.00&quot; '. $letter;
	}
}
