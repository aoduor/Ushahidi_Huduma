<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for reported Incidents
 *
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @module     Incident Model
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

class Incident_Model extends ORM
{
	protected $has_many = array('category' => 'incident_category', 'media', 'verify', 'comment',
		'rating', 'alert' => 'alert_sent', 'incident_lang', 'form_response','cluster' => 'cluster_incident',
		'geometry');
	protected $has_one = array('location','incident_person','user','message','twitter','form');
	protected $belongs_to = array('sharing');

	// Database table name
	protected $table_name = 'incident';

	// Prevents cached items from being reloaded
	protected $reload_on_wakeup   = FALSE;


    /**
     * Validates the incident data before saving
     *
     * @param   array     $array   Data to be validated
     * @param   boolean   $save
     * @return  boolean
     */
    public function validate(array & $array, $save = FALSE)
    {
        $array  = Validation::factory($array)
                    ->pre_filter('trim', TRUE)
                    ->add_rules('incident_title', 'required', 'length[3,200]')
                    ->add_rules('incident_description', 'required')
                    ->add_rules('incident_date', 'required', 'date_mmddyyyy')
                    ->add_rules('location_id', 'required', array('Location_Model','is_valid_location'));
        
        // Apply extra validation rules
        Event::run('ushahidi_action.orm_validate_incident', $array);
        
        return parent::validate($array, $save);
    }

    /**
     * Returns an array of the active categories
     */
    static function get_active_categories()
    {
		// Get all active categories
		$categories = array();
		foreach (ORM::factory('category')
			->where('category_visible', '1')
			->find_all() as $category)
		{
			// Create a list of all categories
			$categories[$category->id] = array($category->category_title, $category->category_color);
		}
		return $categories;
	}

	/**
	 * Get the total number of reports
	 * @param approved - Only count approved reports if true
	 */
	public static function get_total_reports($approved=false)
	{
		$count = ($approved)
			? ORM::factory('incident')->where('incident_active', '1')->count_all()
			: ORM::factory('incident')->count_all();

		return $count;
	}

	/**
	 * Get the total number of verified or unverified reports
	 * @param verified - Only count verified reports if true, unverified if false
	 */
	public static function get_total_reports_by_verified($verified=false)
	{
		$count = ($verified)
			? ORM::factory('incident')->where('incident_verified', '1')->where('incident_active', '1')->count_all()
			: ORM::factory('incident')->where('incident_verified', '0')->where('incident_active', '1')->count_all();

		return $count;
	}

	/**
	 * Get the total number of verified or unverified reports
	 * @param approved - Oldest approved report timestamp if true (oldest overall if false)
	 */
	public static function get_oldest_report_timestamp($approved=true)
	{
		$result = ($approved)
			? ORM::factory('incident')->where('incident_active', '1')->orderby(array('incident_date'=>'ASC'))->find_all(1,0)
			: ORM::factory('incident')->where('incident_active', '0')->orderby(array('incident_date'=>'ASC'))->find_all(1,0);

		foreach($result as $report)
		{
			return strtotime($report->incident_date);
		}
	}

	private static function category_graph_text($sql, $category)
	{
		$db = new Database();
		$query = $db->query($sql);
		$graph_data = array();
		$graph = ", \"".  $category[0] ."\": { label: '". str_replace("'","",$category[0]) ."', ";
		foreach ( $query as $month_count )
		{
			array_push($graph_data, "[" . $month_count->time * 1000 . ", " . $month_count->number . "]");
		}
		$graph .= "data: [". join($graph_data, ",") . "], ";
		$graph .= "color: '#". $category[1] ."' ";
		$graph .= " } ";
		return $graph;
	}

