<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Creates google charts links.
 *
 * @author     Andrew Edwards
 */
class Kohana_Charts {

	protected $config = array();
	
	protected $chart_types = array('line');

	/**
	 * Creates a new Charts object.
	 *
	 * @param   array  configuration
	 * @return  Charts
	 */
	public static function factory(array $config = array())
	{
		return new Charts($config);
	}

	/**
	 * Charts constructor. Setup config
	 *
	 * @param   array  configuration
	 * @return  void
	 */
	public function __construct(array $config = array())
	{
		// Overwrite system defaults with application defaults
		$this->config = $this->config_group() + $this->config;
	}

	/**
	 * Retrieves a charts config group from the config file. One config group can
	 * refer to another as its parent, which will be recursively loaded.
	 *
	 * @param   string  charts config group; "default" if none given
	 * @return  array   config settings
	 */
	public function config_group($group = 'default')
	{
		// Load the pagination config file
		$config_file = Kohana::config('pagination');

		// Initialize the $config array
		$config['group'] = (string) $group;

		// Recursively load requested config groups
		while (isset($config['group']) AND isset($config_file->$config['group']))
		{
			// Temporarily store config group name
			$group = $config['group'];
			unset($config['group']);

			// Add config group values, not overwriting existing keys
			$config += $config_file->$group;
		}

		// Get rid of possible stray config group names
		unset($config['group']);

		// Return the merged config group settings
		return $config;
	}


	
	public function type($chart_type)
	{
		if ( ! in_array($chart_type, $this->chart_types))
		{
			throw new Kohana_Exception('Tried to load a invalid chart type: :chart', array(':chart' => $chart_type));
		}
		
		$this->config['type'] = $chart_type;
		
		return $this;
	}	

	private function chart_type()
	{
		switch ($this->config['type'])
		{
			case 'line':
			default:
				return 'lc';
			break;
		}
	}

	public function fill()
	{
		return $this;
	}
	
	public function line()
	{
		return $this;
	}

	public function render()//array $x_axis, array $y_axis, array $line_data)
	{
		//http://chart.apis.google.com/chart?cht=lc&chxl=0:|2010-03-11|2010-04-10|2010-05-10|2010-06-09|1:|0|34|42|56|85|170&chm=B,EBF5FB,0,0,0&chco=008Cd6&chls=3,1,0&chg=8.3,20,1,4&chd=s:FPOPOPXMKRQYaTSafcWVQPLRJRRV9ghyfdeSVaUVZWXULjfXeSQdaffgaMbgeedXPObVWhaKykmpbbVbhbZwhciup1&chxt=x,y&chs=920x200
		
		$image = 'http://chart.apis.google.com/chart';
		
		// Chart typeLine chart
		$image .= '?cht='.$this->chart_type();
		
		// X and Y axis (&chxl=0:|2010-03-11|2010-04-10|2010-05-10|2010-06-09|1:|0|34|42|56|85|170)
		$image .= '&chxl=0:|'.implode('|',$x_axis).'|1:|'.implode('|',$y_axis);
		
		// Line graph fill
		$image .= '&chm=B,EBF5FB,0,0,0';
		
		// Line colour
		$image .= '&chco=008Cd6';
		
		// Line style (<line_1_thickness>,<dash_length>,<space_length>)
		$image .= '&chls=3,1,0';
		
		// Grid lines
		$image .= '&chg=8.3,20,1,4';
		
		// Data
		//&chd=s:FPOPOPXMKRQYaTSafcWVQPLRJRRV9ghyfdeSVaUVZWXULjfXeSQdaffgaMbgeedXPObVWhaKykmpbbVbhbZwhciup1
		// TODO would be nice to convert it to a serialised version: http://code.google.com/apis/chart/docs/data_formats.html#encoding_data
		//$image .= '&chd=t:'.implode(',',$line_data);
		$image .= '&chd=s:'.self::encoder($line_data);
		
		// Visible axes
		$image .= '&chxt=x,y';
		
		// Chart size
		$image .= '&chs=920x200';
		
		return $image;
	}




	/**
	 * Renders the chart link.
	 *
	 * @return  string  chart url
	 */
	public function __toString()
	{
		return $this->render();
	}

}
