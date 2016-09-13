<?php
$get_wikis = isset($_GET['wikis']) && is_array($_GET['wikis']) ? array_values($_GET['wikis']) :
	array('starcraft', 'starcraft2', 'dota2', 'hearthstone', 'heroes', 'smash', 'counterstrike', 'overwatch', 'commons', 'warcraft', 'fighters', 'rocketleague');
$clean_wikis_list = array();
foreach ($get_wikis as $wiki) {
	if (preg_match('/^(starcraft|starcraft2|dota2|hearthstone|heroes|smash|counterstrike|overwatch|commons|warcraft|fighters|rocketleague)$/', $wiki)) {
		$clean_wikis_list[$wiki] = $wiki;
	}
}
$view = isset($_GET['view']) ? $_GET['view'] : 0;
$view = intval($view);
$compare_to_record = isset($_GET['compare_to_record']) ? $_GET['compare_to_record'] : 0;
$compare_to_record = intval($compare_to_record);
if (isset($_GET['store_in_db'])) {
	$store_in_db = $_GET['store_in_db'];
} else if (isset($argv) && isset($argv[1])) {
	$store_in_db = $argv[1];
} else {
	$store_in_db = false;
}

include('connection.php');
$connection = Connection::getConnection();

$timestamps = array();
$stmt = $connection->prepare('SELECT id, timestamp FROM activeusers ORDER BY timestamp desc LIMIT 60');
$stmt->execute();
$result = $stmt->fetchAll();
if (count($result)) {
	foreach ($result as $key => $data) {
		$timestamps[$data['id']] = $data['timestamp'];
	}
}
$stmt = $connection->prepare('SELECT id, timestamp FROM activeusers WHERE id = :view OR id = :compare_to_record ORDER BY timestamp desc');
$stmt->bindValue(':view', $view);
$stmt->bindValue(':compare_to_record', $compare_to_record);
$stmt->execute();
$result = $stmt->fetchAll();
if (count($result)) {
	foreach ($result as $key => $data) {
		$timestamps[$data['id']] = $data['timestamp'];
	}
}

?>
<!doctype html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Liquipedia Active users</title>
	<link rel="stylesheet" type="text/css" href="style.css">
	<link rel="stylesheet" type="text/css" href="flag-icon.min.css">
	<meta name="viewport" content="initial-scale=1, width=device-width, user-scalable=yes">
	<script type="text/javascript">
		function heatmap() {
			var elements = document.querySelectorAll('tr.total-row,tr.user-row');
			Array.prototype.forEach.call(elements, function(el, i){
				var wikiCells = el.querySelectorAll('td.wiki');
				var editCountTotal = 0, editCountValues = [];
				Array.prototype.forEach.call(wikiCells, function(wikiCell, j) {
					var value = wikiCell.textContent.replace(/[^0-9,]/g, '');
					value = value === '' ? 0 : parseInt(value);
					editCountValues.push(value);
					editCountTotal += value;
				});
				for (var k = 0; k < wikiCells.length; k++) {
					var ratio = editCountTotal != 0 ? (editCountValues[k] / editCountTotal) : 0;
					var color = Math.floor((7*(1 - ratio)/8 + 1/16) * 100);
					wikiCells[k].style.backgroundColor = 'hsl(120,100%,' + color + '%)';
					if (color < 40) {
						var anchors = wikiCells[k].querySelectorAll('a');
						if (anchors.length) {
							anchors[0].style.color = '#ffffff';
						} else {
							wikiCells[k].style.color = '#ffffff';
						}
						var abbrs = wikiCells[k].querySelectorAll('abbr');
						if (abbrs.length) {
							abbrs[0].style.color = '#ffffff';
						}
					}
				}
			});
		}
		function ready() {
			var buttons = document.querySelectorAll('#heatmap-button')
			buttons[0].addEventListener('click', heatmap);
		}
		if (document.readyState != 'loading'){
			ready();
		} else {
			document.addEventListener('DOMContentLoaded', ready);
		}
	</script>