	static function get_incidents_by_interval($interval='month',$start_date=NULL,$end_date=NULL,$active='true',$media_type=NULL)
	{
		// Table Prefix
		$table_prefix = Kohana::config('database.default.table_prefix');

		// get graph data
		// could not use DB query builder. It does not support parentheses yet
		$db = new Database();

		$select_date_text = "DATE_FORMAT(incident_date, '%Y-%m-01')";
		$groupby_date_text = "DATE_FORMAT(incident_date, '%Y%m')";
		
		if ($interval == 'day')
		{
			$select_date_text = "DATE_FORMAT(incident_date, '%Y-%m-%d')";
			$groupby_date_text = "DATE_FORMAT(incident_date, '%Y%m%d')";
		}
		elseif ($interval == 'hour')
		{
			$select_date_text = "DATE_FORMAT(incident_date, '%Y-%m-%d %H:%M')";
			$groupby_date_text = "DATE_FORMAT(incident_date, '%Y%m%d%H')";
		}
		elseif ($interval == 'week')
		{
			$select_date_text = "STR_TO_DATE(CONCAT(CAST(YEARWEEK(incident_date) AS CHAR), ' Sunday'), '%X%V %W')";
			$groupby_date_text = "YEARWEEK(incident_date)";
		}

		$date_filter = "";
		if ($start_date)
		{
			$date_filter .= ' AND incident_date >= "' . $start_date . '"';
		}
		
		if ($end_date)
		{
			$date_filter .= ' AND incident_date <= "' . $end_date . '"';
		}

		$active_filter = ($active == 'all' || $active == 'false')? '0,1' : '1';

		$joins = '';
		$general_filter = '';
		
		if (isset($media_type) && is_numeric($media_type))
		{
			$joins = 'INNER JOIN '.$table_prefix.'media AS m ON m.incident_id = i.id';
			$general_filter = ' AND m.media_type IN ('. $media_type  .')';
		}

		$graph_data = array();
		$all_graphs = array();

		$all_graphs['0'] = array();
		$all_graphs['0']['label'] = 'All Categories';
		$query_text = 'SELECT UNIX_TIMESTAMP(' . $select_date_text . ') AS time,
					   COUNT(*) AS number
					   FROM '.$table_prefix.'incident AS i ' . $joins . '
					   WHERE incident_active IN (' . $active_filter .')' .
		$general_filter .'
					   GROUP BY ' . $groupby_date_text;
		$query = $db->query($query_text);
		$all_graphs['0']['data'] = array();
		
		foreach ( $query as $month_count )
		{
			array_push($all_graphs['0']['data'],
				array($month_count->time * 1000, $month_count->number));
		}
		
		$all_graphs['0']['color'] = '#990000';

		$query_text = 'SELECT category_id, category_title, category_color, UNIX_TIMESTAMP(' . $select_date_text . ')
							AS time, COUNT(*) AS number
								FROM '.$table_prefix.'incident AS i
							INNER JOIN '.$table_prefix.'incident_category AS ic ON ic.incident_id = i.id
							INNER JOIN '.$table_prefix.'category AS c ON ic.category_id = c.id
							' . $joins . '
							WHERE incident_active IN (' . $active_filter . ')
								  ' . $general_filter . '
							GROUP BY ' . $groupby_date_text . ', category_id ';
		$query = $db->query($query_text);
		foreach ( $query as $month_count )
		{
			$category_id = $month_count->category_id;
			if (!isset($all_graphs[$category_id]))
			{
				$all_graphs[$category_id] = array();
				$all_graphs[$category_id]['label'] = $month_count->category_title;
				$all_graphs[$category_id]['color'] = '#'. $month_count->category_color;
				$all_graphs[$category_id]['data'] = array();
			}
			array_push($all_graphs[$category_id]['data'],
				array($month_count->time * 1000, $month_count->number));
		}
		$graphs = json_encode($all_graphs);
		return $graphs;
	}

	/**
	 * Get the number of reports by date for dashboard chart
	 */
	public static function get_number_reports_by_date($range=NULL)
	{
		// Table Prefix
		$table_prefix = Kohana::config('database.default.table_prefix');
		
		$db = new Database();
		
		if ($range == NULL)
		{
			$sql = 'SELECT COUNT(id) as count, DATE(incident_date) as date, MONTH(incident_date) as month, DAY(incident_date) as day, YEAR(incident_date) as year FROM '.$table_prefix.'incident GROUP BY date ORDER BY incident_date ASC';
		}else{
			$sql = 'SELECT COUNT(id) as count, DATE(incident_date) as date, MONTH(incident_date) as month, DAY(incident_date) as day, YEAR(incident_date) as year FROM '.$table_prefix.'incident WHERE incident_date >= DATE_SUB(CURDATE(), INTERVAL '.mysql_escape_string($range).' DAY) GROUP BY date ORDER BY incident_date ASC';
		}
		
		$query = $db->query($sql);
		$result = $query->result_array(FALSE);
		
		$array = array();
		foreach ($result AS $row)
		{
			$timestamp = mktime(0,0,0,$row['month'],$row['day'],$row['year'])*1000;
			$array["$timestamp"] = $row['count'];
		}

		return $array;
	}

	/**
	 * Returns an array of the dates of all approved incidents
	 */
	static function get_incident_dates()
	{
		//$incidents = ORM::factory('incident')->where('incident_active',1)->incident_date->find_all();
		$incidents = ORM::factory('incident')->where('incident_active',1)->select_list('id', 'incident_date');
		$array = array();
		foreach ($incidents as $id => $incident_date)
		{
			$array[] = $incident_date;
		}
		return $array;
	}
	
	/**
	 * Checks if the specified incident id is valid and exists in the database
	 *
	 * @param   int $incident_id
	 * @return  boolean
	 */
	public static function is_valid_incident($incident_id)
	{
	    return (preg_match('/^[1-9](\d*)$/', $incident_id) > 0)
	        ? self::factory('incident', $incident_id)->loaded
	        : FALSE;
	}
	
	/**
	 * Gets the comments for the specified incident
 	 *
	 * @return ORM_Iterator
	 */
	public static function get_comments($incident_id)
	{
		if ( ! self::is_valid_incident($incident_id))
		{
			// Validation failed!
			return FALSE;
		}
		else
		{
			// Return list of comments for the incident
			return self::factory('comment')
					->where(array('incident_id' => $incident_id, 'comment_spam' => 0, 'comment_active' => 1))
					->orderby('comment_date', 'desc')
					->find_all();
		}
	}

}
