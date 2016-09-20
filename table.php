<?php
// *** SOME FUNCTIONS ***
function objectToArray($obj) {
	if (is_object($obj))
		$obj = (array) $obj;
	if (is_array($obj)) {
		$new = array();
		foreach($obj as $key => $val) {
			$new[$key] = objectToArray($val);
		}
	} else
		$new = $obj;
	return $new;
}

function getParams($store_in_db) {
	$params = array();

	$params['debug'] = isset($_GET['debug']) ? $_GET['debug'] : false;
	$params['show_flags'] = isset($_GET['flags']) ? $_GET['flags'] : false;
	if (!isset($store_in_db)) { 
		$params['store_in_db'] = isset($_GET['store_in_db']) ? $_GET['store_in_db'] : false;
	} else {
		$params['store_in_db'] = $store_in_db;
	}
	$params['view'] = isset($_GET['view']) ? $_GET['view'] : 0;
	$params['view'] = intval($params['view']);
	$params['compare_to_record'] = isset($_GET['compare_to_record']) ? $_GET['compare_to_record'] : 0;
	$params['compare_to_record'] = intval($params['compare_to_record']);

	return $params;
}

function getFlags($connection, $show_flags) {
	$flags = array();

	if ($show_flags) {
		$stmt = $connection->prepare('SELECT id, user_name, flag FROM lp_users ORDER BY id');
		$stmt->execute();
		$result = $stmt->fetchAll();

		if (count($result)) {
			foreach ($result as $key => $data) {
				$flags[$data['user_name']] = $data['flag'];
			}
		}
	}

	return $flags;
}

function getRecord($connection, $id) {
	$record = array();

	if ($id > 0 && is_int($id))
	{
		$stmt = $connection->prepare('SELECT id, record FROM activeusers WHERE ID = :id');
		$stmt->bindValue(':id', $id);
		$stmt->execute();
		$data = $stmt->fetch();

		if (isset($data['record'])) {
			$record = json_decode($data['record']);
		}
	}

	return $record;
}

function getCache($connection, $wikis) {
	$record_cache = array();

	$stmt = $connection->prepare('SELECT wiki, record, timestamp FROM activeusers_cache WHERE wiki IN (:wikilist)');
	$stmt->bindValue(':wikilist', implode(',', array_keys($wikis)));
	$stmt->execute();
	$result = $stmt->fetchAll();

	if (count($result)) {
		foreach ($result as $key => $data) {
			$record_cache[$data['wiki']] = array(
				'record' => json_decode($data['record']),
				'timestamp' => $data['timestamp']
			);
		}
	}

	return $record_cache;
}

function getContributions($curl, $postdata, &$wikiStats) {
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	$data = unserialize(curl_exec($curl));

	$users = $data['query']['allusers'];
	foreach ($users as $user) {
		$wikiStats[$user['name']] = (object) array(
			'count' => $user['recenteditcount'],
			'groups' => $user['groups']
		);
	}

	return isset($data['continue']) ? $data['continue'] : 0;
}

function mergeWikiStats(&$stats, $wiki, $wikiStats) {
	foreach ($wikiStats as $username => $userstats) {
		if ( !isset($stats[$username]) ) {
			createStats($stats, $username);
		}
		$stats[$username]['count_' . $wiki] = $userstats->count;
		$stats[$username]['groups_' . $wiki] = $userstats->groups;
	}
}

function createStats(&$stats, $username) {
	global $wikis;

	$stats[$username] = array();
	foreach (array_keys($wikis) as $wiki) {
		$stats[$username]['count_' . $wiki] = 0;
		$stats[$username]['groups_' . $wiki] = array();
	}
}

function completeStats(&$stats) {
	global $wikis;

	foreach (array_keys($stats) as $username) {
		foreach (array_keys($wikis) as $wiki) {
			if ( !isset($stats[$username]['count_' . $wiki]) ) {
				$stats[$username]['count_' . $wiki] = 0;
				$stats[$username]['groups_' . $wiki] = array();
			}
		}
	}
}

function diffSpanHtml($count_diff) {
	$class = array('diff');
	$sign = '';
	if ($count_diff > 0) {
		$class[] = 'positive';
		$sign = '+';
	} else if ($count_diff < 0) {
		$class[] = 'negative';
		$sign = '';
	}
	echo '&nbsp;<span class="' . implode($class, ' ') . '">(' . $sign . $count_diff . ')</span>';
}

// *** THE MAIN STUFF ***

