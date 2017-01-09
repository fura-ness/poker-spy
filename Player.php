<?
/* vim: set tabstop=4: */

/*
$dsn = 'mysql:dbname=poker;host=127.0.0.1';
$user = '';
$password = '';
$dbh = new PDO($dsn, $user, $password);

$player = new Player('tfederman', 1);
$player->readId($dbh);
echo $player->getId() . "\n";
*/

class Player
{
	private $site_id;
	private $name;
	private $player_id;

	public function Player($name, $site_id)
	{
		$this->name = $name;
		$this->site_id = $site_id;
		$this->player_id = -1;
	}

	public function readId($dbh)
	{
		$sql = 'SELECT player_id FROM players where site_id=:site_id and name=:name';

		$sth = $dbh->prepare($sql);
		$sth->bindParam(':site_id', $this->site_id, PDO::PARAM_INT);
		$sth->bindParam(':name', $this->name, PDO::PARAM_STR, 32);

		if ($sth->execute())
		{
			$result = $sth->fetch(PDO::FETCH_ASSOC);
			if ($result)
			{
				$this->player_id = $result['player_id'];
			}
		}
	}

	public function save($dbh)
	{
		$sql = 'insert into players (player_id, site_id, name) values(null, :site_id, :name)';

		$sth = $dbh->prepare($sql);
		$sth->bindParam(':site_id', $this->site_id, PDO::PARAM_INT);
		$sth->bindParam(':name', $this->name, PDO::PARAM_STR, 32);

		$success = $sth->execute();

		if ($success)
		{
			$this->player_id = $dbh->lastInsertId();
		}

		return $success;
	}		

	public function getId()
	{
		return $this->player_id;
	}

	public function getSiteId()
	{
		return $this->site_id;
	}

	public function getName()
	{
		return $this->name;
	}
}

?>
