<?
/* vim: set tabstop=4: */

/* contains collections of hand and player objects represented in a log file */
class ParsedLog
{
	private $site_id;

	private $parsedhands;
	private $players;

	private $hand_count;

	private $players_written;

	public function ParsedLog($site_id)
	{
		$this->site_id = $site_id;
		$this->parsedhands = array();
		$this->players = array();
		$this->hand_count = 0;
		$this->players_written = false;
	}

	public function getSiteId()
	{
		return $this->site_id;
	}

	public function getPlayer($name)
	{
		return $this->players[$name];
	}

	public function getPlayers()
	{
		return $this->players;
	}

	public function getCurrentHand()
	{
		return $this->parsedhands[$this->hand_count - 1];
	}		

	public function addParsedHand($parsedhand)
	{
		$this->hand_count++;
		$this->parsedhands[] = $parsedhand;
	}

	public function getParsedHands()
	{
		return $this->parsedhands;
	}

	public function logPlayer($name)
	{
		if (!in_array($name, array_keys($this->players)))
		{
			$this->players[$name] = new Player($name, $this->site_id);
		}
	}

	public function writePlayers($dbh)
	{
		foreach ($this->players as $name => $player)
		{
			$player->readId($dbh);
			if ($player->getId() == -1)
			{
				if (!$player->save($dbh))
				{
					throw new Exception("couldn't save player " . $name);
				}
			}
		}

		$this->players_written = true;
	}

	/* writePlayers must be called prior to this */
	public function writeHands($dbh)
	{
		if (!$this->players_written)
		{
			throw new Exception("players must be written before hands are written");
		}

		foreach ($this->parsedhands as $parsedhand)
		{
			$hands = $parsedhand->getHandsShown();
			foreach ($hands as $hand)
			{
				$playername = $hand[0];
				$player = $this->getPlayer($playername);
				if (!$player)
				{
					throw new Exception("can't find player $playername");
				}

				$position = $parsedhand->getAdjustedPosition($player->getName());
				$player_count = $parsedhand->getPlayerCount();
			
				$card1_rank = PokerUtil::getRankValue($hand[1][0]);
				$card2_rank = PokerUtil::getRankValue($hand[2][0]);
				$card1_suit = PokerUtil::getSuitValue($hand[1][1]);
				$card2_suit = PokerUtil::getSuitValue($hand[2][1]);

				if ($card2_rank > $card1_rank)
				{
					$card2_rank = PokerUtil::getRankValue($hand[1][0]);
					$card1_rank = PokerUtil::getRankValue($hand[2][0]);
					$card2_suit = PokerUtil::getSuitValue($hand[1][1]);
					$card1_suit = PokerUtil::getSuitValue($hand[2][1]);
				}

				if (!$this->writeHand($dbh, $player->getId(), $card1_rank, $card1_suit, $card2_rank, $card2_suit, $position, $player_count, $parsedhand->getGameId()))
				{
					throw new Exception("error writing hand id " . $parsedhand->getGameId());
				}
			}
		}
	}

	public function writeHand($dbh, $player_id, $card1_rank, $card1_suit, $card2_rank, $card2_suit, $position, $players, $game_id)
	{
		$sql = 'insert into player_hands values(:player_id, :card1_rank, :card1_suit, :card2_rank, :card2_suit, :position, :players, :game_id)';

		$sth = $dbh->prepare($sql);
		$sth->bindParam(':player_id',  $player_id,  PDO::PARAM_INT);
		$sth->bindParam(':card1_rank', $card1_rank, PDO::PARAM_INT);
		$sth->bindParam(':card1_suit', $card1_suit, PDO::PARAM_INT);
		$sth->bindParam(':card2_rank', $card2_rank, PDO::PARAM_INT);
		$sth->bindParam(':card2_suit', $card2_suit, PDO::PARAM_INT);
		$sth->bindParam(':position',   $position,   PDO::PARAM_INT);
		$sth->bindParam(':players',    $players,    PDO::PARAM_INT);
		$sth->bindParam(':game_id',    $game_id,    PDO::PARAM_INT);

		$success = $sth->execute();

		return $success;
	}

	/* debugging output */
	public function dump()
	{
		$playerhands = array();
		foreach ($this->parsedhands as $parsedhand)
		{
			$hands = $parsedhand->getHandsShown();
			foreach ($hands as $hand)
			{
				$playerhands[$hand[0]][] = array($hand[1], $hand[2]);
			}
		}

		foreach ($playerhands as $player => $handsplayed)
		{
			echo "$player:\n\t";
			foreach ($handsplayed as $handplayed)
			{
				echo "[" . implode(",", $handplayed) . "] ";
			}
			echo "\n";
		}
		
	}

	public function dumpHTML()
	{
		echo <<< CSS
			<style>
			.redcard {color:red; font-family:helvetica, arial; font-weight:bold}
			.blackcard {color:black; font-family:helvetica, arial; font-weight:bold}
			</style> 
CSS;


	}
}

?>