// The variables $loginName (wiki user name) and $loginPass (wiki password) must be set in login.php
require_once('connection.php');
$connection = Connection::getConnection();
$cookieFile = 'cookies.tmp';
// The value of $store_in_db is set in 'index.php'
$params = getParams($store_in_db);

$flags = getFlags($connection, $params['show_flags']);
$previous_record = getRecord($connection, $params['compare_to_record']);

$default_wikis = array('starcraft', 'starcraft2', 'dota2', 'hearthstone', 'heroes', 'smash', 'counterstrike', 'overwatch', 'commons', 'warcraft', 'fighters', 'rocketleague');
$get_wikis = isset($_GET['wikis']) && is_array($_GET['wikis']) ? array_values($_GET['wikis']) : $default_wikis;
$clean_wikis_list = array();
foreach ($get_wikis as $wiki) {
	if (preg_match('/^(starcraft|starcraft2|dota2|hearthstone|heroes|smash|counterstrike|overwatch|commons|warcraft|fighters|rocketleague)$/', $wiki)) {
		$clean_wikis_list[] = $wiki;
	}
}

$wiki_names = array(
	'starcraft' => 'Brood War',
	'starcraft2' => 'StarCraft II',
	'dota2' => 'Dota 2',
	'hearthstone' => 'Hearthstone',
	'heroes' => 'Heroes',
	'smash' => 'Smash Bros',
	'counterstrike' => 'Counter-Strike',
	'overwatch' => 'Overwatch',
	'commons' => 'Commons',
	'warcraft' => 'Warcraft',
	'fighters' => 'Fighting Games',
	'rocketleague' => 'Rocket League'
);

global $wikis;
$wikis = array();
foreach ($clean_wikis_list as $wiki) {
	$wikis[$wiki] = $wiki_names[$wiki];
}

$record_cache = getCache($connection, $wikis);
/*if ($params['debug'])
	return;*/

$stats = array();

// *--
// -*- cURL configuration
// --*
$cc = array();
$cc['options'] = array(
	CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; activeusers/1.0; chapatiyaq@gmail.com)',
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_ENCODING => '',
	CURLOPT_COOKIEJAR => $cookieFile,
	CURLOPT_COOKIEFILE => $cookieFile,
	CURLOPT_POST => true,
	CURLOPT_TIMEOUT => 60
);

