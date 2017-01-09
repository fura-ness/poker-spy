<?
/* vim: set tabstop=4: */

class ParsedHand
{
	
	private $site;
	private $game_id;
	private $bet_low;
	private $bet_high;
	private $game_type;
	private $game_time;
	private $game_date;

	private $hands;

	private $players; /* in log file order, one-based, can have gaps */
	private $positions; /* array_flip of $players */
	private $positions_adjusted; /* index 0 is dealer, 1 = small blind, 2 = big blind, etc. */

	private $button; /* seat number of dealer button */

	public function ParsedHand($site, $game_id, $bet_low, $bet_high, $game_type, $game_time, $game_date)
	{
		$this->site = $site;
		$this->game_id = $game_id;
		$this->bet_low = $bet_low;
		$this->bet_high = $bet_high;
		$this->game_type = $game_type;
		$this->game_time = $game_time;
		$this->game_date = $game_date;
		$this->hands = array();
		$this->players = array();
	}

	public function addPlayerAtSeat($name, $seat)
	{
		$this->players[$seat] = $name;
		$this->positions[$name] = $seat;
	}

	public function getAdjustedPosition($name)
	{
		if (!isset($this->positions_adjusted))
		{
			$this->adjustPositions();
		}

		return $this->positions_adjusted[$name];
	}

	public function getPlayerCount()
	{
		if (!isset($this->positions_adjusted))
		{
			$this->adjustPositions();
		}

		return count($this->positions_adjusted);
	}

	public function adjustPositions()
	{
		if (!isset($this->button))
		{
			throw new Exception("trying to adjust positions without the button being set, game id " . $this->game_id);
		}
		
		$positions_adjusted_index = 0;
		$this->positions_adjusted = array();

		foreach ($this->positions as $name => $seat)
		{
			if ($seat >= $this->button)
			{
				$this->positions_adjusted[$name] = $positions_adjusted_index;
				$positions_adjusted_index++;
			}
		}

		foreach ($this->positions as $name => $seat)
		{
			if ($seat < $this->button)
			{
				$this->positions_adjusted[$name] = $positions_adjusted_index;
				$positions_adjusted_index++;
			}
		}
	}

	public function setButton($button)
	{
		if ($button == '0' || $button == 0 || !is_numeric($button))
		{
			throw new Exception("setting non-numeric dealer button: $button");
		}
		$this->button = $button;
	}

	public function printButton()
	{
		echo $this->game_id . ": button is seat " . $this->button . " of " . count($this->players) . " (" . $this->players[$this->button] . ")\n";
		print_r($this->positions);
		print_r($this->positions_adjusted);
	}

	public function addHandShown($player, $card1, $card2)
	{
		$this->hands[] = array($player, $card1, $card2);
	}

	public function getHandsShown()
	{
		return $this->hands;
	}

	public function getGameId()
	{
		return $this->game_id;
	}

	public function toString()
	{
		return $this->game_type . " (" . $this->game_id . ")";
	}
	
}

?>