</head>
<body>
	<form action="index.php" type="GET">
		<div id="options">
			<div id="wikis">
				<span><b>Select wikis: </b></span>
				<span class="wiki-buttons">
					<input type="checkbox" <?php echo isset($clean_wikis_list['starcraft']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="starcraft" id="starcraft"/>
					<label class="wiki-button starcraft" for="starcraft" title="starcraft"></label>
					<input type="checkbox" <?php echo isset($clean_wikis_list['starcraft2']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="starcraft2" id="starcraft2"/>
					<label class="wiki-button starcraft2" for="starcraft2" title="starcraft2"></label>
					<input type="checkbox" <?php echo isset($clean_wikis_list['dota2']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="dota2" id="dota2"/>
					<label class="wiki-button dota2" for="dota2" title="dota2"></label>
					<input type="checkbox" <?php echo isset($clean_wikis_list['hearthstone']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="hearthstone" id="hearthstone"/>
					<label class="wiki-button hearthstone" for="hearthstone" title="hearthstone"></label>
					<input type="checkbox" <?php echo isset($clean_wikis_list['heroes']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="heroes" id="heroes"/>
					<label class="wiki-button heroes" for="heroes" title="heroes"></label>
					<input type="checkbox" <?php echo isset($clean_wikis_list['smash']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="smash" id="smash"/>
					<label class="wiki-button smash" for="smash" title="smash"></label>
					<input type="checkbox" <?php echo isset($clean_wikis_list['counterstrike']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="counterstrike" id="counterstrike"/>
					<label class="wiki-button counterstrike" for="counterstrike" title="counterstrike"></label>
					<input type="checkbox" <?php echo isset($clean_wikis_list['overwatch']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="overwatch" id="overwatch"/>
					<label class="wiki-button overwatch" for="overwatch" title="overwatch"></label>
					<input type="checkbox" <?php echo isset($clean_wikis_list['commons']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="commons" id="commons"/>
					<label class="wiki-button commons" for="commons" title="commons"></label>
					<input type="checkbox" <?php echo isset($clean_wikis_list['warcraft']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="warcraft" id="warcraft"/>
					<label class="wiki-button warcraft" for="warcraft" title="warcraft"></label>
					<input type="checkbox" <?php echo isset($clean_wikis_list['fighters']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="fighters" id="fighters"/>
					<label class="wiki-button fighters" for="fighters" title="fighters"></label>
					<input type="checkbox" <?php echo isset($clean_wikis_list['rocketleague']) ? 'checked="checked" ' : ''; ?>name="wikis[]" value="rocketleague" id="rocketleague"/>
					<label class="wiki-button rocketleague" for="rocketleague" title="rocketleague"></label>
				</span>
			</div>
			<div id="record-options">
				<span class="from">
					<label for="view"><b>View stats from: </b></label>
					<select id="view" name="view">
						<option value="0">Now</option>
						<?php foreach($timestamps as $id => $timestamp) { echo '<option value="' . $id . '"' . ($id == $view ? 'selected' : ''). '>' . $timestamp . '</option>'; }?>
					</select>
				</span>
				<span class="compare-to">
					<label for="compare_to_record"><b>Compare to record from: </b></label>
					<select id="compare_to_record" name="compare_to_record">
						<option value="0"></option>
						<?php foreach($timestamps as $id => $timestamp) { echo '<option value="' . $id . '"' . ($id == $compare_to_record ? 'selected' : ''). '>' . $timestamp . '</option>'; }?>
					</select>
				</span>
			</div>
		</div>
		<input type="submit" />
	</form>
	<input type="button" value="Heatmap" id="heatmap-button" />
	<hr>
	<p>This is a list of users who had some kind of activity (edits, file uploads, moves, deletions...) within the last 30 days. Numbers represent actions in the last 30 days.</p>

<?php
/*debut du cache*/
$cache = 'cache/index_compare_' . $view . '-' . $compare_to_record . '_' . implode('_', $clean_wikis_list) . '.html';
$expire = time() - 3600 ; // valable une heure
 
if(!$store_in_db && file_exists($cache) && filemtime($cache) > $expire)
{
	echo '<b>Cached: ' . date('r (T)', filemtime($cache)) . '</b><br/>';
	echo 'Current server time: ' . date('r (T)') . ' - The cache expires after 60 minutes.<br/><br/>';
	readfile($cache);
}
else
{
	echo '<b>Generated now: ' . date('r (T)') . '</b><br/><br/>';

	ob_start(); // ouverture du tampon

	require('table.php');

	$page = ob_get_contents(); // copie du contenu du tampon dans une chaîne
	ob_end_clean(); // effacement du contenu du tampon et arrêt de son fonctionnement
	
	file_put_contents($cache, $page) ; // on écrit la chaîne précédemment récupérée ($page) dans un fichier ($cache) 
	echo $page ; // on affiche notre page :D 
}
?>
</body>