if (is_int($params['view']) && $params['view'] > 0) {
	$record = getRecord($connection, $params['view']);
	$stats = objectToArray($record->stats);
	completeStats($stats);
	//echo '<h5>$stats</h5><pre>' . print_r( $stats, true ) . '</pre>';
} else {
	foreach (array_keys($wikis) as $wiki) {
		$bot = false;

		$wikiStats = array();

		if (!$params['store_in_db']
			&& isset($record_cache[$wiki])
			&& (time() - strtotime($record_cache[$wiki]['timestamp'])) < 3600 ) {
			// Use the cache if available and recent (from the last hour)
			$bot = true;
			$wikiStats = $record_cache[$wiki]['record'];
		} else {
			// Trying to log in with the bot, for bigger queries...
			if (isset($loginName) && isset($loginPass) && $loginName !== '' && $loginPass !== '') {
				// *--
				// -*- Initialize a new cURL session
				// --*
				$curl = curl_init();
				curl_setopt_array($curl, $cc['options'] );
				curl_setopt($curl, CURLOPT_URL, 'http://wiki.teamliquid.net/' . $wiki . '/api.php');

				// *--
				// -*- Check user info
				// --*
				$postdata = http_build_query(array(
					'action' => 'query',
					'meta' => 'userinfo',
					'format' => 'php'
				));
				curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
				$data = unserialize(curl_exec($curl));

				$loginStatus = '';
				if (!isset($data['query']['userinfo']['anon']) && $data['query']['userinfo']['name'] == $loginName) {
					$loginStatus = 'Logged in from cookie as ' . $loginName;
				} else {
					// *--
					// -*- Login
					// --*
					$postdata = http_build_query(array(
						'action' => 'login',
						'lgname' => $loginName,
						'format' => 'php'
					));
					curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
					$data = unserialize(curl_exec($curl));
					//echo '<h5>$data[\'login\']</h5><pre>' . print_r( $data['login'], true ) . '</pre>';
					$loginToken = $data['login']['token'];

					if ( $data['login']['result'] == 'NeedToken') {
						$postdata = http_build_query(array(
							'action' => 'login',
							'lgname' => $loginName,
							'lgpassword' => $loginPass,
							'lgtoken' => $loginToken,
							'format' => 'php'
						));
						curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
						$data = unserialize(curl_exec($curl));
						//echo '<h5>$data[\'login\']</h5><pre>' . print_r($data['login'], true) . '</pre>';

						if ($data['login']['result'] == 'Success') {
							$loginStatus = 'Logged in from login info as ' . $loginName;
							$bot = true;
							// *--
							// -*- Prepare cookie vars
							// --*
							$cookiePrefix = $data['login']['cookieprefix'];
							$cookieVars = array(
								$cookiePrefix . '_session=' . $data['login']['sessionid'],
								$cookiePrefix . 'UserID=' . $data['login']['lguserid'],
								$cookiePrefix . 'UserName=' . $data['login']['lgusername'],
								$cookiePrefix . 'Token=' . $data['login']['lgtoken']
							);
							$isNewCookieSet = setrawcookie($toluenoCookieName, implode('|', $cookieVars), strtotime('+1 day'), '/liquipedia/', 'tolueno.fr');
						} else {
							$loginStatus = 'Error when logging in as ' . $loginName;
							//exit(3);
							$bot = false;
						}
					} else {
						$loginStatus = 'Error when logging in as ' . $loginName;
						//exit(3);
						$bot = false;
					}
				}

				// Close the cURL session to save cookies
				curl_close($curl);
			} else {
				$bot = false;
			}

			// *--
			// -*- Initialize a new cURL session
			// --*
			$curl = curl_init();
			curl_setopt_array($curl, $cc['options'] );
			curl_setopt($curl, CURLOPT_URL, 'http://wiki.teamliquid.net/' . $wiki . '/api.php');

			// Contributions
			$postdata = array(
				'action' => 'query',
				'list' => 'allusers',
				'auwitheditsonly' => true,
				'auactiveusers' => true,
				'auprop' => 'editcount|groups',
				'aulimit' => ($bot ? 5000 : 500),
				'continue' => '',
				'format' => 'php'
			);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
			$continue = getContributions($curl, $postdata, $wikiStats);
			while ( $continue !== 0 ) {
				$postdata['aufrom'] = str_replace(' ', '%20', $continue['aufrom']);
				$continue = getContributions($curl, $postdata, $wikiStats);
			}

			$stmt = $connection->prepare("UPDATE activeusers_cache SET `record` = :record WHERE `wiki` = :wiki");
			$record = $wikiStats;
			$stmt->bindValue(':record', json_encode($record));
			$stmt->bindValue(':wiki', $wiki);
			$stmt->execute();

			curl_close($curl);
		}

		mergeWikiStats($stats, $wiki, $wikiStats);
	}
}

foreach ($stats as &$userstats) {
	$userstats['count_total'] = 0;
	foreach (array_keys($wikis) as $wiki) {
		if (isset($userstats['count_' . $wiki])) {
			$userstats['count_total'] += $userstats['count_' . $wiki];
		}
	}
}
unset($userstats);

uasort($stats, 'cmp');
function cmp($a, $b) {
	return $b['count_total'] - $a['count_total'];
}

$i = 1;
$total = array();
$total_without_bots = array();
foreach(array_keys($wikis) as $wiki) {
	$total[$wiki] = 0;
	$total_without_bots[$wiki] = 0;
}
foreach($stats as $username => $userstats) {
	foreach(array_keys($wikis) as $wiki) {
		if (isset($userstats['count_' . $wiki])) {
			$total[$wiki] += $userstats['count_' . $wiki];
			if (isset($userstats['groups_' . $wiki])) {
				if (!in_array('bot', $userstats['groups_' . $wiki])) {
					$total_without_bots[$wiki] += $userstats['count_' . $wiki];
				}
			}
		}
	}
}
$total['all'] = 0;
$total_without_bots['all'] = 0;
foreach(array_keys($wikis) as $wiki) {
	$total['all'] += $total[$wiki];
	$total_without_bots['all'] += $total_without_bots[$wiki];
}
?>
	<div class="table-wrapper">
		<table>
			<tr class="header-row">
				<th class="pos"></th>
				<th class="name">Name</th>
				<?php foreach($wikis as $url_part => $name) {
					echo '<th class="wiki ' . $url_part . '"><div title="' . $name . '"></div></th>';
				} ?>
				<th class="total">Total</th>
			</tr>
			<tr class="total-row">
				<td class="pos"></td>
				<td class="name">Total</td>
