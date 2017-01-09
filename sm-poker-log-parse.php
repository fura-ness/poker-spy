<?
/* vim: set tabstop=4: */

/* usage: 

   for file in `find . -name "*.log"`
   do
       echo $file
       cat $file |php ddpokerparse.php
   done
*/

require_once('PokerUtil.php');
require_once('ParseUtil.php');
require_once('ParsedHand.php');
require_once('ParsedLog.php');
require_once('Player.php');

$mysqli = new mysqli("localhost", "username", "password", "poker");           /* (was in progress of converting */
$dbh = new PDO('mysql:dbname=poker;host=127.0.0.1', 'username', 'password');  /*  from mysqli to PDO) */

$log = null; 

$parseutil = new ParseUtil($mysqli);

$handle = fopen('php://stdin', 'r');

$parse_state = PokerUtil::STATE_BEGIN;

while (!feof($handle))
{
	$line = fgets($handle);
	$line = trim($line);

	/* parse the current line based on the current state, 
       return new current state,
	   add/update player and/or hand object to the log object as needed */
	$parse_state = handle_state($line, $log, $parse_state, $parseutil);
}

/* for each player in log, write to players table if doesn't exist */
$log->writePlayers($dbh);

/* write each hand details to db */
$log->writeHands($dbh);

fclose($handle);
$mysqli->close();
$dbh = null;

function handle_state($line, &$log, $parse_state, $parseutil)
{
	if (isset($log))
	{
		$parsedhand = $log->getCurrentHand();
	}

	switch ($parse_state)
	{
		case PokerUtil::STATE_BEGIN:
			if ($fields = $parseutil->parse(-1, 'sitename', $line))
			{
				switch ($fields['sitename'])
				{
					case 'Full Tilt Poker':
						$log = new ParsedLog(PokerUtil::SITE_ID_FULLTILT);
						break;
					case 'PokerStars':
						$log = new ParsedLog(PokerUtil::SITE_ID_POKERSTARS);
						break;
					default:
						die("can't determine site: $line\n");
						break;
				}
			}
			else
			{
				die("can't determine site\n");
			}

		case PokerUtil::STATE_BETWEEN_GAMES:
			if ($fields = $parseutil->parse($log->getSiteId(), 'gameinfo', $line))
			{
				$newph = new ParsedHand('Full Tilt', $fields['game_id'], $fields['bet_low'], $fields['bet_high'], $fields['game_type'], $fields['game_time'], $fields['game_date']);

				$log->addParsedHand($newph);

				$parse_state = PokerUtil::STATE_GAME_INTRO;
			}
			break;

		case PokerUtil::STATE_GAME_INTRO:
		case PokerUtil::STATE_SEATING:
			if ($fields = $parseutil->parse($log->getSiteId(), 'seatnumber', $line))
			{
				$log->logPlayer($fields['playername']);
			
				$parsedhand->addPlayerAtSeat($fields['playername'], $fields['seatnumber']);

				$parse_state = PokerUtil::STATE_SEATING;
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'button', $line))
			{
				$parsedhand->setButton($fields['seatnumber']);
				$parse_state = PokerUtil::STATE_SEATING;
			}
			else if (ignore_line($line))
			{
				$parse_state = PokerUtil::STATE_SEATING;
			}
			else
			{
				$parse_state = PokerUtil::STATE_BLINDS;
				return handle_state($line, $log, $parse_state, $parseutil);
			}
			break;

		case PokerUtil::STATE_BLINDS:
			if ($fields = $parseutil->parse($log->getSiteId(), 'blind', $line))
			{
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'deadblind', $line))
			{
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'otherblind', $line))
			{
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'button', $line))
			{
				$parsedhand->setButton($fields['seatnumber']);
			}
			else if (ignore_line($line))
			{
			}
			else
			{
				$parse_state = PokerUtil::STATE_PREFLOP;
			}
			break;

		case PokerUtil::STATE_PREFLOP:
			if (ignore_line($line))
			{
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'flopheader', $line))
			{
				$parse_state = PokerUtil::STATE_FLOP;
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'summaryheader', $line))
			{
				$parse_state = PokerUtil::STATE_SUMMARY;
			}
			break;

		case PokerUtil::STATE_FLOP:
			if (ignore_line($line))
			{
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'turnheader', $line))
			{
				$parse_state = PokerUtil::STATE_TURN;
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'summaryheader', $line))
			{
				$parse_state = PokerUtil::STATE_SUMMARY;
			}
			break;
			
		case PokerUtil::STATE_TURN:
			if (ignore_line($line))
			{
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'riverheader', $line))
			{
				$parse_state = PokerUtil::STATE_RIVER;
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'summaryheader', $line))
			{
				$parse_state = PokerUtil::STATE_SUMMARY;
			}
			break;
			
		case PokerUtil::STATE_RIVER:
			if (ignore_line($line))
			{
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'showdownheader', $line))
			{
				$parse_state = PokerUtil::STATE_SHOWDOWN;
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'summaryheader', $line))
			{
				$parse_state = PokerUtil::STATE_SUMMARY;
			}
			break;

		case PokerUtil::STATE_SHOWDOWN:
			if (ignore_line($line))
			{
			}
			else if ($fields = $parseutil->parse($log->getSiteId(), 'summaryheader', $line))
			{
				$parse_state = PokerUtil::STATE_SUMMARY;
			}
			break;

		case PokerUtil::STATE_SUMMARY:
			if ($fields = $parseutil->parse($log->getSiteId(), 'showdown', $line))
			{
				$player = $fields['playername'];
				$player = str_replace(" (big blind)", "", $player);
				$player = str_replace(" (small blind)", "", $player);
				$player = str_replace(" (button)", "", $player);
				$card1 = $fields['rank1'] . $fields['suit1'];
				$card2 = $fields['rank2'] . $fields['suit2'];
				$code1 = PokerUtil::getCardCode($card1);
				$code2 = PokerUtil::getCardCode($card2);
				$parsedhand->addHandShown($player, $card1, $card2);	
			}
			else if (ignore_line($line))
			{
			}
			else if (strlen($line) == 0)
			{
				$parse_state = PokerUtil::STATE_BETWEEN_GAMES;
			}
			break;		

		default:
			$parse_state = PokerUtil::STATE_BETWEEN_GAMES;
			break;
	}

	return $parse_state;
}

/* these lines may show up in any state and should be ignored without triggering a state change */
function ignore_line($line)
{
	return (
		preg_match("/adds \\$/", $line) > 0 ||
		preg_match("/\d+ seconds left to act$/", $line) > 0 ||
		preg_match("/ is sitting out$/", $line) > 0 ||
		preg_match("/ sits down$/", $line) > 0 ||
		preg_match("/^Time has expired$/", $line) > 0 ||
		preg_match("/^.* is feeling happy$/", $line) > 0
	);
}

?>
