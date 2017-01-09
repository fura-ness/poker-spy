<?
/* vim: set tabstop=4: */

require_once('PokerUtil.php');

class ParseUtil
{
	private $patterns = array();

	public function ParseUtil($mysqli)
	{
		$this->read_parse_info($mysqli);
	}

	public function read_parse_info($mysqli)
	{
		$patterns = array();

		$query = 'select * from parseinfo order by parse_info_id, site_id';

		if ($result = $mysqli->query($query))
		{
			while ($row = mysqli_fetch_assoc($result)) 
			{
				$parse_type = $row['parse_type'];
				$site_id = $row['site_id'];
				$regex = $row['regex'];
				$fieldnames = $row['fieldnames'];

				if (!in_array($parse_type, array_keys($patterns)))
				{
					$patterns[$parse_type] = array();
				}

				$patterns[$parse_type][$site_id] = array('regex' => $regex, 'fieldnames' => explode(',', $fieldnames));
			}
			$result->close();
		}
		
		$this->patterns = $patterns;
	}

	public function parse($siteid, $pattern, $str)
	{
		$fields = array();
	
		if ($siteid == -1 || strlen($siteid) == 0)
		{
			throw new Exception("invalid site id: $siteid");
		}

		$preg_pattern = $this->patterns[$pattern][$siteid]['regex'];
		$fieldnames = $this->patterns[$pattern][$siteid]['fieldnames'];

		$matches = array();
		$matchcount = preg_match($preg_pattern, $str, $matches);
		if ($matchcount > 0)
		{
			$index = 0;
			foreach ($this->patterns[$pattern][$siteid]['fieldnames'] as $fieldname)
			{
				$fields[$fieldname] = $matches[$index+1];
				$index++;
			}

			/* need at least a single element in array */
			if (count($fieldnames) == 0)
			{
				$fields['line'] = $str;
			}
		}

		return $fields;
	}
}

?>
