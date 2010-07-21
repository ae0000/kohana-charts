<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Creates google charts links.
 *
 * @author	   Andrew Edwards
 */
class Kohana_Charts {

	protected $config = array();
	
	protected $chart_types = array('line','sparkline');
	protected $available_axis = array('x','t','y','r');
	
	/**
	 * Creates a new Charts object.
	 *
	 * @param	array  configuration
	 * @return	Charts
	 */
	public static function factory()
	{
		return new Charts();
	}

	/**
	 * Charts constructor. Setup config
	 *
	 * @param	array  configuration
	 * @return	void
	 */
	public function __construct($group='default')
	{
		// Overwrite system defaults with application defaults
		$this->config = $this->config_group($group) + $this->config;
	}

	/**
	 * Retrieves a charts config group from the config file. One config group can
	 * refer to another as its parent, which will be recursively loaded.
	 *
	 * @param	string	charts config group; "default" if none given
	 * @return	array	config settings
	 */
	public function config_group($group = 'default')
	{
		// Load the pagination config file
		$config_file = Kohana::config('charts');
		
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

	private function get_type()
	{
		switch ($this->config['type'])
		{
			case 'sparkline':
				return 'ls';
			break;
			case 'line':
			default:
				return 'lc';
			break;
		}
	}

	private function get_series_color()
	{
		return $this->config['series_color'];
	}
	
	private function get_background_fill()
	{
		return implode(',',$this->config['background_fill']);
	}
	
	private function get_line_style()
	{
		return implode(',',$this->config['line_style']);
	}

	private function get_data()
	{
		return $this->config['data'];
	}
	
	/**
	 * We are expecting an array containing 'value' and 'timestamp'
	 * ie. $data = array(array('value'=>123, 'timestamp=>'2010-07-07'),array('value'=>44,'timestamp'=>'2010-07-07'));
	 */
	public function data($data)
	{
		// Serialise the data
		if (is_array($data))
		{
			// How many intervals will we use?
			$intervals = min(count($data), $this->config['interval_max']);
			
			// Dive the data into time segments,
			// Average the results of each segment
			$func_new_array = function() { return array(); };
			$segments = array_map($func_new_array, range(0,$intervals));
			$start = strtotime($data[0]['timestamp']);
			$stop = strtotime($data[count($data) -1]['timestamp']);
			$segment = round(($stop - $start) / $intervals);

			foreach ($data as $statistic)
			{
				// Get the stats time, on the timeline and add its value to the right segment
				$stat_time = strtotime($statistic['timestamp']) - $start;
				if ($stat_time == 0 AND $segment == 0)
				{
					$stat_segment = 0;
				}
				else
				{
					$stat_segment = round($stat_time / $segment);
				}
				$segments[$stat_segment][] = $statistic['value'];
			}

			// Now average the results of each segment, as the new set of data
			$func_average = function($array) { return (empty($array))? 0 : array_sum($array) / count($array); };
			$this->config['data'] = self::encoder(array_map($func_average, $segments));
		}
		else
		{
			$this->config['data'] = '';
		}
	}

	// TODO Would be nice to make this easier to edit
	public function background_fill(array $background_fill)
	{
		$this->config['background_fill'] = $background_fill;
		return $this;
	}
	
	public function series_color(array $series_color)
	{
		$this->config['series_color'] = $series_color;
		return $this;
	}

	public function line_style(array $line_style)
	{
		$this->config['line_style'] = $line_style;
		return $this;
	}
	
	public function show_axis($axis)
	{
		$axis_array = explode(',',$axis);
		
		foreach($axis_array as $ax)
		{
			// If one of the axis is wrong - it all goes to hell
			if ( ! in_array($ax, $this->available_axis))
			{
				return $this;
			}
		}

		$this->config['visible_axis'] = $axis;
		
		return $this;
	}
	
	public function render()//array $x_axis, array $y_axis, array $line_data)
	{
		//http://chart.apis.google.com/chart?cht=lc&chxl=0:|2010-03-11|2010-04-10|2010-05-10|2010-06-09|1:|0|34|42|56|85|170&chm=B,EBF5FB,0,0,0&chco=008Cd6&chls=3,1,0&chg=8.3,20,1,4&chd=s:FPOPOPXMKRQYaTSafcWVQPLRJRRV9ghyfdeSVaUVZWXULjfXeSQdaffgaMbgeedXPObVWhaKykmpbbVbhbZwhciup1&chxt=x,y&chs=920x200
		
		// Google charts api
		$image = 'http://chart.apis.google.com/chart';
		
		// Chart type
		$image .= '?cht='.$this->get_type();
		
		// TODO
		// X and Y axis (&chxl=0:|2010-03-11|2010-04-10|2010-05-10|2010-06-09|1:|0|34|42|56|85|170)
		//$image .= '&chxl=0:|'.implode('|',$x_axis).'|1:|'.implode('|',$y_axis);
		
		// Background fill
		$image .= '&chm='.$this->get_background_fill();
		
		// Series color (its the line color)
		$image .= '&chco='.$this->get_series_color();
		
		// Line style (<line_1_thickness>,<dash_length>,<space_length>)
		$image .= '&chls='.$this->get_line_style();
		
		// Grid lines
		$image .= '&chg=8.3,20,1,4';
		
		// Data (serialised)
		$image .= '&chd=s:'.$this->config['data'];

		// Visible axes
		$image .= '&chxt='.$this->config['visible_axis'];
		
		// Chart size
		$image .= '&chs=920x200';
		return $image;
	}


	/**
	 * Renders the chart link.
	 *
	 * @return	string	chart url
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Serialise the data
	 * See: http://code.google.com/apis/chart/docs/data_formats.html#encoding_data
	 */
	public static function encoder($data)
	{
		$simple_encoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$encoded_data = '';
		$max_val = max($data);
	
		foreach($data as $current_val)
		{
			if (is_numeric($current_val) AND (float)$current_val >= 0)
			{
				$str_pos = round((strlen($simple_encoding) - 1) * (float)$current_val / $max_val);
				
				$encoded_data .= substr($simple_encoding, $str_pos, 1);
			}
			else
			{
				$encoded_data .= '_';
			}
		}
		return $encoded_data;
	} 
			   
}
