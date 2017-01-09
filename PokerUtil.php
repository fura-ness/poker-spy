<?
/* vim: set tabstop=4: */

/* contains constants, other utils for card/deck/hand logic */
class PokerUtil
{

	/* site ids */
	const SITE_ID_FULLTILT = 1;
	const SITE_ID_POKERSTARS = 2;

	/* state codes */
	const STATE_BEGIN = 0;
	const STATE_GAME_INTRO = 1;
	const STATE_SEATING = 2;
	const STATE_BLINDS = 3;
	const STATE_PREFLOP = 4;
	const STATE_FLOP = 5;
	const STATE_TURN = 6;
	const STATE_RIVER = 7;
	const STATE_SHOWDOWN = 8;
	const STATE_SUMMARY = 9;
	const STATE_BETWEEN_GAMES = 10;

	private static $rank_values = array('2' => 1, '3' => 2, '4' => 3, '5' => 4, 
										'6' => 5, '7' => 6, '8' => 7, '9' => 8,
										't' => 9, 'j' => 10, 'q' => 11, 'k' => 12,
										'a' => 13);

	private static $rank_codes = array('?', '2', '3', '4', '5', '6', '7', 
										'8', '9', 't', 'j', 'q', 'k', 'a');

	private static $suit_values = array('d' => 0, 'c' => 1, 'h' => 2, 's' => 3);

	private static $suit_codes = array('d', 'c', 'h', 's' );

	public static function getSuitValue($suit)
	{
		return PokerUtil::$suit_values[strtolower($suit)];
	}

	public static function getRankValue($rank)
	{
		return PokerUtil::$rank_values[strtolower($rank)];
	}

	public static function getCardCode($card)
	{
		return PokerUtil::$rank_values[strtolower($card[0])] +
				(PokerUtil::$suit_values[strtolower($card[1])] * 13);
	
	}

	public static function getCardText($rank, $suit)
	{
		return PokerUtil::$rank_codes[$rank] . PokerUtil::$suit_codes[$suit];
	}

	public function getCardHTML($card)
	{
		$cardhtml = '';

		switch ($card[1])
		{
			case 'd':
				$cardhtml = "<span class='redcard'>" . strtoupper($card[0]) . "<img src='diamond.gif'></span>";
				break;

			case 'h':
				$cardhtml = "<span class='redcard'>" . strtoupper($card[0]) . "<img src='heart.gif'></span>";
				break;

			case 'c':
				$cardhtml = "<span class='blackcard'>" . strtoupper($card[0]) . "<img src='club.gif'></span>";
				break;

			case 's':
				$cardhtml = "<span class='blackcard'>" . strtoupper($card[0]) . "<img src='spade.gif'></span>";
				break;
		}

		return $cardhtml;
	}

	public function handsAreEqual($rank1, $suit1, $rank2, $suit2, $rank3, $suit3, $rank4, $suit4)
	{

		// if both hands are the same pair
		if ($rank1 == $rank2 && $rank2 == $rank3 && $rank3 == $rank4)
		{
			return true;
		}

		if ($rank1 == $rank3 && $rank2 == $rank4)
		{
			if ($suit1 == $suit2 && $suit3 == $suit4)
			{
				// same ranks, both suited
				return true;
			}
			else if ($suit1 != $suit2 && $suit3 != $suit4)
			{
				// same ranks, both non-suited
				return true;
			}
		}

		return false;
	}

}

?>