<?php
foreach(array_keys($wikis) as $wiki) {
	echo '<td class="wiki ' . $wiki . '">' . $total[$wiki];
	if ($params['compare_to_record']) {
		$count_diff = $total[$wiki];
		if (isset($previous_record->total)) {
			if (isset($previous_record->total->{$wiki})) {
				$count_diff -= $previous_record->total->{$wiki};
			}
		}
		echo diffSpanHtml($count_diff);
	}
	echo '</td>';
}
echo '<td class="total">' . $total['all'];
if ($params['compare_to_record']) {
	$count_diff = $total['all'];
	if (isset($previous_record->total)) {
		if (isset($previous_record->total->all)) {
			$count_diff -= $previous_record->total->all;
		}
	}
	echo diffSpanHtml($count_diff);
}
echo '</td>';
?>
			</tr>
			<tr class="total-row">
				<td class="pos"></td>
				<td class="name">Total w/o bots</td>
<?php
foreach(array_keys($wikis) as $wiki) {
	echo '<td class="wiki ' . $wiki . '">' . $total_without_bots[$wiki];
	if ($params['compare_to_record']) {
		$count_diff = $total_without_bots[$wiki];
		if (isset($previous_record->total_without_bots)) {
			if (isset($previous_record->total_without_bots->{$wiki})) {
				$count_diff -= $previous_record->total_without_bots->{$wiki};
			}
		}
		echo diffSpanHtml($count_diff);
	}
	echo '</td>';
}
echo '<td class="total">' . $total_without_bots['all'];
if ($params['compare_to_record']) {
	$count_diff = $total_without_bots['all'];
	if (isset($previous_record->total_without_bots)) {
		if (isset($previous_record->total_without_bots->all)) {
			$count_diff -= $previous_record->total_without_bots->all;
		}
	}
	echo diffSpanHtml($count_diff);
}
echo '</td>';
?>
			</tr>
<?php
foreach($stats as $username => $userstats) {
	echo '<tr class="user-row">';
	echo '<td class="pos">' . $i . '.</td>';
	echo '<td class="name">';
	if ($params['show_flags'] && isset($flags[$username])) {
		echo '<span class="flag-icon flag-icon-' . $flags[$username] . '"></span>&nbsp;';
	}
	echo '<a href="http://tolueno.fr/liquipedia/userstats/?user=' . $username . '">' . $username . '</a></td>';
	foreach ($wikis as $url_part => $name) {
		if (isset($userstats['groups_' . $url_part])) {
			echo '<td class="wiki ' . $url_part . ' ' . implode(' ', $userstats['groups_' . $url_part]) . '">';
		} else {
			echo '<td class="wiki ' . $url_part . '">';
		}

		if (isset($userstats['count_' . $url_part]) && $userstats['count_' . $url_part] != 0) {
			echo '<a title="Contributions on Liquipedia: ' . $name
				. '" href="http://wiki.teamliquid.net/' . $url_part . '/Special:Contributions/' . $username . '">';
			echo $userstats['count_' . $url_part];
			echo '</a>';
		} else {
			echo '-';
		}
		if ($params['compare_to_record']) {
			$count_diff = $userstats['count_' . $url_part];
			if (isset($previous_record->stats->{$username})) {
				if (isset($previous_record->stats->{$username}->{'count_' . $url_part})) {
					$count_diff -= $previous_record->stats->{$username}->{'count_' . $url_part};
				}
			}
			echo diffSpanHtml($count_diff);
		}
		echo '</td>';
	}
	echo '<td class="total">' . $userstats['count_total'];
	if ($params['compare_to_record']) {
		$count_diff = $userstats['count_total'];
		if (isset($previous_record->stats->{$username})) {
			if (isset($previous_record->stats->{$username}->{'count_total'})) {
				$count_diff -= $previous_record->stats->{$username}->{'count_total'};
			}
		}
		echo diffSpanHtml($count_diff);
	}
	echo '</td>';
	echo '</tr>';
	++$i;
	if ($i > 2000)
		break;
}
if (($clean_wikis_list == $default_wikis) && $params['store_in_db']) {
	$stmt = $connection->prepare("INSERT INTO activeusers (`record`) VALUES (:record)");
	$record = array('stats' => $stats, 'total' => $total, 'total_without_bots' => $total_without_bots);
	$stmt->bindValue(':record', json_encode($record));
	$stmt->execute();
}
?>
		</table>
	</div>